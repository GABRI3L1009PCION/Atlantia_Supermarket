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
        Schema::create('ml_training_jobs', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique()->comment('Identificador público seguro del job de entrenamiento.');
            $table->string('modelo_nombre', 140)->index();
            $table->foreignId('modelo_version_id')->nullable()->constrained('ml_model_versions')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('inicio_at')->nullable()->index();
            $table->timestamp('fin_at')->nullable()->index();
            $table->enum('estado', ['queued', 'running', 'completed', 'failed', 'cancelled'])->default('queued')->index();
            $table->json('metricas_finales')->nullable();
            $table->unsignedInteger('dataset_size')->nullable();
            $table->longText('error_log')->nullable();
            $table->timestamps();

            $table->index(['modelo_nombre', 'estado']);
            $table->index(['estado', 'created_at']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('ml_training_jobs');
    }
};
