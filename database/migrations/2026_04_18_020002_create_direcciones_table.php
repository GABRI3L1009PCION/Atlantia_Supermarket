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
        Schema::create('direcciones', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique()->comment('Identificador publico seguro de la direccion.');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('alias', 80)->default('Casa')->comment('Nombre corto asignado por el cliente.');
            $table->string('nombre_contacto', 160)->comment('Persona que recibe el pedido.');

            $table->longText('telefono_contacto')->comment('Telefono de entrega cifrado a nivel de modelo.');

            $table->enum('municipio', [
                'Puerto Barrios',
                'Santo Tomas',
                'Morales',
                'Los Amates',
                'Livingston',
                'El Estor',
            ])->index();

            $table->string('zona_o_barrio', 160)->nullable()->index();
            $table->longText('direccion_linea_1')->comment('Direccion principal cifrada a nivel de modelo.');
            $table->longText('direccion_linea_2')->nullable()->comment('Complemento de direccion cifrado a nivel de modelo.');
            $table->longText('referencia')->nullable()->comment('Referencia de entrega cifrada a nivel de modelo.');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('mapbox_place_id', 255)->nullable()->index();
            $table->boolean('es_principal')->default(false)->index();
            $table->boolean('activa')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'es_principal']);
            $table->index(['municipio', 'activa']);
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('direcciones');
    }
};
