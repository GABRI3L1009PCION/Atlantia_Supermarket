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
        Schema::create('ml_model_versions', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique()->comment('Identificador público seguro de la versión del modelo.');
            $table->string('nombre_modelo', 140)->index();
            $table->string('version', 80)->index();
            $table->string('ruta_artefacto', 600)->comment('Ruta del artefacto registrado en MLflow o almacenamiento S3-compatible.');
            $table->json('metricas')->nullable()->comment('Métricas principales serializadas del modelo.');
            $table->timestamp('fecha_entrenamiento')->nullable()->index();
            $table->timestamp('fecha_deploy')->nullable()->index();
            $table->enum('estado', ['training', 'staging', 'production', 'archived'])->default('training')->index();
            $table->foreignId('entrenado_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->unique(['nombre_modelo', 'version']);
            $table->index(['nombre_modelo', 'estado']);
            $table->index(['estado', 'fecha_deploy']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('ml_model_versions');
    }
};
