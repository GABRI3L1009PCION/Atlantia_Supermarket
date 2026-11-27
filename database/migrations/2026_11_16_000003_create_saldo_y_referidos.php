<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Propina al repartidor, saldo a favor (monedero) y programa de referidos.
     */
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table): void {
            $table->decimal('propina', 10, 2)->default(0)->after('total')
                ->comment('Propina para el repartidor; no forma parte de la venta del comercio.');
            $table->decimal('saldo_aplicado', 10, 2)->default(0)->after('propina')
                ->comment('Parte del cobro pagada con saldo a favor del cliente.');
        });

        Schema::create('saldo_movimientos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $table->decimal('monto', 10, 2)->comment('Positivo acredita, negativo descuenta.');
            $table->string('motivo', 40)->index();
            $table->string('descripcion', 255);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('codigo_referido', 16)->nullable()->unique()->after('google_id');
            $table->foreignId('referido_por_id')->nullable()->after('codigo_referido')->constrained('users')->nullOnDelete();
            $table->timestamp('referido_recompensado_at')->nullable()->after('referido_por_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('referido_por_id');
            $table->dropUnique(['codigo_referido']);
            $table->dropColumn(['codigo_referido', 'referido_recompensado_at']);
        });

        Schema::dropIfExists('saldo_movimientos');

        Schema::table('pedidos', function (Blueprint $table): void {
            $table->dropColumn(['propina', 'saldo_aplicado']);
        });
    }
};
