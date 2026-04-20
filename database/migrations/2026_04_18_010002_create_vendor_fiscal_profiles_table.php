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
        Schema::create('vendor_fiscal_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vendor_id')->unique()->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->string('nit', 30)->index()->comment('NIT registrado ante SAT Guatemala.');
            $table->string('razon_social', 220)->index();
            $table->string('nombre_comercial_sat', 220)->nullable();

            // Campos cifrados o potencialmente largos
            $table->text('direccion_fiscal')->comment('Dirección fiscal cifrada a nivel de modelo.');

            $table->enum('regimen_sat', [
                'pequeno_contribuyente',
                'general',
                'exento',
            ])->index();

            $table->string('codigo_establecimiento', 50)->comment('Código de establecimiento registrado ante SAT.');
            $table->string('afiliacion_iva', 80)->default('GEN')->comment('Afiliación IVA requerida para DTE FEL.');
            $table->enum('certificador_fel', ['infile'])->default('infile');

            $table->longText('fel_usuario')->nullable()->comment('Usuario del certificador FEL cifrado a nivel de modelo.');
            $table->longText('fel_llave_firma')->nullable()->comment('Llave o token FEL cifrado; nunca se guarda en texto plano.');
            $table->longText('fel_llave_certificador')->nullable()->comment('Credencial del certificador cifrada.');

            $table->string('banco_nombre', 120)->nullable();
            $table->text('cuenta_bancaria')->nullable()->comment('Cuenta bancaria cifrada a nivel de modelo.');
            $table->string('cuenta_bancaria_tipo', 40)->nullable();
            $table->string('cuenta_bancaria_titular', 180)->nullable();

            $table->boolean('fel_activo')->default(false)->index();
            $table->timestamp('fel_validado_at')->nullable();
            $table->foreignId('fel_validado_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->unique(['nit', 'codigo_establecimiento']);
            $table->index(['regimen_sat', 'fel_activo']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_fiscal_profiles');
    }
};
