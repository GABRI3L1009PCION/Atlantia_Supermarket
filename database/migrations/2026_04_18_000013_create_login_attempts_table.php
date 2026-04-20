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
        Schema::create('login_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->index()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->string('email', 190)->index();
            $table->string('ip_address', 45)->index();
            $table->text('user_agent')->nullable();
            $table->boolean('successful')->default(false)->index();
            $table->string('failure_reason')->nullable()->comment('Motivo técnico del rechazo de autenticación.');
            $table->timestamp('attempted_at')->index();
            $table->timestamps();

            $table->index(['email', 'ip_address', 'attempted_at']);
            $table->index(['successful', 'attempted_at']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
