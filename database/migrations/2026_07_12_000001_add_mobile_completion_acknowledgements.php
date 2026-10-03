<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_routes', function (Blueprint $table): void {
            $table->timestamp('completion_acknowledged_at')->nullable()->after('completada_at')->index();
        });

        Schema::table('external_delivery_orders', function (Blueprint $table): void {
            $table->timestamp('completion_acknowledged_at')->nullable()->after('delivered_at')->index();
        });

        DB::table('delivery_routes')
            ->where('estado', 'completada')
            ->whereNull('completion_acknowledged_at')
            ->update(['completion_acknowledged_at' => DB::raw('COALESCE(completada_at, CURRENT_TIMESTAMP)')]);

        DB::table('external_delivery_orders')
            ->where('status', 'delivered')
            ->whereNull('completion_acknowledged_at')
            ->update(['completion_acknowledged_at' => DB::raw('COALESCE(delivered_at, CURRENT_TIMESTAMP)')]);
    }

    public function down(): void
    {
        Schema::table('delivery_routes', function (Blueprint $table): void {
            $table->dropColumn('completion_acknowledged_at');
        });

        Schema::table('external_delivery_orders', function (Blueprint $table): void {
            $table->dropColumn('completion_acknowledged_at');
        });
    }
};
