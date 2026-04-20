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
        Schema::create('cliente_detalles', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // Campos cifrados
            $table->longText('dpi')->nullable()->comment('DPI cifrado a nivel de modelo.');
            $table->longText('telefono')->nullable()->comment('Teléfono cifrado a nivel de modelo.');

            $table->date('fecha_nacimiento')->nullable();
            $table->string('genero', 30)->nullable();

            // Preferencias tipo arreglo/json
            $table->json('preferencias')->nullable();

            $table->boolean('acepta_marketing')->default(false);
            $table->timestamp('terminos_aceptados_at')->nullable();
            $table->timestamp('privacidad_aceptada_at')->nullable();

            $table->timestamps();

            $table->index('acepta_marketing');
            $table->index('fecha_nacimiento');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente_detalles');
    }
};
