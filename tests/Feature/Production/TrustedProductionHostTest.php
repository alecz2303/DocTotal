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

        $this->withServerVariables([
            'HTTP_HOST' => 'doctotal.test',
        ])
            ->get('/up')
            ->assertOk();
    }

    public function test_production_rejects_unexpected_host_header(): void
    {
        config([
            'app.env' => 'production',
            'app.url' => 'https://doctotal.test',
        ]);

        $this->withServerVariables([
            'HTTP_HOST' => 'evil.test',
        ])
            ->get('/up')
            ->assertStatus(400);
    }

    public function test_non_production_does_not_enforce_host_lock(): void
    {
        config([
            'app.env' => 'local',
            'app.url' => 'http://localhost',
        ]);

        $this->withServerVariables([
            'HTTP_HOST' => 'arbitrary.local',
        ])
            ->get('/up')
            ->assertOk();
    }
}
