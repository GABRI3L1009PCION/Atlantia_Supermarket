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
        Schema::create('categorias', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categorias')->nullOnDelete()->cascadeOnUpdate();
            $table->string('nombre', 140)->index();
            $table->string('slug', 190)->unique();
            $table->string('descripcion', 500)->nullable();
            $table->string('icon', 120)->nullable();
            $table->unsignedSmallInteger('orden')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'is_active']);
            $table->index(['is_active', 'orden']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
