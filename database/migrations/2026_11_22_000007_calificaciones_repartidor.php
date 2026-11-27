<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El cliente califica al repartidor que le entregó el pedido (una vez por pedido).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calificaciones_repartidor', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pedido_id')->unique()->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('repartidor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('estrellas');
            $table->string('comentario', 300)->nullable();
            $table->timestamps();

            $table->index(['repartidor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificaciones_repartidor');
    }
};
