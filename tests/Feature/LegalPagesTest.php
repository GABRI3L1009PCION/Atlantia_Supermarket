<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_terminos_page_shows_marketplace_and_vendor_responsibility(): void
    {
        $this->get(route('legal.terminos'))
            ->assertOk()
            ->assertSee('Términos y Condiciones de Uso y Compra', false)
            ->assertSee('Si un comercio no envía tu pedido', false)
            ->assertSee('Decreto 006-2003', false)
            ->assertSee('Condiciones para comercios afiliados', false);
    }

    public function test_privacidad_page_explains_data_sharing_and_rights(): void
    {
        $this->get(route('legal.privacidad'))
            ->assertOk()
            ->assertSee('Política de Privacidad', false)
            ->assertSee('No vendemos ni alquilamos tus datos personales', false)
            ->assertSee('10 días hábiles', false);
    }
}
