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
        Schema::table('pedidos', function (Blueprint $table): void {
            $table->index('created_at', 'pedidos_created_at_perf_index');
        });

        Schema::table('delivery_routes', function (Blueprint $table): void {
            $table->index(['repartidor_id', 'estado'], 'delivery_routes_repartidor_estado_perf_index');
        });

        Schema::table('productos', function (Blueprint $table): void {
            $table->index(['categoria_id', 'is_active'], 'productos_categoria_active_perf_index');
            $table->index('is_active', 'productos_active_perf_index');
        });

        Schema::table('inventarios', function (Blueprint $table): void {
            $table->index(['producto_id', 'stock_actual'], 'inventarios_producto_stock_perf_index');
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->index(['user_id', 'created_at'], 'audit_logs_user_created_perf_index');
            $table->index('created_at', 'audit_logs_created_at_perf_index');
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex('audit_logs_created_at_perf_index');
            $table->dropIndex('audit_logs_user_created_perf_index');
        });

        Schema::table('inventarios', function (Blueprint $table): void {
            $table->dropIndex('inventarios_producto_stock_perf_index');
        });

        Schema::table('productos', function (Blueprint $table): void {
            $table->dropIndex('productos_active_perf_index');
            $table->dropIndex('productos_categoria_active_perf_index');
        });

        Schema::table('delivery_routes', function (Blueprint $table): void {
            $table->dropIndex('delivery_routes_repartidor_estado_perf_index');
        });

        Schema::table('pedidos', function (Blueprint $table): void {
            $table->dropIndex('pedidos_created_at_perf_index');
        });
    }
};
