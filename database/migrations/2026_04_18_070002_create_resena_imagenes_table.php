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
        Schema::create('resena_imagenes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('resena_id')->constrained('resenas')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('path', 500)->comment('Ruta de imagen en almacenamiento S3-compatible.');
            $table->unsignedSmallInteger('orden')->default(0)->index();
            $table->timestamps();

            $table->index(['resena_id', 'orden']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('resena_imagenes');
    }
};
