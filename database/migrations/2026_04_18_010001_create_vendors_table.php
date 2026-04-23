<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique()->comment('Identificador publico del vendedor para URLs seguras.');
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->string('business_name', 180)->index()->comment('Nombre comercial visible para clientes.');
            $table->string('slug', 190)->unique();
            $table->text('descripcion')->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->string('cover_path', 500)->nullable();
            $table->string('telefono_publico', 30)->nullable();
            $table->string('email_publico', 190)->nullable();
            $table->enum('municipio', [
                'Puerto Barrios',
                'Santo Tomas',
                'Morales',
                'Los Amates',
                'Livingston',
                'El Estor',
            ])->index();
            $table->string('direccion_comercial', 500);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_approved')->default(false)->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('suspendido_at')->nullable()->index();
            $table->foreignId('suspendido_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->string('motivo_suspension', 500)->nullable();
            $table->enum('status', ['pending', 'approved', 'suspended', 'rejected'])->default('pending')->index();
            $table->decimal('commission_percentage', 5, 2)->default(0.00)->comment(
                'Porcentaje comercial vigente aplicado por Atlantia.'
            );
            $table->decimal('monthly_rent', 12, 2)->default(0.00)->comment(
                'Renta mensual fija cobrada al vendedor.'
            );
            $table->boolean('accepts_cash')->default(true);
            $table->boolean('accepts_transfer')->default(true);
            $table->boolean('accepts_card')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_approved']);
            $table->index(['municipio', 'status']);
            $table->index(['created_at', 'status']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
