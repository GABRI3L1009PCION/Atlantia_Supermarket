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
        Schema::create('puntos_cliente', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger('puntos_actuales')->default(0);
            $table->unsignedInteger('puntos_totales_ganados')->default(0);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('puntos_cliente');
    }
};
