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
        Schema::create('delivery_routes', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique()->comment('Identificador público seguro de la ruta.');
            $table->foreignId('pedido_id')->unique()->constrained('pedidos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('repartidor_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->json('ruta_planificada')->nullable()->comment('Respuesta normalizada de ruta óptima de Mapbox.');
            $table->json('ruta_real')->nullable()->comment('Puntos GPS reales capturados durante la entrega.');
            $table->decimal('distancia_km', 8, 2)->nullable();
            $table->unsignedSmallInteger('tiempo_estimado_min')->nullable();
            $table->unsignedSmallInteger('tiempo_real_min')->nullable();
            $table->enum('estado', [
                'pendiente',
                'asignada',
                'iniciada',
                'pausada',
                'completada',
                'cancelada',
            ])->default('pendiente')->index();
            $table->timestamp('asignada_at')->nullable();
            $table->timestamp('iniciada_at')->nullable();
            $table->timestamp('completada_at')->nullable();
            $table->string('firma_path', 500)->nullable()->comment('Firma digital del receptor almacenada en S3-compatible.');
            $table->string('foto_entrega_path', 500)->nullable()->comment('Foto de evidencia de entrega almacenada en S3-compatible.');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['repartidor_id', 'estado']);
            $table->index(['estado', 'created_at']);
            $table->index(['iniciada_at', 'completada_at']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_routes');
    }
};
