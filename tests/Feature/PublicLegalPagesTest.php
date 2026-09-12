<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicLegalPagesTest extends TestCase
{
    public function test_privacy_notice_is_public(): void
    {
        $this->get('/aviso-de-privacidad')
            ->assertOk()
            ->assertSee('Aviso de Privacidad');
    }

    public function test_terms_and_conditions_are_public(): void
    {
        $this->get('/terminos-y-condiciones')
            ->assertOk()
            ->assertSee('Términos y Condiciones');
    }

    public function test_home_exposes_large_social_sharing_metadata(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('property="og:title"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('name="twitter:card" content="summary_large_image"', false)
            ->assertSee('images/social/doctotal-social-card.png');

        $this->assertFileExists(public_path('images/social/doctotal-social-card.png'));
    }
}
