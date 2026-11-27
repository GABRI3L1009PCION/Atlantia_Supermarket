<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - Aprobación automática de tiendas que cumplen los requisitos (y por qué quedó pendiente si no).
 * - Retención de pagos (tiendas en revisión). La frecuencia de pago ya existe en vendors.payment_frequency.
 * - Plazo para que Atlantia transfiera cada pago.
 * - Denuncias de clientes contra tiendas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->timestamp('pagos_retenidos_at')->nullable()->after('payment_frequency')
                ->comment('Si tiene valor, no se le paga hasta que Atlantia revise (por ejemplo, por denuncias).');
            $table->string('motivo_retencion', 255)->nullable()->after('pagos_retenidos_at');
            $table->json('revision_automatica')->nullable()->after('motivo_retencion')
                ->comment('Resultado de la revisión automática de la solicitud: aprobada y motivos.');
        });

        /* La frecuencia de pago ya se guarda en payment_frequency (la elige al registrarse); sin valor = semanal. */
        DB::table('vendors')->whereNull('payment_frequency')->update(['payment_frequency' => 'semanal']);

        Schema::table('liquidaciones_vendedor', function (Blueprint $table): void {
            $table->boolean('automatica')->default(false)->after('estado')->comment('La creó el sistema según la frecuencia de la tienda.');
            $table->timestamp('pagar_antes_de')->nullable()->after('automatica')->index()->comment('Plazo para que Atlantia transfiera.');
        });

        Schema::create('denuncias_tienda', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $table->string('motivo', 30)->index();
            $table->text('descripcion');
            $table->string('estado', 20)->default('pendiente')->index()->comment('pendiente, confirmada o descartada.');
            $table->text('resolucion')->nullable();
            $table->foreignId('revisada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revisada_at')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('denuncias_tienda');
        Schema::table('liquidaciones_vendedor', function (Blueprint $table): void {
            $table->dropIndex(['pagar_antes_de']);
            $table->dropColumn(['automatica', 'pagar_antes_de']);
        });
        Schema::table('vendors', function (Blueprint $table): void {
            $table->dropColumn(['pagos_retenidos_at', 'motivo_retencion', 'revision_automatica']);
        });
    }
};
