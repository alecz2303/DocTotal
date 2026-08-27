<?php

use App\Actions\Billing\ConfirmManualSubscriptionPayment;
use App\Actions\Billing\ConfirmManualSubscriptionRecoveryPayment;
use App\Actions\Billing\CreateManualSubscriptionPaymentIntent;
use App\Actions\Billing\CreateManualSubscriptionRecoveryPaymentIntent;
use App\Actions\Billing\CreateStripeSetupIntent;
use App\Actions\Billing\RegisterManualPaymentMethodForFuture;
use App\Actions\Billing\RegisterStripePaymentMethod;
use App\Actions\Billing\RemoveStripePaymentMethod;
use App\Actions\Billing\ScheduleSubscriptionPlanChange;
use App\Actions\Billing\UpdateManualPaymentFutureUsage;
use App\Actions\Subscription\ResumeSubscription;
use App\Actions\Subscription\ScheduleSubscriptionCancellation;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Layout('layouts::app')]
    #[Title('Facturación | DocTotal')]
    class extends Component
    {
        public Tenant $tenant;

        public ?Subscription $subscription = null;

        public ?Subscription $latestSubscription = null;

        public ?PaymentMethod $paymentMethod = null;

        public $recentPayments;

        public string $stripeKey = '';

        public bool $cardFormVisible = false;

        public bool $manualPaymentFormVisible = false;

        public ?string $manualPaymentUuid = null;

        public bool $manualSaveForFuture = false;

        public bool $manualRecoveryPayment = false;

        public string $billingCycle =
        Subscription::BILLING_CYCLE_MONTHLY;

        public function mount(): void
        {
            $this->tenant =
                auth()->user()
                ->tenant()
                ->firstOrFail();

            $this->stripeKey =
                (string) config(
                    'services.stripe.key'
                );

            $this->refreshPaymentMethod();

            $this->refreshSubscription();

            $this->refreshBillingHistory();
        }

        public function startCardSetup(): void
        {
            if (! $this->stripeKey) {
                $this->dispatch(
                    'swal',
                    title: 'Stripe no está configurado',
                    text: 'No se encontró la clave pública de Stripe.',
                    icon: 'error'
                );

                return;
            }

            try {
                $clientSecret =
                    app(
                        CreateStripeSetupIntent::class
                    )->execute(
                        $this->tenant
                    );

                $this->cardFormVisible =
                    true;

                $this->dispatch(
                    'stripe-setup-started',
                    clientSecret: $clientSecret,
                    stripeKey: $this->stripeKey,
                );
            } catch (\Throwable $exception) {
                report(
                    $exception
                );

                $this->dispatch(
                    'swal',
                    title: 'No fue posible iniciar Stripe',
                    text: 'Intenta nuevamente en unos momentos.',
                    icon: 'error'
                );
            }
        }

        public function registerPaymentMethod(
            string $paymentMethodId
        ): void {
            if (
                ! str_starts_with(
                    $paymentMethodId,
                    'pm_'
                )
            ) {
                $this->dispatch(
                    'swal',
                    title: 'Método de pago inválido',
                    text: 'Stripe devolvió un identificador no válido.',
                    icon: 'error'
                );

                return;
            }

            try {
                app(
                    RegisterStripePaymentMethod::class
                )->execute(
                    $this->tenant,
                    $paymentMethodId
                );

                $this->refreshPaymentMethod();

                $this->cardFormVisible =
                    false;

                $this->dispatch(
                    'stripe-payment-method-saved'
                );

                $this->dispatch(
                    'swal',
                    title: 'Tarjeta guardada',
                    text: 'Tu método de pago se guardó correctamente.',
                    icon: 'success'
                );
            } catch (\Throwable $exception) {
                report(
                    $exception
                );

                $this->dispatch(
                    'swal',
                    title: 'No fue posible guardar la tarjeta',
                    text: 'Verifica los datos e intenta nuevamente.',
                    icon: 'error'
                );
            }
        }

        public function removePaymentMethod(): void
        {
            $this->refreshPaymentMethod();

            if (! $this->paymentMethod) {
                $this->dispatch(
                    'swal',
                    title: 'Tarjeta no encontrada',
                    text: 'No tienes una tarjeta activa para eliminar.',
                    icon: 'info'
                );

                return;
            }

            try {
                app(
                    RemoveStripePaymentMethod::class
                )->execute(
                    $this->tenant,
                    $this->paymentMethod
                );

                $this->refreshPaymentMethod();

                $this->cardFormVisible =
                    false;

                $this->dispatch(
                    'stripe-payment-method-removed'
                );

                $this->dispatch(
                    'swal',
                    title: 'Tarjeta eliminada',
                    text: 'La tarjeta fue eliminada. Las renovaciones automáticas quedarán deshabilitadas hasta que agregues otra tarjeta.',
                    icon: 'success'
                );
            } catch (\Throwable $exception) {
                report(
                    $exception
                );

                $this->refreshPaymentMethod();

                $this->dispatch(
                    'swal',
                    title: 'No fue posible eliminar la tarjeta',
                    text: 'Intenta nuevamente en unos momentos.',
                    icon: 'error'
                );
            }
        }

        public function selectBillingCycle(
            string $billingCycle
        ): void {
            if (
                ! in_array(
                    $billingCycle,
                    [
                        Subscription::BILLING_CYCLE_MONTHLY,
                        Subscription::BILLING_CYCLE_YEARLY,
                    ],
                    true
                )
            ) {
                throw new \InvalidArgumentException(
                    'El ciclo de facturación no es válido.'
                );
            }

            $this->refreshSubscription();

            if ($this->subscription) {
                return;
            }

            $this->billingCycle =
                $billingCycle;
        }

        public function scheduleBillingCycleChange(
            string $billingCycle
        ): void {
            $this->refreshSubscription();

            if (! $this->subscription) {
                return;
            }

            try {
                $subscription = app(
                    ScheduleSubscriptionPlanChange::class
                )->execute(
                    $this->subscription,
                    $billingCycle
                );

                $this->subscription = $subscription;
                $this->refreshBillingHistory();

                if ($subscription->pending_billing_cycle) {
                    $planName =
                        $subscription->pending_billing_cycle ===
                        Subscription::BILLING_CYCLE_YEARLY
                        ? 'DocTotal Anual'
                        : 'DocTotal Mensual';

                    $this->dispatch(
                        'swal',
                        title: 'Cambio de plan programado',
                        text: sprintf(
                            'Cambiarás a %s al finalizar el periodo que corresponda.',
                            $planName
                        ),
                        icon: 'success'
                    );

                    return;
                }

                $this->dispatch(
                    'swal',
                    title: 'Cambio cancelado',
                    text: 'Mantendrás tu plan actual.',
                    icon: 'success'
                );
            } catch (\Throwable $exception) {
                report($exception);

                $this->dispatch(
                    'swal',
                    title: 'No fue posible cambiar el plan',
                    text: 'Intenta nuevamente en unos momentos.',
                    icon: 'error'
                );
            }
        }

        public function startManualPayment(): void
        {
            if (! $this->stripeKey) {
                $this->dispatch(
                    'swal',
                    title: 'Stripe no está configurado',
                    text: 'No se encontró la clave pública de Stripe.',
                    icon: 'error'
                );

                return;
            }

            $this->refreshSubscription();

            if ($this->subscription) {
                $this->dispatch(
                    'swal',
                    title: 'Ya tienes una suscripción',
                    text: 'Tu periodo actual ya está pagado.',
                    icon: 'info'
                );

                return;
            }

            /*
             * Todo checkout manual comienza SIN preparar
             * inicialmente la tarjeta para cobros futuros.
             *
             * La decisión real se tomará dentro del formulario,
             * justo antes de confirmPayment().
             */
            $this->manualSaveForFuture =
                false;

            $this->manualRecoveryPayment =
                false;

            try {
                $result =
                    app(
                        CreateManualSubscriptionPaymentIntent::class
                    )->execute(
                        $this->tenant,
                        $this->billingCycle,
                        now(),
                        sprintf(
                            'doctotal:manual:%s:%s',
                            $this->tenant->uuid,
                            Str::uuid()
                        ),
                        false,
                    );

                $this->manualPaymentUuid =
                    $result->payment->uuid;

                $this->manualPaymentFormVisible =
                    true;

                $this->dispatch(
                    'stripe-manual-payment-started',
                    clientSecret: $result->clientSecret,
                    stripeKey: $this->stripeKey,
                );
            } catch (\Throwable $exception) {
                report(
                    $exception
                );

                $this->dispatch(
                    'swal',
                    title: 'No fue posible iniciar el pago',
                    text: 'Intenta nuevamente en unos momentos.',
                    icon: 'error'
                );
            }
        }

        public function startPastDuePayment(): void
        {
            if (! $this->stripeKey) {
                $this->dispatch(
                    'swal',
                    title: 'Stripe no está configurado',
                    text: 'No se encontró la clave pública de Stripe.',
                    icon: 'error'
                );

                return;
            }

            $this->refreshSubscription();
            $this->refreshPaymentMethod();

            if (! $this->subscription?->isPastDue()) {
                $this->dispatch(
                    'swal',
                    title: 'No hay un pago vencido',
                    text: 'La suscripción ya no requiere recuperación manual.',
                    icon: 'info'
                );

                return;
            }

            $this->billingCycle =
                $this->subscription->billing_cycle;

            $this->manualSaveForFuture = false;
            $this->manualRecoveryPayment = true;

            try {
                /*
                 * Si el usuario ya abrió el checkout de recuperación,
                 * reutilizamos ese Payment y su PaymentIntent en lugar
                 * de crear intentos pendientes adicionales.
                 */
                $pendingRecoveryPayment =
                    Payment::withoutGlobalScopes()
                    ->where('tenant_id', $this->tenant->id)
                    ->where('subscription_id', $this->subscription->id)
                    ->where('provider', 'stripe')
                    ->where('status', Payment::STATUS_PENDING)
                    ->where(
                        'idempotency_key',
                        'like',
                        'doctotal:recovery:%'
                    )
                    ->latest('id')
                    ->first();

                $idempotencyKey =
                    $pendingRecoveryPayment?->idempotency_key
                    ?? sprintf(
                        'doctotal:recovery:%s:%s:%s',
                        $this->tenant->uuid,
                        $this->subscription->uuid,
                        Str::uuid()
                    );

                $result = app(
                    CreateManualSubscriptionRecoveryPaymentIntent::class
                )->execute(
                    $this->tenant,
                    $this->subscription,
                    now(),
                    $idempotencyKey,
                    false,
                );

                $this->manualPaymentUuid =
                    $result->payment->uuid;

                $this->manualPaymentFormVisible = true;

                $this->dispatch(
                    'stripe-manual-payment-started',
                    clientSecret: $result->clientSecret,
                    stripeKey: $this->stripeKey,
                    savedPaymentMethodId: $this->paymentMethod
                        ?->provider_payment_method_id,
                    useSavedPaymentMethod: (bool) $this->paymentMethod,
                );
            } catch (\Throwable $exception) {
                report($exception);

                $this->manualRecoveryPayment = false;

                $this->dispatch(
                    'swal',
                    title: 'No fue posible preparar el pago',
                    text: 'Intenta nuevamente en unos momentos.',
                    icon: 'error'
                );
            }
        }

        public function prepareManualPaymentForConfirmation(
            bool $saveForFuture
        ): void {
            if (! $this->manualPaymentUuid) {
                throw new \LogicException(
                    'No existe un pago manual pendiente.'
                );
            }

            $payment =
                Payment::withoutGlobalScopes()
                ->where(
                    'tenant_id',
                    $this->tenant->id
                )
                ->where(
                    'uuid',
                    $this->manualPaymentUuid
                )
                ->firstOrFail();

            app(
                UpdateManualPaymentFutureUsage::class
            )->execute(
                $this->tenant,
                $payment,
                $saveForFuture
            );

            /*
             * Conservamos la decisión en Livewire.
             *
             * Después del cobro confirmManualPayment()
             * decidirá si debe registrar el pm_*.
             */
            $this->manualSaveForFuture =
                $saveForFuture;
        }

        public function confirmManualPayment(): void
        {
            if (! $this->manualPaymentUuid) {
                $this->dispatch(
                    'swal',
                    title: 'Pago no encontrado',
                    text: 'No encontramos el pago que debe confirmarse.',
                    icon: 'error'
                );

                return;
            }

            try {
                $payment =
                    Payment::withoutGlobalScopes()
                    ->where(
                        'tenant_id',
                        $this->tenant->id
                    )
                    ->where(
                        'uuid',
                        $this->manualPaymentUuid
                    )
                    ->firstOrFail();

                /*
                 * Primero terminamos la operación económica.
                 *
                 * Una vez que Stripe cobró, un posible problema
                 * guardando la tarjeta NO debe invalidar
                 * Payment ni Subscription.
                 */
                if ($this->manualRecoveryPayment) {
                    $payment = app(
                        ConfirmManualSubscriptionRecoveryPayment::class
                    )->execute(
                        $this->tenant,
                        $payment,
                        now()
                    );
                } else {
                    $payment = app(
                        ConfirmManualSubscriptionPayment::class
                    )->execute(
                        $this->tenant,
                        $payment,
                        now()
                    );
                }

                $paymentMethodSaved =
                    false;

                $paymentMethodSaveFailed =
                    false;

                /*
                 * Registrar la tarjeta es una consecuencia
                 * posterior al pago exitoso.
                 */
                if ($this->manualSaveForFuture) {
                    try {
                        app(
                            RegisterManualPaymentMethodForFuture::class
                        )->execute(
                            $this->tenant,
                            $payment
                        );

                        $paymentMethodSaved =
                            true;
                    } catch (\Throwable $exception) {
                        report(
                            $exception
                        );

                        $paymentMethodSaveFailed =
                            true;
                    }
                }

                $this->tenant->refresh();

                $this->refreshSubscription();

                $this->refreshPaymentMethod();

                $this->refreshBillingHistory();

                $this->manualPaymentFormVisible =
                    false;

                $this->manualPaymentUuid =
                    null;

                $this->manualSaveForFuture =
                    false;

                $this->manualRecoveryPayment =
                    false;

                $this->dispatch(
                    'stripe-manual-payment-confirmed'
                );

                if ($paymentMethodSaveFailed) {
                    $this->dispatch(
                        'swal',
                        title: 'Pago realizado',
                        text: 'Tu suscripción está activa, pero no pudimos guardar la tarjeta para renovaciones automáticas. Puedes agregarla después desde Facturación.',
                        icon: 'warning'
                    );

                    return;
                }

                if ($paymentMethodSaved) {
                    $this->dispatch(
                        'swal',
                        title: 'Pago realizado',
                        text: 'Tu suscripción está activa y tu tarjeta quedó guardada para futuras renovaciones.',
                        icon: 'success'
                    );

                    return;
                }

                $this->dispatch(
                    'swal',
                    title: 'Pago realizado',
                    text: 'Tu suscripción de DocTotal está activa.',
                    icon: 'success'
                );
            } catch (\Throwable $exception) {
                report(
                    $exception
                );

                $this->dispatch(
                    'swal',
                    title: 'No fue posible confirmar el pago',
                    text: 'El pago no pudo confirmarse. Intenta nuevamente.',
                    icon: 'error'
                );
            }
        }

        private function refreshPaymentMethod(): void
        {
            $this->paymentMethod =
                $this->tenant
                ->defaultPaymentMethod();
        }

        private function refreshSubscription(): void
        {
            $this->subscription =
                $this->tenant
                ->currentSubscription();
        }

        private function refreshBillingHistory(): void
        {
            $this->latestSubscription =
                Subscription::query()
                ->withoutGlobalScope(
                    \App\Models\Scopes\TenantScope::class
                )
                ->where(
                    'tenant_id',
                    $this->tenant->id
                )
                ->latest(
                    'current_period_ends_at'
                )
                ->latest(
                    'id'
                )
                ->first();

            $this->recentPayments =
                Payment::query()
                ->withoutGlobalScope(
                    \App\Models\Scopes\TenantScope::class
                )
                ->where(
                    'tenant_id',
                    $this->tenant->id
                )
                ->latest(
                    'attempted_at'
                )
                ->latest(
                    'id'
                )
                ->limit(8)
                ->get();
        }

        public function scheduleCancellation(): void
        {
            $this->refreshSubscription();

            if (! $this->subscription) {
                return;
            }

            try {
                $this->subscription = app(
                    ScheduleSubscriptionCancellation::class
                )->execute(
                    $this->subscription
                );

                $this->refreshBillingHistory();

                $this->dispatch(
                    'swal',
                    title: 'Cancelación programada',
                    text: sprintf(
                        'Tu suscripción seguirá activa hasta el %s.',
                        $this->subscription
                            ->current_period_ends_at
                            ->format('d/m/Y H:i')
                    ),
                    icon: 'success'
                );
            } catch (\Throwable $exception) {
                report($exception);

                $this->dispatch(
                    'swal',
                    title: 'No fue posible programar la cancelación',
                    text: 'Intenta nuevamente en unos momentos.',
                    icon: 'error'
                );
            }
        }

        public function resumeSubscription(): void
        {
            $this->refreshSubscription();

            if (! $this->subscription) {
                return;
            }

            try {
                $this->subscription = app(
                    ResumeSubscription::class
                )->execute(
                    $this->subscription
                );

                $this->dispatch(
                    'swal',
                    title: 'Suscripción conservada',
                    text: 'Tu suscripción continuará renovándose con tu plan actual.',
                    icon: 'success'
                );
            } catch (\Throwable $exception) {
                report($exception);

                $this->dispatch(
                    'swal',
                    title: 'No fue posible conservar la suscripción',
                    text: 'Intenta nuevamente en unos momentos.',
                    icon: 'error'
                );
            }
        }
    };
