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
        Schema::create('ml_prediction_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('endpoint', 180)->index();
            $table->json('input');
            $table->json('output')->nullable();
            $table->unsignedInteger('latencia_ms')->nullable()->index();
            $table->foreignId('modelo_version_id')->nullable()->constrained('ml_model_versions')->nullOnDelete()->cascadeOnUpdate();
            $table->enum('estado', ['success', 'failed'])->default('success')->index();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['endpoint', 'created_at']);
            $table->index(['estado', 'created_at']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('ml_prediction_logs');
    }
};
