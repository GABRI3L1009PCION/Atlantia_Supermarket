<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table): void {
            $table->decimal('salario_base', 12, 2)->default(0)->after('puesto');
        });

        Schema::create('nominas', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->date('periodo_inicio')->index();
            $table->date('periodo_fin')->index();
            $table->enum('tipo_periodo', ['mensual', 'quincenal', 'extraordinaria'])->default('mensual');
            $table->enum('estado', ['borrador', 'pagada', 'anulada'])->default('borrador')->index();
            $table->decimal('total_bruto', 14, 2)->default(0);
            $table->decimal('total_bonificaciones', 14, 2)->default(0);
            $table->decimal('total_descuentos', 14, 2)->default(0);
            $table->decimal('total_neto', 14, 2)->default(0);
            $table->foreignId('generada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('pagada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('pagada_at')->nullable();
            $table->string('notas', 500)->nullable();
            $table->timestamps();

            $table->unique(['periodo_inicio', 'periodo_fin', 'tipo_periodo']);
        });

        Schema::create('nomina_detalles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nomina_id')->constrained('nominas')->cascadeOnDelete();
            $table->foreignId('empleado_id')->constrained('empleados')->restrictOnDelete();
            $table->decimal('salario_base', 12, 2);
            $table->decimal('bonificaciones', 12, 2)->default(0);
            $table->decimal('descuentos', 12, 2)->default(0);
            $table->decimal('total_neto', 12, 2);
            $table->string('observaciones', 500)->nullable();
            $table->timestamps();

            $table->unique(['nomina_id', 'empleado_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_detalles');
        Schema::dropIfExists('nominas');

        Schema::table('empleados', function (Blueprint $table): void {
            $table->dropColumn('salario_base');
        });
    }
};
