<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nuevos planes de local virtual: socio (sin renta), puesto, local y ancla.
     */
    public function up(): void
    {
        DB::table('vendors')->where('plan', 'emprende')->update(['plan' => 'socio']);
        DB::table('vendors')->where('plan', 'crece')->update(['plan' => 'puesto']);
        DB::table('vendors')->where('plan', 'pro')->update(['plan' => 'local']);

        Schema::table('vendors', function (Blueprint $table): void {
            $table->string('plan', 20)->default('socio')->change();
        });
    }

    public function down(): void
    {
        DB::table('vendors')->where('plan', 'socio')->update(['plan' => 'emprende']);

        Schema::table('vendors', function (Blueprint $table): void {
            $table->string('plan', 20)->default('emprende')->change();
        });
    }
};
