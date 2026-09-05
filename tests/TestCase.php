<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Historical DocTotal feature tests predate mandatory email verification.
     *
     * By default, a user authenticated through actingAs() is treated as an
     * already-verified account. This keeps legacy tests focused on the feature
     * they were written for instead of being intercepted by the `verified`
     * middleware introduced in DT-28.
     *
     * Tests that intentionally exercise unverified accounts can opt out by
     * setting this property to true.
     */
    protected bool $preserveUnverifiedUsers = false;

    public function actingAs(Authenticatable $user, $guard = null)
    {
        if (! $this->preserveUnverifiedUsers
            && method_exists($user, 'hasVerifiedEmail')
            && ! $user->hasVerifiedEmail()
            && method_exists($user, 'forceFill')
            && method_exists($user, 'saveQuietly')) {
            // Test-only compatibility: do not dispatch Verified or model events,
            // because historical audit/event tests must remain unaffected.
            $user->forceFill([
                'email_verified_at' => now(),
            ])->saveQuietly();
        }

        return parent::actingAs($user, $guard);
    }
}
