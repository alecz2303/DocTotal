<?php

namespace Tests\Feature\Subscription;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_view_its_successful_payment_receipt(): void
    {
        [$user, $payment] = $this->successfulPayment();

        $this->actingAs($user)
            ->get(route('billing.receipts.show', $payment))
            ->assertOk()
            ->assertSee('Comprobante operativo de pago')
            ->assertSee($payment->uuid);
    }

    public function test_pending_payment_has_no_receipt(): void
    {
        [$user, $payment] = $this->successfulPayment(Payment::STATUS_PENDING);

        $this->actingAs($user)
            ->get(route('billing.receipts.show', $payment))
            ->assertNotFound();
    }

    public function test_other_tenant_cannot_view_payment_receipt(): void
    {
        [, $payment] = $this->successfulPayment();

        $otherTenant = Tenant::create([
            'name' => 'Consultorio Sur',
            'slug' => 'consultorio-sur',
        ]);

        $otherUser = User::factory()->create([
            'tenant_id' => $otherTenant->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($otherUser)
            ->get(route('billing.receipts.show', $payment))
            ->assertNotFound();
    }

    private function successfulPayment(string $status = Payment::STATUS_SUCCEEDED): array
    {
        $tenant = Tenant::create(['name' => 'Consultorio Norte', 'slug' => 'consultorio-norte']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'email_verified_at' => now()]);

        $periodStartsAt = now()->subMonth();
        $periodEndsAt = now()->addMonth();

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::BILLING_CYCLE_MONTHLY,
            'billing_amount' => 129900,
            'billing_currency' => 'MXN',
            'starts_at' => $periodStartsAt,
            'current_period_starts_at' => $periodStartsAt,
            'current_period_ends_at' => $periodEndsAt,
            'next_billing_at' => $periodEndsAt,
            'cancel_at_period_end' => false,
            'retry_count' => 0,
        ]);

        $payment = Payment::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'amount' => 129900,
            'gross_amount' => 129900,
            'currency' => 'MXN',
            'status' => $status,
            'attempted_at' => now(),
            'paid_at' => $status === Payment::STATUS_SUCCEEDED ? now() : null,
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_receipt',
            'idempotency_key' => 'receipt-'.$tenant->id.'-'.$status,
            'billing_cycle' => Subscription::BILLING_CYCLE_MONTHLY,
        ]);

        return [$user, $payment];
    }
}
