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
        Schema::create('empleados', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique()->comment('Identificador público del empleado interno.');
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->string('codigo_empleado', 40)->unique();
            $table->enum('departamento', [
                'administracion',
                'operaciones',
                'soporte_cliente',
                'finanzas',
                'logistica',
                'moderacion',
            ])->index();
            $table->string('puesto', 120)->index();
            $table->string('telefono_interno', 30)->nullable();
            $table->date('fecha_contratacion');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->index();
            $table->foreignId('supervisor_id')->nullable()->constrained('empleados')->nullOnDelete()->cascadeOnUpdate();
            $table->json('permisos_operativos')->nullable()->comment('Permisos finos de operación no cubiertos por RBAC.');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['departamento', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
