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
        Schema::create('ml_metrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('modelo_version_id')->constrained('ml_model_versions')->cascadeOnDelete()->cascadeOnUpdate();
            $table->date('fecha')->index();
            $table->decimal('mape', 8, 4)->nullable();
            $table->decimal('rmse', 12, 4)->nullable();
            $table->decimal('r2', 8, 4)->nullable();
            $table->decimal('drift_score', 8, 4)->nullable();
            $table->timestamps();

            $table->unique(['modelo_version_id', 'fecha']);
            $table->index(['fecha', 'drift_score']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('ml_metrics');
    }
};
