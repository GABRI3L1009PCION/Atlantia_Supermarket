<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

/**
 * Caso base para pruebas Laravel de Atlantia.
 */
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Prepara instrumentacion basica de consultas para detectar N+1 en integracion.
     */
    protected function setUp(): void
    {
        parent::setUp();

        DB::enableQueryLog();
    }

    /**
     * Verifica que una prueba no exceda el presupuesto de consultas esperado.
     *
     * @param int $maximo
     * @return void
     */
    protected function assertQueryCountBelow(int $maximo): void
    {
        $this->assertLessThanOrEqual(
            $maximo,
            count(DB::getQueryLog()),
            'La prueba supero el presupuesto de consultas y puede esconder un N+1.'
        );
    }
}
