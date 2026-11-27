<?php

namespace Tests\Unit;

use App\Support\Iva;
use PHPUnit\Framework\TestCase;

class IvaTest extends TestCase
{
    public function test_agrega_iva_al_precio_sin_iva(): void
    {
        $this->assertSame(28.00, Iva::agregar(25.00));
        $this->assertSame(42.00, Iva::agregar(37.50));
    }

    public function test_desglosa_iva_incluido_sin_recargar(): void
    {
        $this->assertSame(4.50, Iva::incluido(42.00));
        $this->assertSame(37.50, Iva::quitar(42.00));
    }
}
