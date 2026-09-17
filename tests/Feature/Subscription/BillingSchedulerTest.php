<?php

namespace Tests\Feature\Subscription;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class BillingSchedulerTest extends TestCase
{
    public function test_safe_billing_tasks_are_scheduled_by_default(): void
    {
        config([
            'billing.automatic_charging_enabled' => false,
        ]);

        $summaries = $this->summaries();

        $this->assertTrue(
            $summaries->contains('doctotal:billing:process-cancellations')
        );

        $this->assertTrue(
            $summaries->contains('doctotal:billing:process-expired-grace-periods')
        );

        $this->assertFalse(
            $summaries->contains('doctotal:billing:process-renewals')
        );

        $this->assertFalse(
            $summaries->contains('doctotal:billing:process-retries')
        );
    }

    public function test_safe_billing_tasks_run_every_minute(): void
    {
        $cancellation = $this->event(
            'doctotal:billing:process-cancellations'
        );

        $grace = $this->event(
            'doctotal:billing:process-expired-grace-periods'
        );

        $this->assertNotNull($cancellation);
        $this->assertNotNull($grace);

        $this->assertSame('* * * * *', $cancellation->expression);
        $this->assertSame('* * * * *', $grace->expression);
    }

    public function test_safe_billing_tasks_prevent_overlapping(): void
    {
        $cancellation = $this->event(
            'doctotal:billing:process-cancellations'
        );

        $grace = $this->event(
            'doctotal:billing:process-expired-grace-periods'
        );

        $this->assertNotNull($cancellation);
        $this->assertNotNull($grace);
        $this->assertTrue($cancellation->withoutOverlapping);
        $this->assertTrue($grace->withoutOverlapping);
    }

    public function test_scheduled_tasks_run_as_in_process_callbacks(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(
                fn ($event) => str_starts_with(
                    $event->getSummaryForDisplay(),
                    'doctotal:'
                )
            );

        $this->assertNotEmpty($events);

        $events->each(function ($event): void {
            $this->assertInstanceOf(CallbackEvent::class, $event);
            $this->assertNull($event->command);
            $this->assertTrue($event->withoutOverlapping);
        });
    }

    private function summaries()
    {
        return collect(app(Schedule::class)->events())
            ->map(fn ($event) => $event->getSummaryForDisplay());
    }

    private function event(string $summary): ?object
    {
        return collect(app(Schedule::class)->events())
            ->first(
                fn ($event) => $event->getSummaryForDisplay() === $summary
            );
    }
}
