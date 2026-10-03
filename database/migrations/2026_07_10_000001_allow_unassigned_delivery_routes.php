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
            $table->dropForeign(['repartidor_id']);
        });

        Schema::table('delivery_routes', function (Blueprint $table): void {
            $table->unsignedBigInteger('repartidor_id')->nullable()->change();
            $table->foreign('repartidor_id')->references('id')->on('users')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        DB::table('delivery_routes')->whereNull('repartidor_id')->delete();

        Schema::table('delivery_routes', function (Blueprint $table): void {
            $table->dropForeign(['repartidor_id']);
        });

        Schema::table('delivery_routes', function (Blueprint $table): void {
            $table->unsignedBigInteger('repartidor_id')->nullable(false)->change();
            $table->foreign('repartidor_id')->references('id')->on('users')->restrictOnDelete()->cascadeOnUpdate();
        });
    }
};
