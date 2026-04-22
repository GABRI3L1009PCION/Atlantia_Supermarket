<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::table('delivery_routes', function (Blueprint $table): void {
            $table->timestamp('aceptada_at')
                ->nullable()
                ->after('asignada_at')
                ->comment('Momento en que el repartidor acepto tomar la entrega.');

            $table->index(['repartidor_id', 'aceptada_at']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::table('delivery_routes', function (Blueprint $table): void {
            $table->dropIndex(['repartidor_id', 'aceptada_at']);
            $table->dropColumn('aceptada_at');
        });
    }
};
