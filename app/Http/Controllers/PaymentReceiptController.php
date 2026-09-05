<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\View\View;

class PaymentReceiptController extends Controller
{
    public function __invoke(Payment $payment): View
    {
        abort_unless($payment->isSucceeded(), 404);

        return view('billing.receipt', [
            'payment' => $payment->load('subscription'),
            'tenant' => $payment->tenant,
        ]);
    }
}
