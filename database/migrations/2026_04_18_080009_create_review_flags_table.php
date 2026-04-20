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
        Schema::create('review_flags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('resena_id')->constrained('resenas')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('razon_ml', 160)->index();
            $table->decimal('score_sospecha', 8, 6)->index();
            $table->boolean('revisada')->default(false)->index();
            $table->enum('accion_tomada', ['ninguna', 'aprobada', 'ocultada', 'eliminada'])
                ->default('ninguna')
                ->index();
            $table->foreignId('revisada_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('revisada_at')->nullable();
            $table->foreignId('modelo_version_id')->nullable()->constrained('ml_model_versions')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->index(['resena_id', 'revisada']);
            $table->index(['revisada', 'accion_tomada']);
            $table->index(['score_sospecha', 'created_at']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_flags');
    }
};
