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
        Schema::create('restock_suggestions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger('stock_actual');
            $table->unsignedInteger('stock_sugerido');
            $table->unsignedSmallInteger('dias_hasta_quiebre')->nullable();
            $table->enum('urgencia', ['baja', 'media', 'alta', 'critica'])->default('media')->index();
            $table->boolean('aceptada')->default(false)->index();
            $table->foreignId('modelo_version_id')->nullable()->constrained('ml_model_versions')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('aceptada_at')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'urgencia']);
            $table->index(['producto_id', 'aceptada']);
            $table->index(['urgencia', 'created_at']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('restock_suggestions');
    }
};
