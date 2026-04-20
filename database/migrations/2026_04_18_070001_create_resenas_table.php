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
        Schema::create('resenas', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique()->comment('Identificador público seguro de la reseña.');
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('cliente_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('pedido_id')->constrained('pedidos')->restrictOnDelete()->cascadeOnUpdate();
            $table->unsignedTinyInteger('calificacion')->comment('Calificación de 1 a 5 estrellas.');
            $table->string('titulo', 140)->nullable();
            $table->text('contenido')->nullable();
            $table->unsignedSmallInteger('imagenes_count')->default(0);
            $table->boolean('aprobada')->default(false)->index();
            $table->boolean('flagged_ml')->default(false)->index();
            $table->foreignId('moderada_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('moderada_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['producto_id', 'cliente_id', 'pedido_id']);
            $table->index(['producto_id', 'aprobada']);
            $table->index(['cliente_id', 'created_at']);
            $table->index(['flagged_ml', 'aprobada']);
            $table->index(['calificacion', 'created_at']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('resenas');
    }
};
