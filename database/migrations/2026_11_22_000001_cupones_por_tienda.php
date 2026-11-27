<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cada cupón pertenece a una tienda: solo descuenta productos de esa tienda y lo paga esa tienda.
 * Los cupones que ya existían eran de la administración, así que pasan a Atlantia Store.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cupones', function (Blueprint $table): void {
            $table->foreignId('vendor_id')->nullable()->after('uuid')->constrained('vendors')->cascadeOnDelete();
        });

        $atlantia = DB::table('vendors')->where('slug', config('atlantia.vendor_propio_slug', 'atlantia-supermarket'))->value('id');
        if ($atlantia !== null) {
            DB::table('cupones')->whereNull('vendor_id')->update(['vendor_id' => $atlantia]);
        }
    }

    public function down(): void
    {
        Schema::table('cupones', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('vendor_id');
        });
    }
};
