<?php

namespace Tests\Feature\Subscription;

use Tests\TestCase;

class StripeWebhookCsrfTest extends TestCase
{
    public function test_stripe_webhook_reaches_signature_validation_without_csrf_rejection(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_dt56_test');

        $this->withMiddleware()
            ->postJson('/webhooks/stripe', [], [
                'Stripe-Signature' => 'invalid-signature',
            ])
            ->assertStatus(400)
            ->assertJson([
                'received' => false,
            ]);
    }
}
