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
        Schema::create('product_recommendations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cliente_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->decimal('score', 8, 6)->comment('Puntaje de relevancia calculado por el motor de recomendaciones.');
            $table->string('algoritmo', 80)->index();
            $table->unsignedSmallInteger('posicion')->index();
            $table->foreignId('modelo_version_id')->nullable()->constrained('ml_model_versions')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->unique(['cliente_id', 'producto_id', 'algoritmo']);
            $table->index(['cliente_id', 'posicion']);
            $table->index(['producto_id', 'score']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_recommendations');
    }
};