?>

<div class="mx-auto max-w-6xl">

    {{-- Header --}}
    <div
        class="relative mb-8 overflow-hidden rounded-2xl
               bg-slate-950 px-6 py-7 text-white
               shadow-sm sm:px-8">

        <div
            class="absolute -right-16 -top-20 h-56 w-56
                   rounded-full bg-white/5">
        </div>

        <div
            class="absolute -bottom-24 right-28 h-48 w-48
                   rounded-full bg-white/5">
        </div>

        <div
            class="relative flex flex-col gap-6
                   sm:flex-row sm:items-center
                   sm:justify-between">

            <div>

                <div class="mb-2 flex items-center gap-2">

                    <span
                        class="inline-flex rounded-full
                               border border-white/10
                               bg-white/10 px-2.5 py-1
                               text-xs font-semibold
                               text-slate-200">
                        DocTotal Billing
                    </span>

                </div>

                <h1
                    class="text-2xl font-bold tracking-tight
                           sm:text-3xl">
                    Facturación
                </h1>

                <p
                    class="mt-2 max-w-2xl text-sm
                           leading-6 text-slate-300">
                    Administra tu suscripción, pagos y método
                    para renovaciones automáticas.
                </p>

            </div>

            <div
                class="shrink-0 rounded-xl
                       border border-white/10
                       bg-white/10 px-4 py-3
                       backdrop-blur">

                <p
                    class="text-xs font-medium uppercase
                           tracking-wider text-slate-400">
                    Estado de cuenta
                </p>

                <div class="mt-1 flex items-center gap-2">

                    @if ($tenant->status === 'active')

                    <span
                        class="h-2 w-2 rounded-full
                                   bg-emerald-400">
                    </span>

                    <span class="text-sm font-semibold">
                        Activo
                    </span>

                    @elseif (
                    $tenant->status === 'trial'
                    && $tenant->isOnTrial()
                    )

                    <span
                        class="h-2 w-2 rounded-full
                                   bg-sky-400">
                    </span>

                    <span class="text-sm font-semibold">
                        Periodo de prueba
                    </span>

                    @elseif (
                    $tenant->status === 'trial'
                    && $tenant->trialHasExpired()
                    )

                    <span
                        class="h-2 w-2 rounded-full
                                   bg-amber-400">
                    </span>

                    <span class="text-sm font-semibold">
                        Prueba vencida
                    </span>

                    @elseif ($tenant->status === 'suspended')

                    <span
                        class="h-2 w-2 rounded-full
                                   bg-red-400">
                    </span>

                    <span class="text-sm font-semibold">
                        Suspendido
                    </span>

                    @else

                    <span
                        class="h-2 w-2 rounded-full
                                   bg-slate-400">
                    </span>

                    <span class="text-sm font-semibold">
                        {{ ucfirst($tenant->status) }}
                    </span>

                    @endif

                </div>

            </div>

        </div>

    </div>


    {{-- Settings navigation --}}
    <div class="mb-7 border-b border-slate-200">

        <nav class="-mb-px flex gap-7">

            <a
                href="{{ route('settings.profile') }}"
                class="border-b-2 border-transparent
                       px-1 pb-3 text-sm font-medium
                       text-slate-500 transition
                       hover:border-slate-300
                       hover:text-slate-900">
                Perfil y consultorio
            </a>

            <a
                href="{{ route('settings.billing') }}"
                class="border-b-2 border-slate-950
                       px-1 pb-3 text-sm font-semibold
                       text-slate-950">
                Facturación
            </a>

        </nav>

    </div>


    {{-- Main billing summary --}}
    <div class="grid gap-5 lg:grid-cols-2">

        {{-- Plan --}}
        <section
            class="relative overflow-hidden rounded-2xl
                   border border-slate-200 bg-white
                   p-6 shadow-sm">

            <div
                class="absolute right-0 top-0 h-24 w-24
                       translate-x-8 -translate-y-8
                       rounded-full bg-slate-100">
            </div>

            <div class="relative">

                @php
                $displayCycle =
                $subscription
                ? $subscription->billing_cycle
                : $billingCycle;

                $displayPlan =
                config(
                'billing.plans.' . $displayCycle
                );

                $displayAmount =
                $subscription
                ? $subscription->billing_amount
                : ($displayPlan['amount'] ?? 0);

                $displayCurrency =
                $subscription
                ? $subscription->billing_currency
                : ($displayPlan['currency'] ?? 'MXN');
                @endphp

                <div
                    class="flex items-start
                           justify-between gap-4">

                    <div>

                        <p
                            class="text-xs font-semibold uppercase
                                   tracking-wider text-slate-400">
                            Tu plan
                        </p>

                        <h2
                            class="mt-2 text-xl font-bold
                                   text-slate-950">
                            @if (
                            $displayCycle ===
                            Subscription::BILLING_CYCLE_YEARLY
                            )
                            DocTotal Anual
                            @else
                            DocTotal Mensual
                            @endif
                        </h2>

                    </div>

                    @if ($subscription?->isPastDue())

                    <span
                        class="rounded-full bg-amber-50
                                   px-2.5 py-1 text-xs
                                   font-semibold text-amber-700">
                        Pago pendiente
                    </span>

                    @elseif ($subscription?->isActive())

                    <span
                        class="rounded-full bg-emerald-50
                                   px-2.5 py-1 text-xs
                                   font-semibold text-emerald-700">
                        Activo
                    </span>

                    @elseif (
                    $tenant->status === 'trial'
                    && $tenant->isOnTrial()
                    )

                    <span
                        class="rounded-full bg-sky-50
                                   px-2.5 py-1 text-xs
                                   font-semibold text-sky-700">
                        Prueba
                    </span>

                    @else

                    <span
                        class="rounded-full bg-slate-100
                                   px-2.5 py-1 text-xs
                                   font-semibold text-slate-600">
                        Sin suscripción
                    </span>

                    @endif

                </div>

                @if (! $subscription)

                <div
                    class="mt-6 grid grid-cols-2 gap-2
                               rounded-xl bg-slate-100 p-1">

                    <button
                        type="button"
                        wire:click="selectBillingCycle('monthly')"
                        class="rounded-lg px-4 py-2.5
                                   text-sm font-semibold transition
                                   {{ $billingCycle === Subscription::BILLING_CYCLE_MONTHLY
                                        ? 'bg-white text-slate-950 shadow-sm'
                                        : 'text-slate-500 hover:text-slate-900' }}">
                        Mensual
                    </button>

                    <button
                        type="button"
                        wire:click="selectBillingCycle('yearly')"
                        class="relative rounded-lg px-4 py-2.5
                                   text-sm font-semibold transition
                                   {{ $billingCycle === Subscription::BILLING_CYCLE_YEARLY
                                        ? 'bg-white text-slate-950 shadow-sm'
                                        : 'text-slate-500 hover:text-slate-900' }}">

                        Anual

                        <span
                            class="absolute -right-1 -top-2
                                       rounded-full bg-emerald-100
                                       px-2 py-0.5 text-[10px]
                                       font-bold text-emerald-700">
                            2 meses gratis
                        </span>

                    </button>

                </div>

                @endif

                <div class="mt-6 flex items-end gap-2">

                    <span
                        class="text-4xl font-bold
                               tracking-tight text-slate-950">
                        ${{
                            number_format(
                                $displayAmount / 100,
                                0
                            )
                        }}
                    </span>

                    <span
                        class="pb-1 text-sm text-slate-500">
                        {{ $displayCurrency }}

                        @if (
                        $displayCycle ===
                        Subscription::BILLING_CYCLE_YEARLY
                        )
                        / año
                        @else
                        / mes
                        @endif
                    </span>

                </div>

                @if (
                ! $subscription
                && $displayCycle ===
                Subscription::BILLING_CYCLE_YEARLY
                )

                <div
                    class="mt-4 rounded-xl
                               bg-emerald-50 p-3">

                    <p
                        class="text-sm font-semibold
                                   text-emerald-800">
                        Ahorras $1,200 MXN al año
                    </p>

                    <p
                        class="mt-1 text-xs leading-5
                                   text-emerald-700">
                        Equivale a $500 MXN por mes.
                        Recibes 12 meses pagando el equivalente
                        a 10 mensualidades.
                    </p>

                </div>

                @elseif (
                $displayCycle ===
                Subscription::BILLING_CYCLE_YEARLY
                )

                <p
                    class="mt-4 text-sm leading-6
                               text-slate-500">
                    Facturación anual de $6,000 MXN.
                    Este plan cubre 12 meses de DocTotal.
                </p>

                @else

                <p
                    class="mt-4 text-sm leading-6
                               text-slate-500">
                    Facturación mensual de $600 MXN.
                    Este plan se renueva cada mes.
                </p>

                @endif

                @if (! $subscription)

                <div class="mt-6">

                    <button
                        type="button"
                        wire:click="startManualPayment"
                        wire:loading.attr="disabled"
                        wire:target="startManualPayment"
                        class="inline-flex items-center
                                   justify-center rounded-lg
                                   bg-slate-950 px-4 py-2.5
                                   text-sm font-semibold text-white
                                   shadow-sm transition
                                   hover:bg-slate-800
                                   disabled:cursor-not-allowed
                                   disabled:opacity-50">

                        <span
                            wire:loading.remove
                            wire:target="startManualPayment">
                            Pagar ahora
                        </span>

                        <span
                            wire:loading
                            wire:target="startManualPayment">
                            Preparando pago...
                        </span>

                    </button>

                </div>

                @elseif ($subscription->isPastDue())

                <div class="mt-6 space-y-3">

                    <div
                        class="rounded-xl border border-amber-200
                               bg-amber-50 p-4">
                        <p class="text-sm font-semibold text-amber-900">
                            Este periodo tiene un pago pendiente
                        </p>
                        <p class="mt-1 text-sm leading-6 text-amber-700">
                            Regulariza ${{ number_format($subscription->billing_amount / 100, 2) }}
                            {{ $subscription->billing_currency }} para recuperar tu suscripción.
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="startPastDuePayment"
                        wire:loading.attr="disabled"
                        wire:target="startPastDuePayment"
                        class="inline-flex items-center justify-center rounded-lg
                               bg-slate-950 px-4 py-2.5 text-sm font-semibold
                               text-white shadow-sm transition hover:bg-slate-800
                               disabled:cursor-not-allowed disabled:opacity-50">
                        <span wire:loading.remove wire:target="startPastDuePayment">
                            Pagar ahora
                        </span>
                        <span wire:loading wire:target="startPastDuePayment">
                            Preparando pago...
                        </span>
                    </button>

                </div>

                @else

                <div
                    class="mt-6 inline-flex items-center gap-2
                               rounded-lg bg-emerald-50
                               px-3 py-2 text-sm
                               font-medium text-emerald-700">

                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2">

                        <path d="m5 12 4 4L19 6" />

                    </svg>

                    Tu periodo actual ya está pagado

                </div>

                @endif

                @if ($subscription)

                @if (
                $subscription->isActive()
                && $subscription->cancel_at_period_end
                )

                <div
                    class="mt-6 rounded-xl border border-amber-200
                           bg-amber-50 p-4">

                    <p
                        class="text-sm font-semibold
                               text-amber-900">
                        Cancelación programada
                    </p>

                    <p
                        class="mt-1 text-sm leading-6
                               text-amber-700">
                        Tu suscripción seguirá activa hasta el

                        <strong>
                            {{
                                $subscription
                                    ->current_period_ends_at
                                    ->format('d/m/Y H:i')
                            }}
                        </strong>.

                        Después de esa fecha no se realizará
                        otra renovación.
                    </p>

                    <button
                        type="button"
                        wire:click="resumeSubscription"
                        wire:loading.attr="disabled"
                        wire:target="resumeSubscription"
                        class="mt-4 inline-flex items-center
                               justify-center rounded-lg
                               border border-amber-300 bg-white
                               px-4 py-2.5 text-sm font-semibold
                               text-amber-800 shadow-sm transition
                               hover:bg-amber-100
                               disabled:cursor-not-allowed
                               disabled:opacity-50">

                        <span
                            wire:loading.remove
                            wire:target="resumeSubscription">
                            Conservar mi suscripción
                        </span>

                        <span
                            wire:loading
                            wire:target="resumeSubscription">
                            Procesando...
                        </span>

                    </button>

                </div>

                @else

                @php
                $otherCycle =
                $subscription->isYearly()
                ? Subscription::BILLING_CYCLE_MONTHLY
                : Subscription::BILLING_CYCLE_YEARLY;

                $otherPlan =
                config(
                'billing.plans.' . $otherCycle
                );
                @endphp

                <div
                    class="mt-6 border-t border-slate-100 pt-5">

                    <p
                        class="text-sm font-semibold text-slate-900">
                        Cambiar de plan
                    </p>

                    @if ($subscription->pending_billing_cycle)

                    <div
                        class="mt-3 rounded-xl border border-sky-200
                               bg-sky-50 p-4">

                        <p
                            class="text-sm font-semibold
                                   text-sky-900">
                            Cambio programado
                        </p>

                        <p
                            class="mt-1 text-sm leading-6
                                   text-sky-700">
                            Cambiarás a

                            <strong>
                                {{
                                    $subscription
                                        ->pending_billing_cycle ===
                                    Subscription::BILLING_CYCLE_YEARLY
                                        ? 'DocTotal Anual'
                                        : 'DocTotal Mensual'
                                }}
                            </strong>

                            al finalizar el periodo que corresponda.
                        </p>

                    </div>

                    <button
                        type="button"
                        wire:click="scheduleBillingCycleChange('{{ $subscription->billing_cycle }}')"
                        wire:loading.attr="disabled"
                        wire:target="scheduleBillingCycleChange"
                        class="mt-3 inline-flex items-center
                               justify-center rounded-lg
                               border border-slate-300 bg-white
                               px-4 py-2.5 text-sm font-semibold
                               text-slate-700 shadow-sm transition
                               hover:bg-slate-50
                               disabled:cursor-not-allowed
                               disabled:opacity-50">
                        Mantener mi plan actual
                    </button>

                    @else

                    <p
                        class="mt-1 text-sm leading-6
                               text-slate-500">
                        @if ($subscription->isPastDue())

                        Puedes programar el siguiente plan ahora.
                        El pago pendiente actual conserva su importe y,
                        después de regularizarlo, el cambio se aplicará
                        cuando termine el periodo que quede cubierto.

                        @else

                        El cambio se aplicará al finalizar
                        tu periodo actual.

                        @endif
                    </p>

                    <button
                        type="button"
                        wire:click="scheduleBillingCycleChange('{{ $otherCycle }}')"
                        wire:loading.attr="disabled"
                        wire:target="scheduleBillingCycleChange"
                        class="mt-3 inline-flex items-center
                               justify-center rounded-lg
                               border border-slate-300 bg-white
                               px-4 py-2.5 text-sm font-semibold
                               text-slate-700 shadow-sm transition
                               hover:bg-slate-50
                               disabled:cursor-not-allowed
                               disabled:opacity-50">

                        @if (
                        $otherCycle ===
                        Subscription::BILLING_CYCLE_YEARLY
                        )

                        Cambiar a Anual ·
                        ${{
                            number_format(
                                ($otherPlan['amount'] ?? 0) / 100,
                                0
                            )
                        }}/año

                        @else

                        Cambiar a Mensual ·
                        ${{
                            number_format(
                                ($otherPlan['amount'] ?? 0) / 100,
                                0
                            )
                        }}/mes

                        @endif

                    </button>

                    @endif

                </div>

                @if ($subscription->isActive())

                <div
                    class="mt-6 border-t border-slate-100 pt-5">

                    <p
                        class="text-sm font-semibold text-slate-900">
                        Cancelar suscripción
                    </p>

                    <p
                        class="mt-1 text-sm leading-6
                               text-slate-500">
                        Puedes cancelar la renovación de tu
                        suscripción. Seguirás teniendo acceso hasta
                        que termine tu periodo actual.
                    </p>

                    <button
                        type="button"
                        id="schedule-subscription-cancellation"
                        class="mt-3 inline-flex items-center
                               justify-center rounded-lg
                               border border-red-200 bg-white
                               px-4 py-2.5 text-sm font-semibold
                               text-red-600 shadow-sm transition
                               hover:border-red-300 hover:bg-red-50
                               disabled:cursor-not-allowed
                               disabled:opacity-50">
                        Cancelar suscripción
                    </button>

                </div>

                @endif

                @endif

                @endif

            </div>

        </section>


        {{-- Subscription details --}}
        <section
            class="rounded-2xl border border-slate-200
                   bg-white p-6 shadow-sm">

            <p
                class="text-xs font-semibold uppercase
                       tracking-wider text-slate-400">
                Estado de la suscripción
            </p>


            @if (
            $tenant->status === 'trial'
            && ! $subscription
            )

            <div class="mt-5">

                <div
                    class="rounded-xl border border-sky-100
                               bg-sky-50 p-4">

                    <p
                        class="text-sm font-semibold
                                   text-sky-900">

                        @if ($tenant->isOnTrial())
                        Periodo de prueba
                        @else
                        Periodo de prueba vencido
                        @endif

                    </p>

                    <p
                        class="mt-2 text-sm
                                   leading-6 text-sky-700">

                        @if ($tenant->trial_ends_at)

                        Tu periodo de prueba
                        {{ $tenant->isOnTrial()
                                    ? 'termina'
                                    : 'terminó' }}
                        el

                        <strong>
                            {{ $tenant
                                        ->trial_ends_at
                                        ->format('d/m/Y') }}
                            a las
                            {{ $tenant
                                        ->trial_ends_at
                                        ->format('H:i') }}
                        </strong>.

                        @else

                        No hay una fecha de terminación
                        configurada para el periodo de prueba.

                        @endif

                    </p>

                </div>

                <div
                    class="mt-5 flex items-center
                               justify-between border-t
                               border-slate-100 pt-5">

                    <div>

                        <p class="text-sm text-slate-500">
                            Planes disponibles
                        </p>

                        <p
                            class="mt-1 font-semibold
                                       text-slate-950">
                            $600/mes o $6,000/año
                        </p>

                    </div>

                    <div class="text-right">

                        <p class="text-sm text-slate-500">
                            Forma de pago
                        </p>

                        <p
                            class="mt-1 font-semibold
                                       text-slate-950">
                            Manual o automática
                        </p>

                    </div>

                </div>

            </div>

            @elseif ($subscription)

            <div class="mt-5 space-y-5">

                <div
                    class="flex items-center
                               justify-between border-b
                               border-slate-100 pb-5">

                    <div>

                        <p class="text-sm text-slate-500">
                            Estado
                        </p>

                        <p
                            class="mt-1 font-semibold
                                       text-slate-950">

                            @if ($subscription->isPastDue())
                            Pago pendiente
                            @elseif ($subscription->isActive())
                            Activa
                            @elseif ($subscription->isCancelled())
                            Cancelada
                            @else
                            {{ ucfirst($subscription->status) }}
                            @endif

                        </p>

                    </div>

                    <div class="text-right">

                        <p class="text-sm text-slate-500">
                            Importe
                        </p>

                        <p
                            class="mt-1 font-semibold
                                       text-slate-950">

                            ${{
                                    number_format(
                                        $subscription
                                            ->billing_amount / 100,
                                        2
                                    )
                                }}
                            {{ $subscription->billing_currency }}

                        </p>

                    </div>

                </div>


                <div
                    class="grid gap-5
                           sm:grid-cols-2">

                    <div>

                        <p class="text-sm text-slate-500">
                            Ciclo de facturación
                        </p>

                        <p
                            class="mt-1 font-semibold
                                   text-slate-950">
                            @if ($subscription->isYearly())
                            Anual
                            @else
                            Mensual
                            @endif
                        </p>

                    </div>

                    <div>

                        <p class="text-sm text-slate-500">
                            Precio contratado
                        </p>

                        <p
                            class="mt-1 font-semibold
                                   text-slate-950">
                            ${{
                                number_format(
                                    $subscription->billing_amount / 100,
                                    2
                                )
                            }}
                            {{ $subscription->billing_currency }}
                        </p>

                    </div>

                </div>


                <div
                    class="grid gap-5
                               sm:grid-cols-2">

                    <div>

                        <p class="text-sm text-slate-500">
                            Inicio del periodo actual
                        </p>

                        <p
                            class="mt-1 font-semibold
                                       text-slate-950">
                            {{
                                    $subscription
                                        ->current_period_starts_at
                                        ->format(
                                            'd/m/Y H:i'
                                        )
                                }}
                        </p>

                    </div>

                    <div>

                        <p class="text-sm text-slate-500">
                            Fin del periodo actual
                        </p>

                        <p
                            class="mt-1 font-semibold
                                       text-slate-950">
                            {{
                                    $subscription
                                        ->current_period_ends_at
                                        ->format(
                                            'd/m/Y H:i'
                                        )
                                }}
                        </p>

                    </div>

                </div>


                <div
                    class="rounded-xl
                               bg-slate-50 p-4">

                    <div class="flex items-start gap-3">

                        <div
                            class="mt-0.5 flex h-9 w-9
                                       shrink-0 items-center
                                       justify-center rounded-full
                                       bg-white shadow-sm
                                       ring-1 ring-slate-200">

                            <svg
                                class="h-4 w-4
                                           text-slate-700"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2">

                                <path d="M12 6v6l4 2" />

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="9" />

                            </svg>

                        </div>

                        <div>

                            <p
                                class="text-sm font-medium
                                           text-slate-900">
                                @if ($subscription->isPastDue())
                                Próximo intento automático
                                @elseif ($subscription->cancel_at_period_end)
                                Acceso hasta
                                @else
                                Próxima renovación
                                @endif
                            </p>

                            <p
                                class="mt-1 text-sm
                                           text-slate-600">

                                @if ($subscription->isPastDue())

                                @if ($subscription->next_retry_at)
                                {{ $subscription->next_retry_at->format('d/m/Y H:i') }}
                                @else
                                No programado
                                @endif

                                @elseif ($subscription->next_billing_at)

                                {{
                                            $subscription
                                                ->next_billing_at
                                                ->format(
                                                    'd/m/Y H:i'
                                                )
                                        }}

                                @else

                                No programada

                                @endif

                            </p>

                            <p
                                class="mt-1 text-xs
                                           leading-5 text-slate-400">
                                @if ($subscription->isPastDue())
                                Puedes pagar ahora sin esperar al reintento.
                                @elseif ($subscription->cancel_at_period_end)
                                Tu suscripción seguirá activa hasta esta fecha.
                                Después no se realizará otra renovación.
                                @else
                                El cobro será automático
                                solamente si tienes un método
                                de renovación configurado.
                                @endif
                            </p>

                        </div>

                    </div>

                </div>


                @if ($subscription->isPastDue())

                <div
                    class="rounded-xl border
                                   border-amber-200
                                   bg-amber-50 p-4">

                    <p
                        class="text-sm font-semibold
                                       text-amber-900">
                        Pago pendiente
                    </p>

                    @if ($subscription->grace_ends_at)

                    <p
                        class="mt-1 text-sm
                                           leading-6 text-amber-700">
                        Tu periodo de gracia termina el

                        <strong>
                            {{
                                            $subscription
                                                ->grace_ends_at
                                                ->format(
                                                    'd/m/Y H:i'
                                                )
                                        }}
                        </strong>.
                    </p>

                    @endif

                    @if ($subscription->next_retry_at)

                    <p
                        class="mt-1 text-xs
                                           text-amber-600">
                        Próximo intento:

                        {{
                                        $subscription
                                            ->next_retry_at
                                            ->format(
                                                'd/m/Y H:i'
                                            )
                                    }}
                    </p>

                    @endif

                </div>

                @endif

            </div>

            @else

            @if ($latestSubscription)

            <div class="mt-5 space-y-5">

                @if ($tenant->status === 'suspended')

                <div
                    class="rounded-xl border border-red-200
                           bg-red-50 p-4">

                    <p
                        class="text-sm font-semibold
                               text-red-900">
                        Cuenta suspendida
                    </p>

                    <p
                        class="mt-1 text-sm leading-6
                               text-red-700">
                        El acceso al consultorio se encuentra
                        suspendido. Revisa el estado de tu
                        facturación para regularizar la cuenta.
                    </p>

                    @if ($tenant->suspended_at)

                    <p
                        class="mt-1 text-xs text-red-600">
                        Suspendida desde
                        {{
                            $tenant->suspended_at
                                ->format('d/m/Y H:i')
                        }}.
                    </p>

                    @endif

                </div>

                @endif

                <div
                    class="rounded-xl border border-slate-200
                           bg-slate-50 p-5">

                    <div
                        class="flex flex-col gap-4
                               sm:flex-row sm:items-start
                               sm:justify-between">

                        <div>

                            <p
                                class="text-xs font-semibold uppercase
                                       tracking-wider text-slate-400">
                                Última suscripción
                            </p>

                            <p
                                class="mt-2 text-lg font-bold
                                       text-slate-950">
                                @if ($latestSubscription->isYearly())
                                DocTotal Anual
                                @else
                                DocTotal Mensual
                                @endif
                            </p>

                            <p
                                class="mt-1 text-sm text-slate-500">

                                @if ($latestSubscription->isCancelled())
                                Suscripción cancelada
                                @elseif ($latestSubscription->isPastDue())
                                Pago vencido
                                @elseif (
                                $latestSubscription->isActive()
                                && $latestSubscription->current_period_ends_at
                                && $latestSubscription->current_period_ends_at->isPast()
                                )
                                Periodo finalizado
                                @elseif ($latestSubscription->isActive())
                                Sin periodo vigente
                                @else
                                {{ ucfirst($latestSubscription->status) }}
                                @endif

                            </p>

                        </div>

                        <span
                            class="inline-flex w-fit rounded-full
                                   bg-slate-200 px-2.5 py-1
                                   text-xs font-semibold
                                   text-slate-700">

                            @if ($latestSubscription->isCancelled())
                            Cancelada
                            @elseif ($latestSubscription->isPastDue())
                            Vencida
                            @else
                            Histórica
                            @endif

                        </span>

                    </div>

                    <div
                        class="mt-5 grid gap-4 border-t
                               border-slate-200 pt-5
                               sm:grid-cols-2">

                        <div>

                            <p class="text-sm text-slate-500">
                                Precio contratado
                            </p>

                            <p
                                class="mt-1 font-semibold
                                       text-slate-950">
                                ${{
                                    number_format(
                                        $latestSubscription
                                            ->billing_amount / 100,
                                        2
                                    )
                                }}
                                {{
                                    $latestSubscription
                                        ->billing_currency
                                }}
                            </p>

                        </div>

                        <div>

                            <p class="text-sm text-slate-500">
                                Último periodo
                            </p>

                            <p
                                class="mt-1 font-semibold
                                       text-slate-950">
                                {{
                                    $latestSubscription
                                        ->current_period_starts_at
                                        ?->format('d/m/Y')
                                }}
                                —
                                {{
                                    $latestSubscription
                                        ->current_period_ends_at
                                        ?->format('d/m/Y')
                                }}
                            </p>

                        </div>

                    </div>

                    @if ($latestSubscription->cancelled_at)

                    <p
                        class="mt-4 text-xs leading-5
                               text-slate-500">
                        Cancelada el
                        {{
                            $latestSubscription
                                ->cancelled_at
                                ->format('d/m/Y H:i')
                        }}.
                    </p>

                    @endif

                    <p
                        class="mt-4 text-sm leading-6
                               text-slate-600">
                        No tienes una suscripción vigente en
                        este momento. Puedes elegir un plan y
                        volver a contratar DocTotal.
                    </p>

                </div>

            </div>

            @else

            <div
                class="mt-5 rounded-xl
                       bg-slate-50 p-5">

                <p
                    class="font-medium
                           text-slate-900">
                    Sin suscripción activa
                </p>

                <p
                    class="mt-1 text-sm leading-6
                           text-slate-500">
                    No encontramos suscripciones anteriores.
                    Realiza tu primer pago para comenzar tu
                    suscripción de DocTotal.
                </p>

            </div>

            @endif

            @endif

        </section>

    </div>


    {{-- Billing history --}}
    @if ($recentPayments?->isNotEmpty())

    <section
        class="mt-6 overflow-hidden rounded-2xl
               border border-slate-200
               bg-white shadow-sm">

        <div
            class="border-b border-slate-100
                   px-6 py-5">

            <h2
                class="font-semibold
                       text-slate-950">
                Historial de facturación
            </h2>

            <p
                class="mt-1 text-sm
                       text-slate-500">
                Tus pagos más recientes de DocTotal.
            </p>

        </div>

        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-slate-200">

                <thead class="bg-slate-50">

                    <tr>

                        <th
                            class="px-6 py-3 text-left
                                   text-xs font-semibold uppercase
                                   tracking-wider text-slate-500">
                            Fecha
                        </th>

                        <th
                            class="px-6 py-3 text-left
                                   text-xs font-semibold uppercase
                                   tracking-wider text-slate-500">
                            Concepto
                        </th>

                        <th
                            class="px-6 py-3 text-left
                                   text-xs font-semibold uppercase
                                   tracking-wider text-slate-500">
                            Importe
                        </th>

                        <th
                            class="px-6 py-3 text-left
                                   text-xs font-semibold uppercase
                                   tracking-wider text-slate-500">
                            Estado
                        </th>

                    </tr>

                </thead>

                <tbody
                    class="divide-y divide-slate-100
                           bg-white">

                    @foreach ($recentPayments as $payment)

                    <tr>

                        <td
                            class="whitespace-nowrap
                                   px-6 py-4 text-sm
                                   text-slate-600">
                            {{
                                $payment->attempted_at
                                    ?->format('d/m/Y H:i')
                                ?? '—'
                            }}
                        </td>

                        <td
                            class="px-6 py-4 text-sm
                                   font-medium text-slate-900">

                            @if (
                            $payment->billing_cycle ===
                            Subscription::BILLING_CYCLE_YEARLY
                            || (
                            ! $payment->billing_cycle
                            && $payment->subscription?->isYearly()
                            )
                            )
                            DocTotal Anual
                            @elseif (
                            $payment->billing_cycle ===
                            Subscription::BILLING_CYCLE_MONTHLY
                            || (
                            ! $payment->billing_cycle
                            && $payment->subscription?->isMonthly()
                            )
                            )
                            DocTotal Mensual
                            @else
                            Pago de DocTotal
                            @endif

                        </td>

                        <td
                            class="whitespace-nowrap
                                   px-6 py-4 text-sm
                                   text-slate-700">
                            ${{
                                number_format(
                                    $payment->amount / 100,
                                    2
                                )
                            }}
                            {{ $payment->currency }}
                        </td>

                        <td
                            class="whitespace-nowrap
                                   px-6 py-4">

                            @if ($payment->status === Payment::STATUS_SUCCEEDED)

                            <span
                                class="inline-flex rounded-full
                                       bg-emerald-50 px-2.5 py-1
                                       text-xs font-semibold
                                       text-emerald-700">
                                Pagado
                            </span>

                            @elseif ($payment->status === Payment::STATUS_FAILED)

                            <span
                                class="inline-flex rounded-full
                                       bg-red-50 px-2.5 py-1
                                       text-xs font-semibold
                                       text-red-700">
                                Fallido
                            </span>

                            @else

                            <span
                                class="inline-flex rounded-full
                                       bg-amber-50 px-2.5 py-1
                                       text-xs font-semibold
                                       text-amber-700">
                                Pendiente
                            </span>

                            @endif

                        </td>

                    </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </section>

    @endif


    {{-- Manual payment --}}
    @if ($manualPaymentFormVisible)

    @php
    $checkoutPlan =
    config(
    'billing.plans.' . $billingCycle
    );

    $checkoutAmount =
    $manualRecoveryPayment && $subscription
    ? $subscription->billing_amount
    : ($checkoutPlan['amount'] ?? 0);

    $checkoutCurrency =
    $manualRecoveryPayment && $subscription
    ? $subscription->billing_currency
    : ($checkoutPlan['currency'] ?? 'MXN');
    @endphp

    <section
        id="manual-payment-section"
        tabindex="-1"
        class="mt-6 scroll-mt-6 overflow-hidden rounded-2xl
                   border border-slate-200
                   bg-white shadow-sm outline-none">

        <div
            class="border-b border-slate-100
                       bg-slate-50 px-6 py-5">

            <div class="flex items-start gap-3">

                <div
                    class="flex h-9 w-9 shrink-0
                               items-center justify-center
                               rounded-full bg-white
                               ring-1 ring-slate-200">

                    <svg
                        class="h-4 w-4
                                   text-slate-600"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2">

                        <rect
                            x="3"
                            y="5"
                            width="18"
                            height="14"
                            rx="2" />

                        <path d="M3 10h18" />

                    </svg>

                </div>

                <div>

                    <h2
                        class="font-semibold
                                   text-slate-950">
                        @if ($manualRecoveryPayment)
                        Regularizar suscripción
                        @elseif (
                        $billingCycle ===
                        Subscription::BILLING_CYCLE_YEARLY
                        )
                        Pagar DocTotal Anual
                        @else
                        Pagar DocTotal Mensual
                        @endif
                    </h2>

                    <p
                        class="mt-1 text-sm
                                   text-slate-500">
                        Realiza un pago de

                        <strong class="text-slate-900">
                            ${{
                                number_format(
                                    $checkoutAmount / 100,
                                    2
                                )
                            }}
                            {{ $checkoutCurrency }}
                        </strong>.

                        @if ($manualRecoveryPayment)
                        Este pago regulariza el periodo vencido y
                        reactiva la suscripción conservando su ciclo actual.
                        @elseif (
                        $billingCycle ===
                        Subscription::BILLING_CYCLE_YEARLY
                        )
                        Este pago cubre 12 meses de DocTotal.
                        @else
                        Este pago cubre un mes de DocTotal.
                        @endif

                        Antes de pagar puedes decidir si
                        quieres conservar esta tarjeta para
                        futuras renovaciones automáticas.
                    </p>

                </div>

            </div>

        </div>


        <div class="p-6">

            @if ($manualRecoveryPayment && $paymentMethod)

            <div
                id="saved-recovery-payment-method"
                class="rounded-xl border border-slate-200
                       bg-slate-50 p-5">

                <p
                    class="text-xs font-semibold uppercase
                           tracking-wider text-slate-400">
                    Tarjeta guardada
                </p>

                <div
                    class="mt-3 flex flex-col gap-4
                           sm:flex-row sm:items-center
                           sm:justify-between">

                    <div class="flex items-center gap-4">

                        <div
                            class="flex h-11 w-14 shrink-0 items-center
                                   justify-center rounded-lg bg-slate-950
                                   text-[10px] font-bold uppercase
                                   tracking-wide text-white shadow-sm">
                            {{ strtoupper($paymentMethod->brand ?? 'CARD') }}
                        </div>

                        <div>
                            <p class="font-semibold text-slate-950">
                                {{ ucfirst($paymentMethod->brand ?? 'Tarjeta') }}
                                •••• {{ $paymentMethod->last_four }}
                            </p>
                            <p class="mt-1 text-sm text-slate-500">
                                Vence
                                {{ str_pad((string) $paymentMethod->expires_month, 2, '0', STR_PAD_LEFT) }}/{{ $paymentMethod->expires_year }}
                            </p>
                        </div>

                    </div>

                    <button
                        type="button"
                        id="stripe-confirm-saved-recovery-payment"
                        data-payment-method-id="{{ $paymentMethod->provider_payment_method_id }}"
                        class="inline-flex shrink-0 items-center justify-center
                               rounded-lg bg-slate-950 px-5 py-2.5
                               text-sm font-semibold text-white shadow-sm
                               transition hover:bg-slate-800
                               disabled:cursor-not-allowed
                               disabled:opacity-50">
                        Pagar ${{ number_format($checkoutAmount / 100, 0) }}
                        {{ $checkoutCurrency }} con
                        •••• {{ $paymentMethod->last_four }}
                    </button>

                </div>

            </div>

            <div class="my-5 flex items-center gap-3">
                <div class="h-px flex-1 bg-slate-200"></div>
                <span class="text-xs font-medium uppercase tracking-wider text-slate-400">
                    o
                </span>
                <div class="h-px flex-1 bg-slate-200"></div>
            </div>

            <button
                type="button"
                id="stripe-use-another-recovery-card"
                class="inline-flex items-center justify-center rounded-lg
                       border border-slate-300 bg-white px-4 py-2.5
                       text-sm font-semibold text-slate-700 shadow-sm
                       transition hover:bg-slate-50">
                Usar otra tarjeta
            </button>

            <div
                id="stripe-manual-alternative-card-wrapper"
                class="mt-5 hidden">

                <div
                    class="rounded-xl border border-slate-200
                           bg-white p-4">
                    <div wire:ignore>
                        <div id="stripe-manual-payment-element"></div>
                    </div>
                </div>

                <label
                    class="mt-5 flex cursor-pointer items-start gap-3
                           rounded-xl border border-slate-200 bg-slate-50
                           p-4 transition hover:border-slate-300">

                    <div class="pt-0.5">
                        <input
                            type="checkbox"
                            id="manual-save-for-future"
                            class="h-4 w-4 rounded border-slate-300
                                   text-slate-950 focus:ring-slate-500">
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-slate-900">
                            Guardar esta tarjeta para renovaciones automáticas
                        </p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Si activas esta opción, esta tarjeta reemplazará
                            el método predeterminado para futuras renovaciones.
                        </p>
                    </div>

                </label>

                <div class="mt-6 flex justify-end">
                    <button
                        type="button"
                        id="stripe-confirm-manual-payment"
                        class="inline-flex items-center justify-center rounded-lg
                               bg-slate-950 px-5 py-2.5 text-sm font-semibold
                               text-white shadow-sm transition hover:bg-slate-800
                               disabled:cursor-not-allowed disabled:opacity-50">
                        Pagar ${{ number_format($checkoutAmount / 100, 0) }}
                        {{ $checkoutCurrency }}
                    </button>
                </div>

            </div>

            @else

            <div
                class="rounded-xl border border-slate-200
                       bg-white p-4">

                <div wire:ignore>
                    <div id="stripe-manual-payment-element"></div>
                </div>

            </div>

            <label
                class="mt-5 flex cursor-pointer items-start gap-3 rounded-xl
                       border border-slate-200 bg-slate-50 p-4 transition
                       hover:border-slate-300">

                <div class="pt-0.5">
                    <input
                        type="checkbox"
                        id="manual-save-for-future"
                        class="h-4 w-4 rounded border-slate-300
                               text-slate-950 focus:ring-slate-500">
                </div>

                <div>
                    <p class="text-sm font-semibold text-slate-900">
                        Guardar esta tarjeta para renovaciones automáticas
                    </p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Si activas esta opción, esta misma tarjeta quedará
                        disponible en Stripe para tus siguientes renovaciones
                        de DocTotal.
                    </p>
                </div>

            </label>

            <div
                class="mt-6 flex flex-col-reverse gap-3
                       sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <p class="text-xs leading-5 text-slate-400">
                        Pago seguro procesado directamente por Stripe.
                    </p>
                    <p class="mt-1 text-xs leading-5 text-slate-400">
                        La tarjeta sólo quedará registrada para renovaciones
                        si activas la opción anterior.
                    </p>
                </div>

                <button
                    type="button"
                    id="stripe-confirm-manual-payment"
                    class="inline-flex items-center justify-center rounded-lg
                           bg-slate-950 px-5 py-2.5 text-sm font-semibold
                           text-white shadow-sm transition hover:bg-slate-800
                           disabled:cursor-not-allowed disabled:opacity-50">
                    Pagar ${{ number_format($checkoutAmount / 100, 0) }}
                    {{ $checkoutCurrency }}
                </button>

            </div>

            @endif

            <div
                id="stripe-manual-payment-error"
                class="mt-3 hidden rounded-lg bg-red-50 px-4 py-3
                       text-sm text-red-700">
            </div>

        </div>

    </section>

    @endif


    {{-- Automatic payment method --}}
    <section
        class="mt-6 overflow-hidden rounded-2xl
               border border-slate-200
               bg-white shadow-sm">

        <div
            class="flex flex-col gap-4
                   border-b border-slate-100
                   px-6 py-5 sm:flex-row
                   sm:items-center
                   sm:justify-between">

            <div>

                <h2
                    class="font-semibold
                           text-slate-950">
                    Método para renovación automática
                </h2>

                <p
                    class="mt-1 text-sm
                           text-slate-500">
                    Puedes cambiarlo o eliminarlo cuando quieras.
                </p>

            </div>

            @if ($paymentMethod)

            <span
                class="inline-flex w-fit
                           items-center gap-1.5
                           rounded-full bg-emerald-50
                           px-3 py-1.5 text-xs
                           font-semibold text-emerald-700">

                <span
                    class="h-1.5 w-1.5
                               rounded-full bg-emerald-500">
                </span>

                Predeterminada

            </span>

            @endif

        </div>


        <div class="p-6">

            @if ($paymentMethod)

            <div
                class="flex flex-col gap-5
                           sm:flex-row sm:items-center
                           sm:justify-between">

                <div class="flex items-center gap-4">

                    <div
                        class="flex h-12 w-16 shrink-0
                                   items-center justify-center
                                   rounded-lg bg-slate-950
                                   text-xs font-bold uppercase
                                   tracking-wide text-white
                                   shadow-sm">

                        {{
                                strtoupper(
                                    $paymentMethod->brand
                                    ?? 'CARD'
                                )
                            }}

                    </div>

                    <div>

                        <p
                            class="text-base font-semibold
                                       text-slate-950">

                            {{
                                    ucfirst(
                                        $paymentMethod->brand
                                        ?? 'Tarjeta'
                                    )
                                }}

                            ••••
                            {{ $paymentMethod->last_four }}

                        </p>

                        <p
                            class="mt-1 text-sm
                                       text-slate-500">
                            Vence

                            {{
                                    str_pad(
                                        (string)
                                        $paymentMethod
                                            ->expires_month,
                                        2,
                                        '0',
                                        STR_PAD_LEFT
                                    )
                                }}/{{ $paymentMethod->expires_year }}

                        </p>

                    </div>

                </div>


                <div
                    class="flex flex-col gap-2
                               sm:flex-row sm:items-center">

                    <button
                        type="button"
                        wire:click="startCardSetup"
                        wire:loading.attr="disabled"
                        wire:target="startCardSetup,removePaymentMethod"
                        class="inline-flex items-center
                                   justify-center rounded-lg
                                   border border-slate-300
                                   bg-white px-4 py-2.5
                                   text-sm font-semibold
                                   text-slate-700 shadow-sm
                                   transition hover:bg-slate-50
                                   disabled:cursor-not-allowed
                                   disabled:opacity-50">

                        <span
                            wire:loading.remove
                            wire:target="startCardSetup">
                            Cambiar tarjeta
                        </span>

                        <span
                            wire:loading
                            wire:target="startCardSetup">
                            Preparando...
                        </span>

                    </button>


                    <button
                        type="button"
                        id="remove-payment-method"
                        wire:loading.attr="disabled"
                        wire:target="startCardSetup,removePaymentMethod"
                        class="inline-flex items-center
                                   justify-center gap-2 rounded-lg
                                   border border-red-200
                                   bg-white px-4 py-2.5
                                   text-sm font-semibold
                                   text-red-600 shadow-sm
                                   transition
                                   hover:border-red-300
                                   hover:bg-red-50
                                   hover:text-red-700
                                   disabled:cursor-not-allowed
                                   disabled:opacity-50">

                        <svg
                            wire:loading.remove
                            wire:target="removePaymentMethod"
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2">

                            <path d="M3 6h18" />
                            <path d="M8 6V4h8v2" />
                            <path d="M19 6l-1 14H6L5 6" />
                            <path d="M10 11v5" />
                            <path d="M14 11v5" />

                        </svg>

                        <span
                            wire:loading.remove
                            wire:target="removePaymentMethod">
                            Eliminar tarjeta
                        </span>

                        <span
                            wire:loading
                            wire:target="removePaymentMethod">
                            Eliminando...
                        </span>

                    </button>

                </div>

            </div>

            @else

            <div
                class="flex flex-col gap-5
                           rounded-xl border
                           border-dashed border-slate-300
                           bg-slate-50 p-5
                           sm:flex-row sm:items-center
                           sm:justify-between">

                <div class="flex items-start gap-4">

                    <div
                        class="flex h-11 w-11 shrink-0
                                   items-center justify-center
                                   rounded-full bg-white
                                   shadow-sm ring-1
                                   ring-slate-200">

                        <svg
                            class="h-5 w-5
                                       text-slate-500"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2">

                            <rect
                                x="3"
                                y="5"
                                width="18"
                                height="14"
                                rx="2" />

                            <path d="M3 10h18" />

                        </svg>

                    </div>

                    <div>

                        <p
                            class="font-medium
                                       text-slate-900">
                            Sin tarjeta para renovación
                        </p>

                        <p
                            class="mt-1 text-sm
                                       leading-5 text-slate-500">
                            Puedes pagar manualmente
                            sin guardar una tarjeta o
                            agregar una para renovaciones
                            automáticas.
                        </p>

                    </div>

                </div>


                <button
                    type="button"
                    wire:click="startCardSetup"
                    wire:loading.attr="disabled"
                    wire:target="startCardSetup"
                    class="inline-flex shrink-0
                               items-center justify-center
                               rounded-lg bg-slate-950
                               px-4 py-2.5 text-sm
                               font-semibold text-white
                               shadow-sm transition
                               hover:bg-slate-800
                               disabled:opacity-50">

                    <span
                        wire:loading.remove
                        wire:target="startCardSetup">
                        Agregar tarjeta
                    </span>

                    <span
                        wire:loading
                        wire:target="startCardSetup">
                        Preparando...
                    </span>

                </button>

            </div>

            @endif

        </div>

    </section>


    {{-- Stripe SetupIntent form --}}
    @if ($cardFormVisible)

    <section
        class="mt-6 overflow-hidden rounded-2xl
                   border border-slate-200
                   bg-white shadow-sm">

        <div
            class="border-b border-slate-100
                       bg-slate-50 px-6 py-5">

            <div class="flex items-start gap-3">

                <div
                    class="flex h-9 w-9 shrink-0
                               items-center justify-center
                               rounded-full bg-white
                               ring-1 ring-slate-200">

                    <svg
                        class="h-4 w-4
                                   text-slate-600"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2">

                        <rect
                            x="5"
                            y="11"
                            width="14"
                            height="9"
                            rx="2" />

                        <path
                            d="M8 11V8a4 4 0 0 1 8 0v3" />

                    </svg>

                </div>

                <div>

                    <h2
                        class="font-semibold
                                   text-slate-950">
                        Registrar tarjeta
                    </h2>

                    <p
                        class="mt-1 text-sm
                                   text-slate-500">
                        La información se envía
                        directamente a Stripe mediante
                        una conexión segura.
                    </p>

                </div>

            </div>

        </div>


        <div class="p-6">

            <div
                class="rounded-xl border
                           border-slate-200
                           bg-white p-4">

                <div wire:ignore>
                    <div id="stripe-payment-element"></div>
                </div>

            </div>


            <div
                id="stripe-card-error"
                class="mt-3 hidden rounded-lg
                           bg-red-50 px-4 py-3
                           text-sm text-red-700">
            </div>


            <div
                class="mt-6 flex flex-col-reverse
                           gap-3 sm:flex-row
                           sm:items-center
                           sm:justify-between">

                <p
                    class="text-xs leading-5
                               text-slate-400">
                    DocTotal nunca recibe el número
                    completo de tu tarjeta ni el CVC.
                </p>

                <button
                    type="button"
                    id="stripe-save-card"
                    class="inline-flex items-center
                               justify-center rounded-lg
                               bg-slate-950 px-5 py-2.5
                               text-sm font-semibold
                               text-white shadow-sm
                               transition hover:bg-slate-800
                               disabled:cursor-not-allowed
                               disabled:opacity-50">
                    Guardar tarjeta
                </button>

            </div>

        </div>

    </section>

    @endif


    {{-- Security --}}
    <section
        class="mt-6 rounded-2xl
               border border-slate-200
               bg-slate-50 p-5">

        <div class="flex items-start gap-4">

            <div
                class="flex h-10 w-10 shrink-0
                       items-center justify-center
                       rounded-full bg-white
                       shadow-sm ring-1 ring-slate-200">

                <svg
                    class="h-5 w-5
                           text-slate-600"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2">

                    <path
                        d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10" />

                    <path
                        d="m9 12 2 2 4-4" />

                </svg>

            </div>

            <div>

                <h2
                    class="text-sm font-semibold
                           text-slate-900">
                    Pagos protegidos por Stripe
                </h2>

                <p
                    class="mt-1 max-w-3xl
                           text-sm leading-6
                           text-slate-500">
                    DocTotal almacena únicamente información
                    no sensible como la marca, últimos cuatro
                    dígitos y vencimiento. El número completo
                    de tarjeta y el código de seguridad son
                    procesados directamente por Stripe.
                </p>

            </div>

        </div>

    </section>

