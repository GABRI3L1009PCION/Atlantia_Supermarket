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
        Schema::create('oauth_auth_codes', function (Blueprint $table): void {
            $table->string('id', 100)->primary();
            $table->foreignId('user_id')->index()->comment('Usuario propietario del codigo OAuth.');
            $table->string('client_id', 100)->index()->comment('Cliente OAuth que solicito el codigo.');
            $table->text('scopes')->nullable()->comment('Scopes autorizados serializados por Passport.');
            $table->boolean('revoked')->default(false)->index();
            $table->dateTime('expires_at')->nullable()->index();
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_auth_codes');
    }
};
