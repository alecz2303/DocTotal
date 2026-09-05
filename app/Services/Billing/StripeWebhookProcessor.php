<?php

namespace App\Services\Billing;

use App\Actions\Billing\ConfirmManualSubscriptionPayment;
use App\Actions\Billing\ConfirmManualSubscriptionRecoveryPayment;
use App\Actions\Billing\ProcessFailedPayment;
use App\Actions\Billing\ProcessFailedPaymentRetry;
use App\Actions\Billing\ProcessRecoveredPayment;
use App\Actions\Billing\ProcessSuccessfulPaymentPromotions;
use App\Actions\Billing\ReleaseReservedPromotionalCredits;
use App\Actions\Subscription\RenewSubscription;
use App\Models\BillingWebhookEvent;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use LogicException;
use Stripe\Event;
use Stripe\PaymentIntent;
use Throwable;

class StripeWebhookProcessor
{
    private const RELEVANT_EVENTS = [
        'payment_intent.succeeded',
        'payment_intent.payment_failed',
        'payment_intent.canceled',
    ];

    public function __construct(
        private readonly ConfirmManualSubscriptionPayment $confirmManualPayment,
        private readonly ConfirmManualSubscriptionRecoveryPayment $confirmRecoveryPayment,
        private readonly ProcessFailedPayment $processFailedPayment,
        private readonly ProcessFailedPaymentRetry $processFailedPaymentRetry,
        private readonly ProcessRecoveredPayment $processRecoveredPayment,
        private readonly ProcessSuccessfulPaymentPromotions $processSuccessfulPaymentPromotions,
        private readonly ReleaseReservedPromotionalCredits $releasePromotionalCredits,
        private readonly RenewSubscription $renewSubscription,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function process(Event $event): BillingWebhookEvent
    {
        try {
            return DB::transaction(function () use ($event): BillingWebhookEvent {
                $record = BillingWebhookEvent::query()->firstOrCreate(
                    [
                        'provider' => 'stripe',
                        'provider_event_id' => $event->id,
                    ],
                    [
                        'event_type' => $event->type,
                        'status' => BillingWebhookEvent::STATUS_PROCESSING,
                    ],
                );

                $wasCreated = $record->wasRecentlyCreated;

                $record = BillingWebhookEvent::query()
                    ->whereKey($record->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (in_array($record->status, [
                    BillingWebhookEvent::STATUS_PROCESSED,
                    BillingWebhookEvent::STATUS_IGNORED,
                ], true)) {
                    return $record;
                }

                if (! $wasCreated && $record->status === BillingWebhookEvent::STATUS_PROCESSING) {
                    return $record;
                }

                if ($record->status === BillingWebhookEvent::STATUS_FAILED) {
                    $record->update([
                        'status' => BillingWebhookEvent::STATUS_PROCESSING,
                        'failure_message' => null,
                        'processed_at' => null,
                    ]);
                }

                if (! in_array($event->type, self::RELEVANT_EVENTS, true)) {
                    $record->update([
                        'status' => BillingWebhookEvent::STATUS_IGNORED,
                        'processed_at' => now(),
                    ]);

                    return $record->refresh();
                }

                $intent = $event->data->object;

                if (! $intent instanceof PaymentIntent) {
                    throw new LogicException('El evento Stripe no contiene un PaymentIntent válido.');
                }

                [$tenant, $payment, $paymentMode] = $this->resolveCommercialIdentity($intent);

                match ($event->type) {
                    'payment_intent.succeeded' => $this->processSuccess(
                        $tenant,
                        $payment,
                        $paymentMode,
                    ),
                    'payment_intent.payment_failed' => $this->processFailure(
                        $payment,
                        $paymentMode,
                        $intent,
                    ),
                    'payment_intent.canceled' => $this->processCancellation(
                        $payment,
                        $paymentMode,
                    ),
                };

                $record->update([
                    'tenant_id' => $payment->tenant_id,
                    'payment_id' => $payment->id,
                    'status' => BillingWebhookEvent::STATUS_PROCESSED,
                    'processed_at' => now(),
                ]);

                $this->auditLogger->safeLog(
                    action: 'billing.webhook.processed',
                    auditable: $payment,
                    description: 'Se procesó un evento autenticado del proveedor de pagos.',
                    metadata: [
                        'provider' => 'stripe',
                        'event_type' => $event->type,
                        'payment_uuid' => $payment->uuid,
                    ],
                );

                return $record->refresh();
            });
        } catch (Throwable $exception) {
            $record = BillingWebhookEvent::query()->firstOrCreate(
                ['provider' => 'stripe', 'provider_event_id' => $event->id],
                ['event_type' => $event->type],
            );

            $record->update([
                'status' => BillingWebhookEvent::STATUS_FAILED,
                'failure_message' => $this->safeFailure($exception->getMessage()),
                'processed_at' => now(),
            ]);

            throw $exception;
        }
    }

    private function resolveCommercialIdentity(PaymentIntent $intent): array
    {
        $metadata = $intent->metadata;
        $paymentUuid = (string) ($metadata?->doctotal_payment_uuid ?? '');
        $tenantId = (string) ($metadata?->doctotal_tenant_id ?? $metadata?->tenant_id ?? '');
        $paymentMode = (string) ($metadata?->payment_mode ?? 'automatic');

        if ($paymentUuid === '' || $tenantId === '') {
            throw new LogicException('El evento Stripe no contiene identidad DocTotal suficiente.');
        }

        $payment = Payment::withoutGlobalScopes()
            ->where('uuid', $paymentUuid)
            ->lockForUpdate()
            ->firstOrFail();

        if ($payment->provider !== 'stripe') {
            throw new LogicException('El pago del webhook no pertenece a Stripe.');
        }

        if ((string) $payment->tenant_id !== $tenantId) {
            throw new LogicException('El webhook Stripe no pertenece al tenant del pago.');
        }

        if ($payment->provider_payment_id !== (string) $intent->id) {
            throw new LogicException('El PaymentIntent del webhook no coincide con el pago.');
        }

        if ((int) $intent->amount !== (int) $payment->amount) {
            throw new LogicException('El importe del webhook Stripe no coincide con el pago.');
        }

        if (strtoupper((string) $intent->currency) !== strtoupper($payment->currency)) {
            throw new LogicException('La moneda del webhook Stripe no coincide con el pago.');
        }

        $tenant = Tenant::query()->findOrFail($payment->tenant_id);
        $billingCustomer = $tenant->stripeBillingCustomer();

        if (! $billingCustomer) {
            throw new LogicException('El tenant del webhook no tiene un cliente Stripe configurado.');
        }

        if ((string) $intent->customer !== $billingCustomer->provider_customer_id) {
            throw new LogicException('El PaymentIntent del webhook no pertenece al cliente Stripe del tenant.');
        }

        if ($paymentMode === 'automatic') {
            $subscriptionId = (string) ($metadata?->subscription_id ?? '');

            if ($subscriptionId === '' || $subscriptionId !== (string) $payment->subscription_id) {
                throw new LogicException('El webhook automático no pertenece a la suscripción del pago.');
            }
        }

        return [$tenant, $payment, $paymentMode];
    }

    private function processSuccess(Tenant $tenant, Payment $payment, string $paymentMode): void
    {
        if ($payment->isSucceeded()) {
            return;
        }

        if (! $payment->isPending()) {
            throw new LogicException(
                sprintf(
                    'Un pago Stripe en estado "%s" no puede confirmarse como exitoso.',
                    $payment->status
                )
            );
        }

        if ($paymentMode === 'manual') {
            $this->confirmManualPayment->execute($tenant, $payment, now());

            return;
        }

        if ($paymentMode === 'manual_recovery') {
            $this->confirmRecoveryPayment->execute($tenant, $payment, now());

            return;
        }

        if ($paymentMode !== 'automatic') {
            throw new LogicException('El modo de pago del webhook Stripe no es reconocido.');
        }

        $subscription = $this->paymentSubscription($payment);

        if ($subscription->isPastDue()) {
            $this->processRecoveredPayment->execute(
                $payment,
                now(),
                $payment->provider_payment_id,
            );

            return;
        }

        if (! $subscription->isActive()) {
            throw new LogicException('La suscripción automática no admite el pago recibido en su estado actual.');
        }

        $payment->succeed(now(), $payment->provider_payment_id);
        $this->processSuccessfulPaymentPromotions->execute($payment, $payment->paid_at);
        $this->renewSubscription->execute($subscription);
    }

    private function processFailure(
        Payment $payment,
        string $paymentMode,
        PaymentIntent $intent,
    ): void {
        if (! $payment->isPending()) {
            return;
        }

        $failureCode = (string) ($intent->last_payment_error?->code ?? 'payment_intent_failed');
        $failureMessage = $this->safeFailure(
            (string) ($intent->last_payment_error?->message ?? 'Stripe reportó un pago fallido.')
        );

        if (in_array($paymentMode, ['manual', 'manual_recovery'], true)) {
            $payment->fail(
                now(),
                $failureCode,
                $failureMessage,
                $payment->provider_payment_id,
            );
            $this->releasePromotionalCredits->execute($payment, $payment->failed_at);

            return;
        }

        if ($paymentMode !== 'automatic') {
            throw new LogicException('El modo de pago del webhook Stripe no es reconocido.');
        }

        $subscription = $this->paymentSubscription($payment);

        if ($subscription->isPastDue()) {
            $this->processFailedPaymentRetry->execute(
                $payment,
                now(),
                $failureCode,
                $failureMessage,
                $payment->provider_payment_id,
            );

            return;
        }

        $this->processFailedPayment->execute(
            $payment,
            now(),
            $failureCode,
            $failureMessage,
            $payment->provider_payment_id,
        );
    }

    private function processCancellation(Payment $payment, string $paymentMode): void
    {
        if (! $payment->isPending()) {
            return;
        }

        if (in_array($paymentMode, ['manual', 'manual_recovery'], true)) {
            $payment->cancel(now(), $payment->provider_payment_id);
            $this->releasePromotionalCredits->execute($payment, $payment->canceled_at);

            return;
        }

        if ($paymentMode !== 'automatic') {
            throw new LogicException('El modo de pago del webhook Stripe no es reconocido.');
        }

        $subscription = $this->paymentSubscription($payment);
        $message = 'Stripe canceló el PaymentIntent antes de completar el cobro.';

        if ($subscription->isPastDue()) {
            $this->processFailedPaymentRetry->execute(
                $payment,
                now(),
                'payment_intent_canceled',
                $message,
                $payment->provider_payment_id,
            );

            return;
        }

        $this->processFailedPayment->execute(
            $payment,
            now(),
            'payment_intent_canceled',
            $message,
            $payment->provider_payment_id,
        );
    }

    private function paymentSubscription(Payment $payment): Subscription
    {
        $subscription = $payment->subscription;

        if (! $subscription) {
            throw new LogicException('El pago automático no tiene una suscripción asociada.');
        }

        return $subscription;
    }

    private function safeFailure(string $message): string
    {
        $message = preg_replace('/\b(sk|whsec)_(test|live)?_?[A-Za-z0-9]+\b/i', '[REDACTED]', $message) ?? $message;
        $message = preg_replace('/Bearer\s+[^\s]+/i', 'Bearer [REDACTED]', $message) ?? $message;

        return mb_substr($message, 0, 500);
    }
}
