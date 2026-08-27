<?php

namespace App\Actions\Billing;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use LogicException;
use Carbon\CarbonInterface;

class ProcessFailedPayment
{
    public function execute(
        Payment $payment,
        CarbonInterface $failedAt,
        ?string $failureCode = null,
        ?string $failureMessage = null,
        ?string $providerPaymentId = null,
    ): Payment {
        return DB::transaction(
            function () use (
                $payment,
                $failedAt,
                $failureCode,
                $failureMessage,
                $providerPaymentId,
            ): Payment {
                $payment->refresh();

                if (! $payment->isPending()) {
                    throw new LogicException(
                        sprintf(
                            'El pago no puede procesarse como fallido desde el estado "%s".',
                            $payment->status
                        )
                    );
                }

                $subscription =
                    $payment->subscription;

                if (! $subscription) {
                    throw new LogicException(
                        'El pago no tiene una suscripción asociada.'
                    );
                }

                if (! $subscription->isActive()) {
                    throw new LogicException(
                        sprintf(
                            'El primer fallo de pago no puede iniciar recuperación desde una suscripción "%s".',
                            $subscription->status
                        )
                    );
                }

                $payment->fail(
                    $failedAt,
                    $failureCode,
                    $failureMessage,
                    $providerPaymentId
                );

                app(
                    StartSubscriptionRecovery::class
                )->execute(
                    $subscription,
                    $failedAt
                );

                return $payment->refresh();
            }
        );
    }
}
