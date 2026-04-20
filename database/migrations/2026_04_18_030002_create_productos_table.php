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
        Schema::create('productos', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique()->comment('Identificador público seguro del producto.');
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('sku', 80)->comment('Código interno único por vendedor.');
            $table->string('nombre', 180)->index();
            $table->string('slug', 190)->index();
            $table->text('descripcion')->nullable();
            $table->decimal('precio_base', 12, 2);
            $table->decimal('precio_oferta', 12, 2)->nullable();
            $table->unsignedInteger('peso_gramos')->nullable();
            $table->string('unidad_medida', 40)->default('unidad');
            $table->boolean('requiere_refrigeracion')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('visible_catalogo')->default(true)->index();
            $table->timestamp('publicado_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['vendor_id', 'sku']);
            $table->unique(['vendor_id', 'slug']);
            $table->index(['vendor_id', 'is_active']);
            $table->index(['vendor_id', 'visible_catalogo']);
            $table->index(['categoria_id', 'visible_catalogo']);
            $table->index(['precio_base', 'precio_oferta']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
