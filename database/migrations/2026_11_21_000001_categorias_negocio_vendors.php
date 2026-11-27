<?php

use App\Support\CategoriasNegocio;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Una tienda puede tener hasta 3 categorías de negocio (antes solo una, como texto).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->json('categorias_negocio')->nullable()->after('business_category');
        });

        DB::table('vendors')->whereNotNull('business_category')->orderBy('id')->each(function (object $vendor): void {
            $clave = CategoriasNegocio::claveDe($vendor->business_category);

            if ($clave !== null) {
                DB::table('vendors')->where('id', $vendor->id)->update([
                    'categorias_negocio' => json_encode([$clave]),
                    'business_category' => $clave,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->dropColumn('categorias_negocio');
        });
    }
};
