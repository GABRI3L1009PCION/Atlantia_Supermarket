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
        Schema::create('sales_predictions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->date('fecha_prediccion')->index();
            $table->unsignedSmallInteger('horizonte_dias')->comment('Horizonte de predicción en días: 7, 14 o 30.');
            $table->decimal('valor_predicho', 12, 2);
            $table->decimal('valor_real', 12, 2)->nullable();
            $table->decimal('intervalo_inferior', 12, 2)->nullable();
            $table->decimal('intervalo_superior', 12, 2)->nullable();
            $table->foreignId('modelo_version_id')->nullable()->constrained('ml_model_versions')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->unique(
                ['producto_id', 'fecha_prediccion', 'horizonte_dias', 'modelo_version_id'],
                'sales_predictions_unique_model_forecast'
            );
            $table->index(['vendor_id', 'fecha_prediccion']);
            $table->index(['producto_id', 'horizonte_dias']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_predictions');
    }
};
