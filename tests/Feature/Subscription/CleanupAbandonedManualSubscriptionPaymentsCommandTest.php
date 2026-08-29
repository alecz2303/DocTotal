<?php

namespace Tests\Feature\Subscription;

use App\Actions\Billing\CleanupAbandonedManualSubscriptionPayments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class CleanupAbandonedManualSubscriptionPaymentsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_cleanup_command_reports_processed_checkouts(): void
    {
        Carbon::setTestNow(
            '2026-08-29 15:30:00'
        );

        config([
            'billing.manual_checkout_expiration_hours' =>
            24,
        ]);

        $action =
            Mockery::mock(
                CleanupAbandonedManualSubscriptionPayments::class
            );

        $action
            ->shouldReceive('execute')
            ->once()
            ->withArgs(
                function (
                    $cutoff,
                    $processedAt
                ): bool {
                    return
                        $cutoff->equalTo(
                            Carbon::parse(
                                '2026-08-28 15:30:00'
                            )
                        )
                        &&
                        $processedAt->equalTo(
                            Carbon::parse(
                                '2026-08-29 15:30:00'
                            )
                        );
                }
            )
            ->andReturn([
                'processed' =>
                3,

                'canceled' =>
                2,

                'reconciled' =>
                1,

                'errors' =>
                0,
            ]);

        $this->app->instance(
            CleanupAbandonedManualSubscriptionPayments::class,
            $action
        );

        $this->artisan(
            'billing:cleanup-abandoned-checkouts'
        )
            ->expectsOutput(
                'Checkouts procesados: 3'
            )
            ->expectsOutput(
                'Checkouts cancelados: 2'
            )
            ->expectsOutput(
                'Checkouts reconciliados: 1'
            )
            ->assertSuccessful();
    }

    public function test_cleanup_command_reports_errors_without_failing_command(): void
    {
        Carbon::setTestNow(
            '2026-08-29 15:30:00'
        );

        config([
            'billing.manual_checkout_expiration_hours' =>
            24,
        ]);

        $action =
            Mockery::mock(
                CleanupAbandonedManualSubscriptionPayments::class
            );

        $action
            ->shouldReceive('execute')
            ->once()
            ->andReturn([
                'processed' =>
                2,

                'canceled' =>
                1,

                'reconciled' =>
                1,

                'errors' =>
                1,
            ]);

        $this->app->instance(
            CleanupAbandonedManualSubscriptionPayments::class,
            $action
        );

        $this->artisan(
            'billing:cleanup-abandoned-checkouts'
        )
            ->expectsOutput(
                'Checkouts procesados: 2'
            )
            ->expectsOutput(
                'Checkouts cancelados: 1'
            )
            ->expectsOutput(
                'Checkouts reconciliados: 1'
            )
            ->expectsOutput(
                'Checkouts con error: 1'
            )
            ->assertSuccessful();
    }
}
