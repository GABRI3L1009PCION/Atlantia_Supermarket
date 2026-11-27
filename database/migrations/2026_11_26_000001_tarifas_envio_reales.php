<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tarifa de envio igual a lo que cobra el repartidor:
 * Q25 en Santo Tomas de Castilla y Q35 en Puerto Barrios.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('delivery_zones')->where('municipio', 'Santo Tomas')->update(['costo_base' => 25, 'updated_at' => now()]);
        DB::table('delivery_zones')->where('municipio', 'Puerto Barrios')->update(['costo_base' => 35, 'updated_at' => now()]);
    }

    public function down(): void
    {
        /* Las tarifas anteriores eran distintas por zona; se ajustan desde Zonas de entrega. */
    }
};
