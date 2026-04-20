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
        Schema::table('pedidos', function (Blueprint $table): void {
            $table->foreign('dte_id')
                ->references('id')
                ->on('dte_facturas')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('dte_facturas', function (Blueprint $table): void {
            $table->foreign('pedido_id')
                ->references('id')
                ->on('pedidos')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('vendor_commissions', function (Blueprint $table): void {
            $table->foreign('dte_comision_id')
                ->references('id')
                ->on('dte_facturas')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::table('vendor_commissions', function (Blueprint $table): void {
            $table->dropForeign(['dte_comision_id']);
        });

        Schema::table('dte_facturas', function (Blueprint $table): void {
            $table->dropForeign(['pedido_id']);
        });

        Schema::table('pedidos', function (Blueprint $table): void {
            $table->dropForeign(['dte_id']);
        });
    }
};
