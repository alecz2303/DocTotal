<?php

namespace App\Console\Commands;

use App\Models\BillingCustomer;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stripe\Exception\InvalidRequestException;
use Stripe\StripeClient;
use Throwable;

class ResetTestTenantBilling extends Command
{
    protected $signature =
    'billing:reset-test-tenant
        {tenant : ID, UUID o slug del tenant}
        {--yes : No pedir confirmación}';

    protected $description =
    'Reinicia el billing de un tenant para pruebas locales.';

    public function handle(
        StripeClient $stripe
    ): int {
        /*
         * Este comando jamás debe poder ejecutarse
         * fuera de local/testing.
         */
        if (
            ! app()->environment([
                'local',
                'testing',
            ])
        ) {
            $this->error(
                'Este comando sólo puede ejecutarse en local o testing.'
            );

            return self::FAILURE;
        }

        $tenant =
            $this->findTenant(
                (string) $this->argument(
                    'tenant'
                )
            );

        if (! $tenant) {
            $this->error(
                'No se encontró el tenant.'
            );

            return self::FAILURE;
        }

        $this->newLine();

        $this->info(
            sprintf(
                'Tenant: #%d — %s (%s)',
                $tenant->id,
                $tenant->name,
                $tenant->slug
            )
        );

        $this->warn(
            'Se eliminarán suscripciones, pagos y métodos de pago de prueba.'
        );

        $this->warn(
            'El Customer Stripe se conservará para poder reutilizar el mismo tenant.'
        );

        if (
            ! $this->option('yes')
            && ! $this->confirm(
                '¿Continuar con el reset?',
                false
            )
        ) {
            $this->info(
                'Reset cancelado.'
            );

            return self::SUCCESS;
        }

        /*
         * Primero garantizamos que el tenant tenga
         * un Stripe Customer REAL y vigente.
         *
         * Esto además repara tenants que quedaron
         * apuntando a un cus_* eliminado por la
         * versión anterior de este comando.
         */
        $billingCustomer =
            $this->ensureValidStripeCustomer(
                $stripe,
                $tenant
            );

        /*
         * Quitamos de Stripe las tarjetas actualmente
         * adjuntas al Customer.
         *
         * Conservamos el Customer, porque borrarlo y
         * volverlo a crear puede entrar en conflicto
         * con la idempotencia usada por
         * EnsureStripeBillingCustomer.
         */
        $this->detachStripePaymentMethods(
            $stripe,
            $billingCustomer
        );

        /*
         * Ahora limpiamos exclusivamente la información
         * transaccional local.
         *
         * BillingCustomer NO se elimina.
         */
        DB::transaction(
            function () use (
                $tenant
            ): void {
                PaymentMethod::withoutGlobalScopes()
                    ->withTrashed()
                    ->where(
                        'tenant_id',
                        $tenant->id
                    )
                    ->forceDelete();

                Payment::withoutGlobalScopes()
                    ->withTrashed()
                    ->where(
                        'tenant_id',
                        $tenant->id
                    )
                    ->forceDelete();

                Subscription::withoutGlobalScopes()
                    ->withTrashed()
                    ->where(
                        'tenant_id',
                        $tenant->id
                    )
                    ->forceDelete();

                $tenant->update([
                    'status' =>
                    'trial',

                    'suspended_at' =>
                    null,

                    'deletion_due_at' =>
                    null,

                    'trial_started_at' =>
                    now(),

                    'trial_ends_at' =>
                    now()->addDays(3),
                ]);
            }
        );

        $tenant->refresh();

        $billingCustomer->refresh();

        $this->newLine();

        $this->info(
            'Billing reiniciado correctamente.'
        );

        $this->table(
            [
                'Campo',
                'Valor',
            ],
            [
                [
                    'Tenant',
                    sprintf(
                        '#%d %s',
                        $tenant->id,
                        $tenant->name
                    ),
                ],
                [
                    'Estado',
                    $tenant->status,
                ],
                [
                    'Stripe Customer',
                    $billingCustomer
                        ->provider_customer_id,
                ],
                [
                    'Trial inicia',
                    $tenant
                        ->trial_started_at
                        ?->format(
                            'Y-m-d H:i:s'
                        ),
                ],
                [
                    'Trial termina',
                    $tenant
                        ->trial_ends_at
                        ?->format(
                            'Y-m-d H:i:s'
                        ),
                ],
                [
                    'Subscriptions',
                    Subscription::withoutGlobalScopes()
                        ->withTrashed()
                        ->where(
                            'tenant_id',
                            $tenant->id
                        )
                        ->count(),
                ],
                [
                    'Payments',
                    Payment::withoutGlobalScopes()
                        ->withTrashed()
                        ->where(
                            'tenant_id',
                            $tenant->id
                        )
                        ->count(),
                ],
                [
                    'Payment methods',
                    PaymentMethod::withoutGlobalScopes()
                        ->withTrashed()
                        ->where(
                            'tenant_id',
                            $tenant->id
                        )
                        ->count(),
                ],
                [
                    'Billing customers',
                    BillingCustomer::withoutGlobalScopes()
                        ->withTrashed()
                        ->where(
                            'tenant_id',
                            $tenant->id
                        )
                        ->count(),
                ],
            ]
        );

        return self::SUCCESS;
    }

