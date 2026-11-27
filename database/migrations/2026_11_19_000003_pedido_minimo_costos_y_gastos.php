<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rentabilidad: pedido minimo por tienda, costo de compra de los productos y gastos fijos de Atlantia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->decimal('pedido_minimo', 10, 2)->nullable()->after('tiempo_preparacion_min');
        });

        Schema::table('productos', function (Blueprint $table): void {
            $table->decimal('costo_compra', 12, 2)->nullable()->after('precio_oferta');
        });

        Schema::create('gastos_fijos', function (Blueprint $table): void {
            $table->id();
            $table->date('mes')->index();
            $table->string('categoria', 40);
            $table->string('concepto', 160);
            $table->decimal('monto', 12, 2);
            $table->string('notas', 255)->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        /* Atlantia Store: pedido minimo de Q50 para no procesar tarjetas en compras muy pequenas. */
        DB::table('vendors')->where('slug', 'atlantia-supermarket')->update(['pedido_minimo' => 50]);
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos_fijos');
        Schema::table('productos', fn (Blueprint $table) => $table->dropColumn('costo_compra'));
        Schema::table('vendors', fn (Blueprint $table) => $table->dropColumn('pedido_minimo'));
    }
};
