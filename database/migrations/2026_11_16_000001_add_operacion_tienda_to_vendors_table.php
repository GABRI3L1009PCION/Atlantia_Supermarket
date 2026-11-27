<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Horario de atencion, pausa y preferencias operativas de cada tienda.
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->time('hora_apertura')->nullable()->after('accepts_card');
            $table->time('hora_cierre')->nullable()->after('hora_apertura');
            $table->timestamp('pausado_hasta')->nullable()->after('hora_cierre');
            $table->string('pausa_motivo', 160)->nullable()->after('pausado_hasta');
            $table->unsignedSmallInteger('tiempo_preparacion_min')->default(20)->after('pausa_motivo');
            $table->boolean('alerta_sonora_pedidos')->default(true)->after('tiempo_preparacion_min');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->dropColumn([
                'hora_apertura',
                'hora_cierre',
                'pausado_hasta',
                'pausa_motivo',
                'tiempo_preparacion_min',
                'alerta_sonora_pedidos',
            ]);
        });
    }
};