    private function ensureValidStripeCustomer(
        StripeClient $stripe,
        Tenant $tenant,
    ): BillingCustomer {
        $billingCustomer =
            BillingCustomer::withoutGlobalScopes()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->where(
                'provider',
                BillingCustomer::PROVIDER_STRIPE
            )
            ->first();

        /*
         * Si existe localmente comprobamos que siga
         * existiendo realmente en Stripe.
         */
        if ($billingCustomer) {
            try {
                $stripeCustomer =
                    $stripe
                    ->customers
                    ->retrieve(
                        $billingCustomer
                            ->provider_customer_id
                    );

                if (
                    $stripeCustomer->id
                    && ! $stripeCustomer->deleted
                ) {
                    $this->line(
                        sprintf(
                            'Stripe Customer reutilizado: %s',
                            $stripeCustomer->id
                        )
                    );

                    return $billingCustomer;
                }
            } catch (
                InvalidRequestException $exception
            ) {
                /*
                 * Puede ser un Customer borrado por una
                 * ejecución anterior del reset.
                 *
                 * Lo repararemos abajo.
                 */
                if (
                    $exception->getStripeCode()
                    !== 'resource_missing'
                ) {
                    throw $exception;
                }
            }
        }

        /*
         * Customer inexistente o referencia rota.
         *
         * Creamos uno nuevo usando una idempotency key
         * ÚNICA para esta reparación. No reutilizamos
         * la llave determinística de producción.
         */
        $stripeCustomer =
            $stripe
            ->customers
            ->create(
                [
                    'name' =>
                    $tenant->name,

                    'metadata' => [
                        'doctotal_tenant_id' =>
                        (string) $tenant->id,

                        'doctotal_tenant_uuid' =>
                        $tenant->uuid,

                        'created_by' =>
                        'billing-reset-command',
                    ],
                ],
                [
                    'idempotency_key' =>
                    sprintf(
                        'doctotal:billing-reset:%s:%s',
                        $tenant->uuid,
                        Str::uuid()
                    ),
                ]
            );

        if (! $stripeCustomer->id) {
            throw new \LogicException(
                'Stripe no devolvió un Customer válido durante el reset.'
            );
        }

        if ($billingCustomer) {
            $billingCustomer->update([
                'provider_customer_id' =>
                $stripeCustomer->id,
            ]);

            $billingCustomer->refresh();
        } else {
            $billingCustomer =
                BillingCustomer::withoutGlobalScopes()
                ->create([
                    'tenant_id' =>
                    $tenant->id,

                    'provider' =>
                    BillingCustomer::PROVIDER_STRIPE,

                    'provider_customer_id' =>
                    $stripeCustomer->id,
                ]);
        }

        $this->line(
            sprintf(
                'Stripe Customer reparado/creado: %s',
                $stripeCustomer->id
            )
        );

        return $billingCustomer;
    }

    private function detachStripePaymentMethods(
        StripeClient $stripe,
        BillingCustomer $billingCustomer,
    ): void {
        try {
            $paymentMethods =
                $stripe
                ->paymentMethods
                ->all([
                    'customer' =>
                    $billingCustomer
                        ->provider_customer_id,

                    'type' =>
                    'card',

                    'limit' =>
                    100,
                ]);

            foreach (
                $paymentMethods->data
                as $paymentMethod
            ) {
                try {
                    $stripe
                        ->paymentMethods
                        ->detach(
                            $paymentMethod->id
                        );

                    $this->line(
                        sprintf(
                            'PaymentMethod desvinculado: %s',
                            $paymentMethod->id
                        )
                    );
                } catch (Throwable $exception) {
                    report(
                        $exception
                    );

                    $this->warn(
                        sprintf(
                            'No fue posible desvincular %s.',
                            $paymentMethod->id
                        )
                    );
                }
            }
        } catch (Throwable $exception) {
            report(
                $exception
            );

            $this->warn(
                'No fue posible consultar los métodos de pago de Stripe.'
            );
        }
    }

    private function findTenant(
        string $value
    ): ?Tenant {
        return Tenant::query()
            ->withoutGlobalScopes()
            ->where(
                function ($query) use (
                    $value
                ): void {
                    if (ctype_digit($value)) {
                        $query->orWhere(
                            'id',
                            (int) $value
                        );
                    }

                    $query
                        ->orWhere(
                            'uuid',
                            $value
                        )
                        ->orWhere(
                            'slug',
                            $value
                        );
                }
            )
            ->first();
    }
}
