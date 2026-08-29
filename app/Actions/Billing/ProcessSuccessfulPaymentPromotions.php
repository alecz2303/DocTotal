<?php

namespace App\Actions\Billing;

use App\Actions\Referrals\QualifyReferralFromSuccessfulPayment;
use App\Models\Payment;
use Carbon\CarbonInterface;

class ProcessSuccessfulPaymentPromotions
{
    public function execute(
        Payment $payment,
        CarbonInterface $paidAt,
    ): void {
        /*
         * Los créditos promocionales reservados para este
         * Payment se vuelven definitivos únicamente cuando
         * el pago ha sido exitoso.
         */
        app(
            ConsumeReservedPromotionalCredits::class
        )->execute(
            $payment,
            $paidAt
        );

        /*
         * Después procesamos la posible calificación
         * del referido y la recompensa del referrer.
         */
        app(
            QualifyReferralFromSuccessfulPayment::class
        )->execute(
            $payment,
            $paidAt
        );
    }
}
