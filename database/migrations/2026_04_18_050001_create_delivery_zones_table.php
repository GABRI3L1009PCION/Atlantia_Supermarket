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
        Schema::create('delivery_zones', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique()->comment('Identificador publico seguro de la zona de entrega.');
            $table->string('nombre', 140)->unique();
            $table->string('slug', 190)->unique();
            $table->text('descripcion')->nullable();
            $table->enum('municipio', [
                'Puerto Barrios',
                'Santo Tomas',
                'Morales',
                'Los Amates',
                'Livingston',
                'El Estor',
            ])->index();
            $table->decimal('costo_base', 12, 2)->default(0.00);
            $table->decimal('latitude_centro', 10, 8)->nullable();
            $table->decimal('longitude_centro', 11, 8)->nullable();
            $table->json('poligono_geojson')->nullable()->comment('Poligono de cobertura compatible con Mapbox.');
            $table->boolean('activa')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['municipio', 'activa']);
            $table->index(['activa', 'costo_base']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};
