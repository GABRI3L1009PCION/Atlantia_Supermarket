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
        Schema::create('two_factor_authentications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->text('secret')->comment('Secreto TOTP cifrado con la llave de aplicación.');
            $table->text('recovery_codes')->nullable()->comment('Códigos de recuperación cifrados.');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->unsignedSmallInteger('failed_challenges')->default(0);
            $table->timestamp('locked_until')->nullable()->index();
            $table->timestamps();

            $table->index(['confirmed_at', 'locked_until']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('two_factor_authentications');
    }
};
