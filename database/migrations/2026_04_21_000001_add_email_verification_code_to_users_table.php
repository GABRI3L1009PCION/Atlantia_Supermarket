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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('email_verification_code_hash')->nullable()->after('email_verified_at')
                ->comment('Hash del codigo temporal de verificacion de correo.');
            $table->timestamp('email_verification_code_expires_at')->nullable()->after('email_verification_code_hash')
                ->index()
                ->comment('Fecha de expiracion del codigo temporal de verificacion.');
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['email_verification_code_expires_at']);
            $table->dropColumn([
                'email_verification_code_hash',
                'email_verification_code_expires_at',
            ]);
        });
    }
};
