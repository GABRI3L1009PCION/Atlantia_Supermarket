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
        Schema::create('wishlists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->unique(['user_id', 'producto_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
