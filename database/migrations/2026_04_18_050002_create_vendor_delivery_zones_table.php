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
        Schema::create('vendor_delivery_zones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('delivery_zone_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->decimal('costo_override', 12, 2)->nullable()->comment('Costo especial del vendedor para esta zona.');
            $table->unsignedSmallInteger('tiempo_estimado_min')->nullable();
            $table->boolean('activa')->default(true)->index();
            $table->timestamps();

            $table->unique(['vendor_id', 'delivery_zone_id']);
            $table->index(['delivery_zone_id', 'activa']);
            $table->index(['vendor_id', 'activa']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_delivery_zones');
    }
};
