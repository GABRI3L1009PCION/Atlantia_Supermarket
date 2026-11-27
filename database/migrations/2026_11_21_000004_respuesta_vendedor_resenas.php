<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El vendedor puede responder públicamente cada reseña publicada de sus productos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resenas', function (Blueprint $table): void {
            $table->text('respuesta_vendedor')->nullable()->after('contenido');
            $table->timestamp('respondida_at')->nullable()->after('respuesta_vendedor');
        });
    }

    public function down(): void
    {
        Schema::table('resenas', function (Blueprint $table): void {
            $table->dropColumn(['respuesta_vendedor', 'respondida_at']);
        });
    }
};
