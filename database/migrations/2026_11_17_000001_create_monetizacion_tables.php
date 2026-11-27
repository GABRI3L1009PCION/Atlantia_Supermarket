<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notificaciones push, servicios pagados (planes, banners, destacados, patrocinados)
     * y metricas de publicidad.
     */
    public function up(): void
    {
        Schema::create('push_suscripciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->text('endpoint');
            $table->char('endpoint_hash', 64)->unique();
            $table->string('p256dh', 255);
            $table->string('auth', 255);
            $table->boolean('ofertas')->default(true)->comment('Acepta avisos de productos nuevos y ofertas.');
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('ultimo_envio_at')->nullable();
            $table->timestamps();
        });

        Schema::create('servicios_contratados', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('tipo', ['plan', 'banner', 'destacado', 'patrocinado']);
            $table->nullableMorphs('referencia');
            $table->string('anunciante', 160)->nullable()->comment('Empresa externa que paga publicidad (sin tienda en Atlantia).');
            $table->json('detalle')->nullable();
            $table->decimal('monto', 10, 2);
            $table->timestamp('inicia_at')->nullable();
            $table->timestamp('termina_at')->nullable();
            $table->enum('estado', ['pendiente_pago', 'en_revision', 'activo', 'vencido', 'cancelado', 'rechazado'])->default('pendiente_pago')->index();
            $table->enum('metodo_pago', ['recurrente', 'manual'])->default('recurrente');
            $table->string('recurrente_checkout_id', 64)->nullable();
            $table->timestamp('pagado_at')->nullable();
            $table->unsignedInteger('vistas')->default(0);
            $table->unsignedInteger('clics')->default(0);
            $table->timestamps();

            $table->index(['tipo', 'estado', 'inicia_at', 'termina_at']);
        });

        Schema::table('hero_banners', function (Blueprint $table): void {
            $table->string('anunciante', 160)->nullable()->after('nombre');
            $table->string('url_destino', 500)->nullable()->after('anunciante');
            $table->unsignedInteger('vistas')->default(0)->after('termina_en');
            $table->unsignedInteger('clics')->default(0)->after('vistas');
        });

        Schema::table('vendors', function (Blueprint $table): void {
            $table->string('plan', 20)->default('emprende')->after('monthly_rent');
            $table->timestamp('plan_hasta')->nullable()->after('plan');
            $table->string('plan_suscripcion_id', 64)->nullable()->after('plan_hasta');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->dropColumn(['plan', 'plan_hasta', 'plan_suscripcion_id']);
        });
        Schema::table('hero_banners', function (Blueprint $table): void {
            $table->dropColumn(['anunciante', 'url_destino', 'vistas', 'clics']);
        });
        Schema::dropIfExists('servicios_contratados');
        Schema::dropIfExists('push_suscripciones');
    }
};
