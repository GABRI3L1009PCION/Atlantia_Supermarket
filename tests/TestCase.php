<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
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

        /* Las pruebas no dependen de los assets compilados (en CI no se corre npm run build). */
        $this->withoutVite();

        /* Con CACHE_STORE=redis (CI) la cache sobrevive entre pruebas: los contadores de
         * limite de intentos se acumulan y terminan en 429. Cada prueba arranca con cache limpia. */
        Cache::flush();

        DB::enableQueryLog();
    }

    /**
     * Verifica que una prueba no exceda el presupuesto de consultas esperado.
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
