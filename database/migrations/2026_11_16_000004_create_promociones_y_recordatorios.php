<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recordatorio de carrito abandonado, promociones automaticas y pedidos recurrentes.
     */
    public function up(): void
    {
        Schema::table('carritos', function (Blueprint $table): void {
            $table->timestamp('recordatorio_enviado_at')->nullable()->after('expira_at');
        });

        Schema::create('promociones', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->cascadeOnDelete()
                ->comment('Null = promocion de toda la plataforma (solo administracion).');
            $table->string('nombre', 120);
            /* nxm: lleva N paga M (2x1, 3x2). porcentaje: oferta relampago o descuento por porcentaje. */
            $table->enum('tipo', ['nxm', 'porcentaje']);
            $table->unsignedTinyInteger('lleva')->nullable();
            $table->unsignedTinyInteger('paga')->nullable();
            $table->decimal('porcentaje', 5, 2)->nullable();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->cascadeOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->cascadeOnDelete();
            $table->timestamp('inicia_at')->nullable();
            $table->timestamp('termina_at')->nullable();
            $table->time('hora_inicio')->nullable()->comment('Franja diaria para ofertas relampago.');
            $table->time('hora_fin')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->index(['activa', 'inicia_at', 'termina_at']);
        });

        Schema::table('pedidos', function (Blueprint $table): void {
            $table->decimal('descuento_promociones', 10, 2)->default(0)->after('descuento');
        });

        Schema::create('pedidos_recurrentes', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('pedido_origen_id')->constrained('pedidos')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana')->comment('0 = domingo ... 6 = sabado');
            $table->time('hora_aviso')->default('08:00:00');
            $table->boolean('activo')->default(true);
            $table->timestamp('ultimo_aviso_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos_recurrentes');
        Schema::table('pedidos', function (Blueprint $table): void {
            $table->dropColumn('descuento_promociones');
        });
        Schema::dropIfExists('promociones');
        Schema::table('carritos', function (Blueprint $table): void {
            $table->dropColumn('recordatorio_enviado_at');
        });
    }
};
