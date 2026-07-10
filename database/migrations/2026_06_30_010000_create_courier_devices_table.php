<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de dispositivos moviles de repartidores.
     */
    public function up(): void
    {
        Schema::create('courier_devices', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('platform', 30)->default('android');
            $table->string('device_uuid', 120);
            $table->string('device_name')->nullable();
            $table->string('app_version', 40)->nullable();
            $table->string('push_provider', 30)->nullable();
            $table->text('push_token')->nullable();
            $table->boolean('notifications_enabled')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_uuid']);
            $table->index(['user_id', 'platform']);
            $table->index('last_seen_at');
        });
    }

    /**
     * Elimina la tabla de dispositivos moviles.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_devices');
    }
};
