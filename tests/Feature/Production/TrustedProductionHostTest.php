<?php

namespace Tests\Feature\Production;

use Tests\TestCase;

class TrustedProductionHostTest extends TestCase
{
    public function test_production_accepts_canonical_app_host(): void
    {
        config([
            'app.env' => 'production',
            'app.url' => 'https://doctotal.test',
        ]);

        $this->get('https://doctotal.test/login')
            ->assertOk();
    }

    public function test_production_rejects_unexpected_host_header(): void
    {
        config([
            'app.env' => 'production',
            'app.url' => 'https://doctotal.test',
        ]);

        $this->get('https://evil.test/login')
            ->assertStatus(400);
    }

    public function test_non_production_does_not_enforce_host_lock(): void
    {
        config([
            'app.env' => 'local',
            'app.url' => 'http://localhost',
        ]);

        $this->get('http://arbitrary.local/login')
            ->assertOk();
    }
}
