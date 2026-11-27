<?php

use App\Models\PaymentSplit;
use App\Models\Pedido;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pagos a vendedores: cada venta (split) guarda su pedido, la tarifa de la pasarela de esa
 * transaccion y la solicitud de pago (liquidacion) en la que se le pago al vendedor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liquidaciones_vendedor', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('numero', 30)->unique();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger('ventas')->default(0);
            $table->decimal('monto_bruto', 12, 2)->default(0);
            $table->decimal('comision_plataforma', 12, 2)->default(0);
            $table->decimal('comision_pasarela', 12, 2)->default(0);
            $table->decimal('monto_neto', 12, 2)->default(0);
            $table->string('estado', 20)->default('solicitada')->index();
            $table->string('banco_nombre', 120)->nullable();
            $table->string('cuenta_titular', 180)->nullable();
            $table->string('referencia_pago', 120)->nullable();
            $table->text('notas')->nullable();
            $table->timestamp('solicitada_at')->nullable();
            $table->timestamp('pagada_at')->nullable();
            $table->foreignId('pagada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vendor_id', 'estado']);
        });

        Schema::table('payment_splits', function (Blueprint $table): void {
            $table->foreignId('pedido_id')->nullable()->after('vendor_id')->constrained('pedidos')->nullOnDelete();
            $table->decimal('comision_pasarela', 12, 2)->default(0)->after('comision_atlantia');
            $table->foreignId('liquidacion_id')->nullable()->after('estado')->constrained('liquidaciones_vendedor')->nullOnDelete();
        });

        /* Une cada venta ya registrada con el pedido de su tienda. */
        PaymentSplit::query()->with('payment.pedido')->whereNull('pedido_id')->each(function (PaymentSplit $split): void {
            $padre = $split->payment?->pedido;
            if ($padre === null) {
                return;
            }

            $hijo = Pedido::query()->where('pedido_padre_id', $padre->id)->where('vendor_id', $split->vendor_id)->value('id');
            $split->forceFill(['pedido_id' => $hijo ?? $padre->id])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('payment_splits', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('liquidacion_id');
            $table->dropColumn('comision_pasarela');
            $table->dropConstrainedForeignId('pedido_id');
        });

        Schema::dropIfExists('liquidaciones_vendedor');
    }
};
