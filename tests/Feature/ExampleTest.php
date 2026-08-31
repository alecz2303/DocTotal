<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_displays_public_landing_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('DocTotal');
    }
}
