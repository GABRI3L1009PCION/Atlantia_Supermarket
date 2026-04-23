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
        Schema::create('devoluciones', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique()->comment('Identificador publico de la devolucion.');
            $table->foreignId('pedido_id')->constrained('pedidos')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->enum('motivo', ['producto_defectuoso', 'no_llego', 'incorrecto', 'otro'])->index();
            $table->enum('estado', ['solicitada', 'aprobada', 'rechazada', 'completada'])
                ->default('solicitada')
                ->index();
            $table->decimal('monto_reembolso', 12, 2)->default(0);
            $table->text('descripcion')->nullable();
            $table->text('notas_admin')->nullable();
            $table->string('foto_evidencia', 500)->nullable();
            $table->foreignId('resuelta_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('resuelta_at')->nullable();
            $table->timestamps();

            $table->index(['pedido_id', 'estado']);
            $table->index(['user_id', 'estado']);
            $table->index(['estado', 'created_at']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('devoluciones');
    }
};
