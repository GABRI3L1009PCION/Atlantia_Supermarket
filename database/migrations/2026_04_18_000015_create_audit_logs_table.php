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
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique()->comment('Identificador público del evento de auditoría.');
            $table->foreignId('user_id')->nullable()->index()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->string('event', 120)->index()->comment('Acción auditada dentro del sistema.');
            $table->nullableMorphs('auditable');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable()->comment('Datos adicionales del contexto de seguridad.');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_id', 100)->nullable()->index();
            $table->string('url', 500)->nullable();
            $table->string('method', 10)->nullable();
            $table->timestamps();

            $table->index(['event', 'created_at']);
            $table->index(['auditable_type', 'auditable_id', 'created_at'], 'audit_logs_auditable_lookup_index');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
