<?php

namespace App\Actions\Billing;

use App\Models\Payment;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

class ProcessRecoveredPayment
{
    public function execute(
        Payment $payment,
        CarbonInterface $paidAt,
        ?string $providerPaymentId = null,
    ): Payment {
        return DB::transaction(
            function () use (
                $payment,
                $paidAt,
                $providerPaymentId,
            ): Payment {
                $payment->refresh();

                if (! $payment->isPending()) {
                    throw new LogicException(
                        sprintf(
                            'El pago recuperado no puede procesarse desde el estado "%s".',
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

                if (! $subscription->isPastDue()) {
                    throw new LogicException(
                        sprintf(
                            'El pago recuperado requiere una suscripción vencida, estado actual "%s".',
                            $subscription->status
                        )
                    );
                }

                $payment->succeed(
                    $paidAt,
                    $providerPaymentId
                );

                app(
                    ProcessSuccessfulPaymentPromotions::class
                )->execute(
                    $payment,
                    $paidAt
                );

                app(
                    RecoverSubscription::class
                )->execute(
                    $subscription,
                    $paidAt,
                    $payment
                );

                return $payment->refresh();
            }
        );
    }
}
