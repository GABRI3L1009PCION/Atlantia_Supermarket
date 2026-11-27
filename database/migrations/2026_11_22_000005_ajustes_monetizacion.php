<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Precios de planes y publicidad que el administrador cambia desde el panel.
 * Lo guardado aquí se aplica encima de config/monetizacion.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajustes_monetizacion', function (Blueprint $table): void {
            $table->id();
            $table->string('clave', 60)->unique()->comment('planes o publicidad.');
            $table->json('valor');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes_monetizacion');
    }
};
