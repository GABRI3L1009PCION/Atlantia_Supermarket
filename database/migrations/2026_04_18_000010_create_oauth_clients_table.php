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
        Schema::create('oauth_clients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->index()->comment('Usuario dueno del cliente OAuth.');
            $table->string('name');
            $table->string('secret', 100)->nullable();
            $table->string('provider')->nullable();
            $table->text('redirect');
            $table->boolean('personal_access_client')->default(false)->index();
            $table->boolean('password_client')->default(false)->index();
            $table->boolean('revoked')->default(false)->index();
            $table->timestamps();
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_clients');
    }
};

