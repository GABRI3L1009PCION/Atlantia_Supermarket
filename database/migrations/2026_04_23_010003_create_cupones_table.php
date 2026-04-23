<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('cupones', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique()->comment('Identificador publico del cupon.');
            $table->string('codigo', 60)->unique()->comment('Codigo ingresado por el cliente.');
            $table->enum('tipo', ['porcentaje', 'monto_fijo'])->index();
            $table->decimal('valor', 12, 2);
            $table->decimal('minimo_compra', 12, 2)->default(0);
            $table->decimal('maximo_descuento', 12, 2)->nullable();
            $table->unsignedInteger('usos_maximos')->nullable();
            $table->unsignedInteger('usos_actuales')->default(0);
            $table->timestamp('fecha_inicio')->nullable()->index();
            $table->timestamp('fecha_fin')->nullable()->index();
            $table->boolean('activo')->default(true)->index();
            $table->boolean('solo_primera_compra')->default(false);
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->index(['activo', 'fecha_inicio', 'fecha_fin']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('cupones');
    }
};
