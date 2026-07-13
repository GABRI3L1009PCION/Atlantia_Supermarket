<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_delivery_orders', function (Blueprint $table): void {
            $table->json('real_path')->nullable()->after('cash_issue_notes');
        });

        Schema::table('market_courier_statuses', function (Blueprint $table): void {
            $table->foreignId('external_delivery_order_id')
                ->nullable()
                ->after('pedido_id')
                ->constrained('external_delivery_orders')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->index(['external_delivery_order_id', 'timestamp_gps'], 'courier_external_order_gps_index');
        });
    }

    public function down(): void
    {
        Schema::table('market_courier_statuses', function (Blueprint $table): void {
            $table->dropIndex('courier_external_order_gps_index');
            $table->dropConstrainedForeignId('external_delivery_order_id');
        });

        Schema::table('external_delivery_orders', function (Blueprint $table): void {
            $table->dropColumn('real_path');
        });
    }
};