</div>


@script
<script>
    /*
     |--------------------------------------------------------------------------
     | Stripe SetupIntent
     |--------------------------------------------------------------------------
     */

    let stripeInstance = null;
    let stripeElements = null;
    let stripePaymentElement = null;


    /*
     |--------------------------------------------------------------------------
     | Stripe manual PaymentIntent
     |--------------------------------------------------------------------------
     */

    let manualStripeInstance = null;
    let manualStripeElements = null;
    let manualStripePaymentElement = null;
    let manualClientSecret = null;
    let manualSavedPaymentMethodId = null;
    let manualUsesSavedPaymentMethod = false;


    /*
     |--------------------------------------------------------------------------
     | Errors
     |--------------------------------------------------------------------------
     */

    function showStripeError(message) {
        const errorElement =
            document.getElementById(
                'stripe-card-error'
            );

        if (!errorElement) {
            return;
        }

        errorElement.textContent =
            message;

        errorElement.classList.remove(
            'hidden'
        );
    }


    function clearStripeError() {
        const errorElement =
            document.getElementById(
                'stripe-card-error'
            );

        if (!errorElement) {
            return;
        }

        errorElement.textContent = '';

        errorElement.classList.add(
            'hidden'
        );
    }


    function showManualPaymentError(
        message,
        title = 'No fue posible completar el pago'
    ) {
        const errorElement =
            document.getElementById(
                'stripe-manual-payment-error'
            );

        if (errorElement) {
            errorElement.textContent =
                message;

            errorElement.classList.remove(
                'hidden'
            );
        }

        /*
         * El error inline se conserva como contexto dentro del
         * checkout, pero además usamos SweetAlert para que un
         * rechazo o error de cobro sea imposible de pasar por alto.
         */
        Swal.fire({
            title: title,
            text: message,
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
    }


    async function scrollToManualPaymentSection() {
        await new Promise(
            resolve =>
            requestAnimationFrame(
                () => requestAnimationFrame(resolve)
            )
        );

        const section =
            document.getElementById(
                'manual-payment-section'
            );

        if (!section) {
            return;
        }

        section.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });

        section.focus({
            preventScroll: true
        });
    }


    function clearManualPaymentError() {
        const errorElement =
            document.getElementById(
                'stripe-manual-payment-error'
            );

        if (!errorElement) {
            return;
        }

        errorElement.textContent = '';

        errorElement.classList.add(
            'hidden'
        );
    }


    /*
     |--------------------------------------------------------------------------
     | SetupIntent
     |--------------------------------------------------------------------------
     */

    $wire.on(
        'stripe-setup-started',
        async (event) => {
            clearStripeError();

            const stripeKey =
                event.stripeKey;

            const clientSecret =
                event.clientSecret;

            try {
                stripeInstance =
                    await window.loadStripe(
                        stripeKey
                    );

                if (!stripeInstance) {
                    throw new Error(
                        'Stripe.js no pudo inicializarse.'
                    );
                }

                await new Promise(
                    resolve =>
                    requestAnimationFrame(
                        () =>
                        requestAnimationFrame(
                            resolve
                        )
                    )
                );

                const container =
                    document.getElementById(
                        'stripe-payment-element'
                    );

                if (!container) {
                    throw new Error(
                        'No se encontró el contenedor de Stripe.'
                    );
                }

                if (stripePaymentElement) {
                    stripePaymentElement
                        .unmount();
                }

                stripeElements =
                    stripeInstance.elements({
                        clientSecret: clientSecret,
                    });

                stripePaymentElement =
                    stripeElements.create(
                        'payment'
                    );

                stripePaymentElement.mount(
                    '#stripe-payment-element'
                );
            } catch (error) {
                showStripeError(
                    error.message ??
                    'No fue posible cargar Stripe.'
                );
            }
        }
    );


    /*
     |--------------------------------------------------------------------------
     | Manual PaymentIntent
     |--------------------------------------------------------------------------
     */

    async function mountManualPaymentElement() {
        if (
            !manualStripeInstance ||
            !manualClientSecret
        ) {
            throw new Error(
                'Stripe todavía no está listo.'
            );
        }

        await new Promise(
            resolve =>
            requestAnimationFrame(
                () => requestAnimationFrame(resolve)
            )
        );

        const container =
            document.getElementById(
                'stripe-manual-payment-element'
            );

        if (!container) {
            throw new Error(
                'No se encontró el formulario de pago.'
            );
        }

        if (manualStripePaymentElement) {
            manualStripePaymentElement.unmount();
        }

        manualStripeElements =
            manualStripeInstance.elements({
                clientSecret: manualClientSecret,
            });

        manualStripePaymentElement =
            manualStripeElements.create('payment');

        manualStripePaymentElement.mount(
            '#stripe-manual-payment-element'
        );
    }


    $wire.on(
        'stripe-manual-payment-started',
        async (event) => {
            clearManualPaymentError();

            manualClientSecret =
                event.clientSecret;

            manualSavedPaymentMethodId =
                event.savedPaymentMethodId ?? null;

            manualUsesSavedPaymentMethod =
                Boolean(
                    event.useSavedPaymentMethod &&
                    manualSavedPaymentMethodId
                );

            try {
                manualStripeInstance =
                    await window.loadStripe(
                        event.stripeKey
                    );

                if (!manualStripeInstance) {
                    throw new Error(
                        'Stripe.js no pudo inicializarse.'
                    );
                }

                /*
                 * En recuperación con tarjeta guardada no montamos
                 * el Payment Element inicialmente. El usuario ve
                 * primero su tarjeta predeterminada. Si decide usar
                 * otra, se monta bajo demanda.
                 */
                if (!manualUsesSavedPaymentMethod) {
                    await mountManualPaymentElement();
                }

                /*
                 * El formulario aparece más abajo de la tarjeta del plan.
                 * Llevamos al usuario automáticamente al checkout una vez
                 * que Livewire y Stripe terminaron de prepararlo.
                 */
                await scrollToManualPaymentSection();
            } catch (error) {
                showManualPaymentError(
                    error.message ??
                    'No fue posible cargar el formulario de pago.'
                );
            }
        }
    );


    /*
     |--------------------------------------------------------------------------
     | Save card with SetupIntent
     |--------------------------------------------------------------------------
     */

    document.addEventListener(
        'click',
        async (event) => {
            const button =
                event.target.closest(
                    '#stripe-save-card'
                );

            if (!button) {
                return;
            }

            clearStripeError();

            if (
                !stripeInstance ||
                !stripeElements
            ) {
                showStripeError(
                    'Stripe todavía no está listo.'
                );

                return;
            }

            button.disabled =
                true;

            const originalText =
                button.textContent;

            button.textContent =
                'Guardando...';

            try {
                const result =
                    await stripeInstance
                    .confirmSetup({
                        elements: stripeElements,

                        redirect: 'if_required',
                    });

                if (result.error) {
                    showStripeError(
                        result.error.message ??
                        'Stripe rechazó los datos de la tarjeta.'
                    );

                    return;
                }

                const setupIntent =
                    result.setupIntent;

                if (
                    !setupIntent ||
                    !setupIntent.payment_method
                ) {
                    throw new Error(
                        'Stripe no devolvió un método de pago.'
                    );
                }

                const paymentMethodId =
                    typeof setupIntent.payment_method ===
                    'string' ?
                    setupIntent.payment_method :
                    setupIntent
                    .payment_method
                    .id;

                await $wire
                    .registerPaymentMethod(
                        paymentMethodId
                    );
            } catch (error) {
                showStripeError(
                    error.message ??
                    'No fue posible guardar la tarjeta.'
                );
            } finally {
                button.disabled =
                    false;

                button.textContent =
                    originalText;
            }
        }
    );

    document.addEventListener(
        'click',
        async (event) => {
            const button =
                event.target.closest(
                    '#schedule-subscription-cancellation'
                );

            if (!button) {
                return;
            }

            const result =
                await Swal.fire({
                    title: '¿Cancelar la suscripción?',
                    text: 'Seguirás teniendo acceso hasta que termine tu periodo actual. No se realizará otra renovación.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cancelar al final del periodo',
                    cancelButtonText: 'Conservar suscripción',
                    reverseButtons: true,
                    focusCancel: true,
                    confirmButtonColor: '#dc2626'
                });

            if (!result.isConfirmed) {
                return;
            }

            button.disabled = true;

            try {
                await $wire.scheduleCancellation();
            } finally {
                if (document.body.contains(button)) {
                    button.disabled = false;
                }
            }
        }
    );


    /*
     |--------------------------------------------------------------------------
     | Recovery with saved payment method
     |--------------------------------------------------------------------------
     */

    document.addEventListener(
        'click',
        async (event) => {
            const button =
                event.target.closest(
                    '#stripe-use-another-recovery-card'
                );

            if (!button) {
                return;
            }

            clearManualPaymentError();

            const wrapper =
                document.getElementById(
                    'stripe-manual-alternative-card-wrapper'
                );

            if (!wrapper) {
                return;
            }

            wrapper.classList.remove('hidden');
            button.classList.add('hidden');

            try {
                if (!manualStripePaymentElement) {
                    await mountManualPaymentElement();
                }
            } catch (error) {
                showManualPaymentError(
                    error.message ??
                    'No fue posible cargar el formulario de pago.'
                );
            }
        }
    );


    document.addEventListener(
        'click',
        async (event) => {
            const button =
                event.target.closest(
                    '#stripe-confirm-saved-recovery-payment'
                );

            if (!button) {
                return;
            }

            clearManualPaymentError();

            if (
                !manualStripeInstance ||
                !manualClientSecret
            ) {
                showManualPaymentError(
                    'Stripe todavía no está listo.'
                );

                return;
            }

            const paymentMethodId =
                button.dataset.paymentMethodId ||
                manualSavedPaymentMethodId;

            if (!paymentMethodId) {
                showManualPaymentError(
                    'No encontramos la tarjeta guardada.'
                );

                return;
            }

            button.disabled = true;

            const originalText =
                button.textContent;

            button.textContent =
                'Procesando pago...';

            try {
                /*
                 * confirmCardPayment sigue siendo on-session:
                 * si Stripe requiere 3DS puede solicitarlo aquí.
                 */
                const result =
                    await manualStripeInstance
                    .confirmCardPayment(
                        manualClientSecret, {
                            payment_method: paymentMethodId,
                        }
                    );

                if (result.error) {
                    showManualPaymentError(
                        result.error.message ??
                        'Stripe no pudo procesar el pago.',
                        'Pago rechazado'
                    );

                    return;
                }

                if (
                    !result.paymentIntent ||
                    result.paymentIntent.status !== 'succeeded'
                ) {
                    showManualPaymentError(
                        'El pago todavía no ha sido completado.'
                    );

                    return;
                }

                await $wire.confirmManualPayment();
            } catch (error) {
                showManualPaymentError(
                    error.message ??
                    'No fue posible completar el pago.'
                );
            } finally {
                if (document.body.contains(button)) {
                    button.disabled = false;
                    button.textContent = originalText;
                }
            }
        }
    );


    /*
     |--------------------------------------------------------------------------
     | Confirm manual payment
     |--------------------------------------------------------------------------
     */

    document.addEventListener(
        'click',
        async (event) => {
            const button =
                event.target.closest(
                    '#stripe-confirm-manual-payment'
                );

            if (!button) {
                return;
            }

            clearManualPaymentError();

            if (
                !manualStripeInstance ||
                !manualStripeElements
            ) {
                showManualPaymentError(
                    'Stripe todavía no está listo.'
                );

                return;
            }

            button.disabled =
                true;

            const originalText =
                button.textContent;

            button.textContent =
                'Procesando pago...';

            try {
                /*
                 * Leemos el checkbox directamente del DOM
                 * en el último instante posible.
                 */
                const saveCheckbox =
                    document.getElementById(
                        'manual-save-for-future'
                    );

                const saveForFuture =
                    saveCheckbox ?
                    saveCheckbox.checked :
                    false;

                /*
                 * CRÍTICO:
                 *
                 * Primero sincronizamos la decisión con
                 * el PaymentIntent existente en Stripe.
                 */
                await $wire
                    .prepareManualPaymentForConfirmation(
                        saveForFuture
                    );

                /*
                 * Sólo después de actualizar Stripe
                 * confirmamos el cobro.
                 */
                const result =
                    await manualStripeInstance
                    .confirmPayment({
                        elements: manualStripeElements,

                        redirect: 'if_required',
                    });

                if (result.error) {
                    showManualPaymentError(
                        result.error.message ??
                        'Stripe no pudo procesar el pago.',
                        'Pago rechazado'
                    );

                    return;
                }

                const paymentIntent =
                    result.paymentIntent;

                if (!paymentIntent) {
                    throw new Error(
                        'Stripe no devolvió el resultado del pago.'
                    );
                }

                if (
                    paymentIntent.status !==
                    'succeeded'
                ) {
                    showManualPaymentError(
                        'El pago todavía no ha sido completado.'
                    );

                    return;
                }

                await $wire
                    .confirmManualPayment();

            } catch (error) {
                showManualPaymentError(
                    error.message ??
                    'No fue posible completar el pago.'
                );
            } finally {
                /*
                 * Después de una operación exitosa
                 * Livewire puede haber eliminado el botón
                 * durante el re-render.
                 */
                if (
                    document.body.contains(
                        button
                    )
                ) {
                    button.disabled =
                        false;

                    button.textContent =
                        originalText;
                }
            }
        }
    );


    /*
     |--------------------------------------------------------------------------
     | Remove saved payment method
     |--------------------------------------------------------------------------
     */

    document.addEventListener(
        'click',
        async (event) => {
            const button =
                event.target.closest(
                    '#remove-payment-method'
                );

            if (!button) {
                return;
            }

            const result =
                await Swal.fire({
                    title: '¿Eliminar esta tarjeta?',

                    text: 'DocTotal ya no podrá utilizarla para renovaciones automáticas. Podrás agregar otra tarjeta cuando quieras.',

                    icon: 'warning',

                    showCancelButton: true,

                    confirmButtonText: 'Sí, eliminar tarjeta',

                    cancelButtonText: 'Cancelar',

                    reverseButtons: true,

                    focusCancel: true,

                    confirmButtonColor: '#dc2626'
                });

            if (!result.isConfirmed) {
                return;
            }

            button.disabled =
                true;

            try {
                await $wire
                    .removePaymentMethod();
            } catch (error) {
                /*
                 * El método Livewire maneja y reporta
                 * el error.
                 */
            } finally {
                if (
                    document.body.contains(
                        button
                    )
                ) {
                    button.disabled =
                        false;
                }
            }
        }
    );


    /*
     |--------------------------------------------------------------------------
     | Cleanup
     |--------------------------------------------------------------------------
     */

    $wire.on(
        'stripe-payment-method-saved',
        () => {
            if (stripePaymentElement) {
                stripePaymentElement
                    .unmount();
            }

            stripePaymentElement =
                null;

            stripeElements =
                null;

            stripeInstance =
                null;
        }
    );


    $wire.on(
        'stripe-payment-method-removed',
        () => {
            if (stripePaymentElement) {
                stripePaymentElement
                    .unmount();
            }

            stripePaymentElement =
                null;

            stripeElements =
                null;

            stripeInstance =
                null;
        }
    );


    $wire.on(
        'stripe-manual-payment-confirmed',
        () => {
            if (manualStripePaymentElement) {
                manualStripePaymentElement
                    .unmount();
            }

            manualStripePaymentElement =
                null;

            manualStripeElements =
                null;

            manualStripeInstance =
                null;

            manualClientSecret =
                null;

            manualSavedPaymentMethodId =
                null;

            manualUsesSavedPaymentMethod =
                false;
        }
    );


    /*
     |--------------------------------------------------------------------------
     | SweetAlert
     |--------------------------------------------------------------------------
     */

    $wire.on(
        'swal',
        (event) => {
            Swal.fire({
                title: event.title,

                text: event.text,

                icon: event.icon,

                confirmButtonText: 'Aceptar'
            });
        }
    );
</script>
@endscript