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
        Schema::create('contact_messages', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique()->comment('Identificador público seguro del mensaje de contacto.');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->string('nombre', 160);
            $table->string('email', 190)->index();
            $table->string('telefono', 30)->nullable();
            $table->string('asunto', 180)->index();
            $table->text('mensaje');
            $table->boolean('atendido')->default(false)->index();
            $table->foreignId('atendido_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('atendido_at')->nullable();
            $table->enum('prioridad', ['baja', 'normal', 'alta'])->default('normal')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['atendido', 'created_at']);
            $table->index(['prioridad', 'atendido']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
