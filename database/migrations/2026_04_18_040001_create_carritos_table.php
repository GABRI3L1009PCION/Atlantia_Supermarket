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
        Schema::create('carritos', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique()->comment('Identificador público del carrito.');
            $table->foreignId('user_id')->nullable()->index()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('session_id', 120)->nullable()->index()->comment('Sesión de carrito para visitantes no autenticados.');
            $table->enum('estado', ['activo', 'convertido', 'abandonado', 'expirado'])->default('activo')->index();
            $table->timestamp('expira_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'estado']);
            $table->index(['session_id', 'estado']);
            $table->index(['estado', 'expira_at']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('carritos');
    }
};
