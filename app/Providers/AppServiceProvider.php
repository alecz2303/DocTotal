<?php

namespace App\Providers;

use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use App\Contracts\PaymentGateway;
use App\Services\Billing\FakePaymentGateway;
use LogicException;
use Stripe\StripeClient;
use App\Contracts\StripePaymentIntentProcessor as StripePaymentIntentProcessorContract;
use App\Services\Billing\StripePaymentIntentProcessor;
use App\Contracts\StripePaymentIntentApi as StripePaymentIntentApiContract;
use App\Services\Billing\StripePaymentIntentApi;
use App\Contracts\StripeCustomerApi as StripeCustomerApiContract;
use App\Services\Billing\StripeCustomerApi;
use App\Contracts\StripeSetupIntentApi as StripeSetupIntentApiContract;
use App\Services\Billing\StripeSetupIntentApi;
use App\Contracts\StripePaymentMethodApi as StripePaymentMethodApiContract;
use App\Services\Billing\StripePaymentMethodApi;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, function () {
            return new TenantContext();
        });

        $this->app->singleton(
            PaymentGateway::class,
            function ($app): PaymentGateway {
                return match (config(
                    'billing.payment_gateway',
                    'fake'
                )) {
                    'stripe' =>
                    $app->make(
                        \App\Services\Billing\StripePaymentGateway::class
                    ),

                    default =>
                    $app->make(
                        FakePaymentGateway::class
                    ),
                };
            }
        );

        $this->app->singleton(
            StripeClient::class,
            function (): StripeClient {
                $secret =
                    config('services.stripe.secret');

                if (! $secret) {
                    throw new LogicException(
                        'STRIPE_SECRET no está configurado.'
                    );
                }

                return new StripeClient(
                    $secret
                );
            }
        );

        $this->app->singleton(
            StripePaymentIntentProcessorContract::class,
            StripePaymentIntentProcessor::class
        );

        $this->app->singleton(
            StripePaymentIntentApiContract::class,
            StripePaymentIntentApi::class
        );

        $this->app->singleton(
            StripeCustomerApiContract::class,
            StripeCustomerApi::class
        );

        $this->app->singleton(
            StripeSetupIntentApiContract::class,
            StripeSetupIntentApi::class
        );

        $this->app->singleton(
            StripePaymentMethodApiContract::class,
            StripePaymentMethodApi::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::addNamespace(
            'layouts',
            resource_path('views/components/layouts')
        );


        Event::listen(Verified::class, function (Verified $event): void {
            app(AuditLogger::class)->safeLog(
                action: 'account.email.verified',
                auditable: $event->user,
                description: 'Correo electrónico de acceso verificado.',
            );
        });
    }
}
