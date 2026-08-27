<?php

namespace Tests\Feature\Subscription;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class BillingSchedulerTest extends TestCase
{
    public function test_safe_billing_tasks_are_scheduled_by_default(): void
    {
        config([
            'billing.automatic_charging_enabled' => false,
        ]);

        $events = app(
            Schedule::class
        )->events();

        $commands = collect($events)
            ->map(
                fn($event) =>
                $event->command
            )
            ->filter()
            ->values();

        $this->assertTrue(
            $commands->contains(
                fn($command) =>
                str_contains(
                    $command,
                    'billing:process-cancellations'
                )
            )
        );

        $this->assertTrue(
            $commands->contains(
                fn($command) =>
                str_contains(
                    $command,
                    'billing:process-expired-grace-periods'
                )
            )
        );

        $this->assertFalse(
            $commands->contains(
                fn($command) =>
                str_contains(
                    $command,
                    'billing:process-renewals'
                )
            )
        );

        $this->assertFalse(
            $commands->contains(
                fn($command) =>
                str_contains(
                    $command,
                    'billing:process-retries'
                )
            )
        );
    }

    public function test_safe_billing_tasks_run_every_minute(): void
    {
        config([
            'billing.automatic_charging_enabled' => false,
        ]);

        $events = collect(
            app(Schedule::class)->events()
        );

        $cancellation =
            $events->first(
                fn($event) =>
                str_contains(
                    (string) $event->command,
                    'billing:process-cancellations'
                )
            );

        $grace =
            $events->first(
                fn($event) =>
                str_contains(
                    (string) $event->command,
                    'billing:process-expired-grace-periods'
                )
            );

        $this->assertNotNull(
            $cancellation
        );

        $this->assertNotNull(
            $grace
        );

        $this->assertSame(
            '* * * * *',
            $cancellation->expression
        );

        $this->assertSame(
            '* * * * *',
            $grace->expression
        );
    }

    public function test_safe_billing_tasks_prevent_overlapping(): void
    {
        config([
            'billing.automatic_charging_enabled' => false,
        ]);

        $events = collect(
            app(Schedule::class)->events()
        );

        $cancellation =
            $events->first(
                fn($event) =>
                str_contains(
                    (string) $event->command,
                    'billing:process-cancellations'
                )
            );

        $grace =
            $events->first(
                fn($event) =>
                str_contains(
                    (string) $event->command,
                    'billing:process-expired-grace-periods'
                )
            );

        $this->assertTrue(
            $cancellation->withoutOverlapping
        );

        $this->assertTrue(
            $grace->withoutOverlapping
        );
    }
}
