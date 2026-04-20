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
        Schema::create('producto_imagenes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('path', 500)->comment('Ruta del archivo en almacenamiento S3-compatible.');
            $table->string('alt_text', 180)->nullable();
            $table->unsignedSmallInteger('orden')->default(0)->index();
            $table->boolean('es_principal')->default(false)->index();
            $table->timestamps();

            $table->index(['producto_id', 'orden']);
            $table->index(['producto_id', 'es_principal']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_imagenes');
    }
};
