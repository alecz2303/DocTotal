<?php

namespace Tests\Feature\Subscription;

use Tests\TestCase;

class StripeWebhookCsrfTest extends TestCase
{
    public function test_stripe_webhook_is_not_rejected_by_csrf_middleware(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_dt56_test');

        $this->withMiddleware()
            ->postJson('/webhooks/stripe', [], [
                'Stripe-Signature' => 'invalid-signature',
            ])
            ->assertStatus(400)
            ->assertJson([
                'message' => 'Firma de webhook inválida.',
            ]);
    }

    public function test_regular_web_post_still_requires_csrf_token(): void
    {
        $this->withMiddleware()
            ->post('/logout')
            ->assertStatus(419);
    }
}
