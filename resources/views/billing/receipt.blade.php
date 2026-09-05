<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprobante de pago · DocTotal</title>
    <style>
        body { font-family: sans-serif; color: #1f2937; margin: 40px; }
        .receipt { max-width: 720px; margin: 0 auto; }
        .muted { color: #6b7280; }
        .row { display: flex; justify-content: space-between; border-bottom: 1px solid #e5e7eb; padding: 12px 0; }
        .total { font-size: 1.35rem; font-weight: 700; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
<div class="receipt">
    <h1>DocTotal</h1>
    <p class="muted">Comprobante operativo de pago</p>

    <div class="row"><span>Folio</span><strong>{{ $payment->uuid }}</strong></div>
    <div class="row"><span>Cuenta</span><strong>{{ $tenant->name }}</strong></div>
    <div class="row"><span>Fecha de pago</span><strong>{{ $payment->paid_at?->format('d/m/Y H:i') }}</strong></div>
    <div class="row"><span>Ciclo</span><strong>{{ $payment->billing_cycle }}</strong></div>
    <div class="row"><span>Proveedor</span><strong>{{ strtoupper($payment->provider ?? 'N/A') }}</strong></div>
    <div class="row total"><span>Total pagado</span><strong>{{ number_format($payment->amount / 100, 2) }} {{ strtoupper($payment->currency) }}</strong></div>

    @if ($payment->totalDiscountAmount() > 0)
        <div class="row"><span>Precio contractual</span><span>{{ number_format($payment->contractualAmount() / 100, 2) }} {{ strtoupper($payment->currency) }}</span></div>
        <div class="row"><span>Beneficios aplicados</span><span>-{{ number_format($payment->totalDiscountAmount() / 100, 2) }} {{ strtoupper($payment->currency) }}</span></div>
    @endif

    <p class="muted">Este documento es un comprobante operativo de DocTotal y no sustituye una factura fiscal.</p>
    <button class="no-print" type="button" onclick="window.print()">Imprimir</button>
</div>
</body>
</html>
