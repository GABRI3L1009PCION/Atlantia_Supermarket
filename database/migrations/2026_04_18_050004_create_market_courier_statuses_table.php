<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Ejecuta la migración.
     */
    public function up(): void
    {
        Schema::create('market_courier_statuses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('repartidor_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->timestamp('timestamp_gps')->index();
            $table->enum('estado', [
                'disponible',
                'asignado',
                'en_ruta',
                'entregando',
                'fuera_servicio',
            ])->index();
            $table->unsignedSmallInteger('battery_level')->nullable()->comment('Nivel de batería reportado por el dispositivo móvil.');
            $table->decimal('accuracy_meters', 8, 2)->nullable()->comment('Precisión GPS reportada por el dispositivo.');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['repartidor_id', 'timestamp_gps']);
            $table->index(['pedido_id', 'timestamp_gps']);
            $table->index(['estado', 'timestamp_gps']);
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_courier_statuses');
    }
};
