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
        Schema::create('dte_anulaciones', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique()->comment('Identificador público seguro de la anulación.');
            $table->foreignId('dte_id')->unique()->constrained('dte_facturas')->restrictOnDelete()->cascadeOnUpdate();
            $table->text('motivo');
            $table->timestamp('fecha_anulacion')->index();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('uuid_anulacion_sat', 120)->nullable()->unique();
            $table->enum('estado', ['solicitada', 'aceptada', 'rechazada'])->default('solicitada')->index();
            $table->json('certificador_respuesta')->nullable()->comment('Respuesta de INFILE para la anulación.');
            $table->timestamps();

            $table->index(['estado', 'fecha_anulacion']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('dte_anulaciones');
    }
};
