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
        Schema::create('fraud_alerts', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique()->comment('Identificador público seguro de la alerta antifraude.');
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->string('tipo', 100)->index();
            $table->decimal('score_riesgo', 8, 6)->index();
            $table->json('detalle')->nullable();
            $table->boolean('revisada')->default(false)->index();
            $table->boolean('resuelta')->default(false)->index();
            $table->foreignId('revisada_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('revisada_at')->nullable();
            $table->foreignId('modelo_version_id')->nullable()->constrained('ml_model_versions')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->index(['tipo', 'score_riesgo']);
            $table->index(['revisada', 'resuelta']);
            $table->index(['pedido_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('fraud_alerts');
    }
};
