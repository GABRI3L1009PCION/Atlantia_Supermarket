<?php

namespace Tests\Feature\Checkout;

use App\Support\CoberturaColonias;
use Tests\TestCase;

class CoberturaColoniasTest extends TestCase
{
    public function test_detects_excluded_colonias_in_santo_tomas(): void
    {
        $this->assertSame('El Bordo', CoberturaColonias::excluida('Santo Tomas', 'Colonia El Bordo'));
        $this->assertSame('Piedras Negras', CoberturaColonias::excluida('Santo Tomas', 'piedras negras II'));
        $this->assertSame('El Pueblito', CoberturaColonias::excluida('Santo Tomas', null, 'Barrio El Pueblito, casa 4'));
        $this->assertSame('La Bendición', CoberturaColonias::excluida('Santo Tomas', 'la bendicion'));
        $this->assertNull(CoberturaColonias::excluida('Santo Tomas', 'Colonia La Repegua'));
    }

    public function test_detects_excluded_barrios_in_puerto_barrios(): void
    {
        $this->assertSame('Los Tanques', CoberturaColonias::excluida('Puerto Barrios', 'Barrio Los Tanques'));
        $this->assertSame('San José', CoberturaColonias::excluida('Puerto Barrios', 'Barrio San Jose'));
        $this->assertSame('Los Ángeles', CoberturaColonias::excluida('Puerto Barrios', 'Col. Los Angeles'));
        $this->assertNull(CoberturaColonias::excluida('Puerto Barrios', 'Barrio El Centro'));
    }

    public function test_street_names_do_not_trigger_exclusion(): void
    {
        /* "San Jose" como nombre de calle no es el Barrio San Jose. */
        $this->assertNull(CoberturaColonias::excluida('Puerto Barrios', 'Barrio El Centro', 'Calle San Jose, casa 12'));
    }

    public function test_quebrada_seca_and_san_agustin_belong_to_santo_tomas(): void
    {
        $this->assertSame('Santo Tomas', CoberturaColonias::municipioDe('Colonia Quebrada Seca'));
        $this->assertSame('Santo Tomas', CoberturaColonias::municipioDe('san agustin'));
        $this->assertSame('Puerto Barrios', CoberturaColonias::municipioDe('Barrio La Veintidos'));
    }
}
