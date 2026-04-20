<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

/**
 * Crea la aplicacion Laravel para la suite de pruebas.
 */
trait CreatesApplication
{
    /**
     * Construye una instancia fresca de la aplicacion.
     */
    public function createApplication(): mixed
    {
        $app = require __DIR__ . '/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
