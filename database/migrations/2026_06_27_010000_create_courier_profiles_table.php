<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        if (Schema::hasTable('courier_profiles')) {
            return;
        }

        Schema::create('courier_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('availability_status', 30)->default('offline')->index();
            $table->string('service_scope', 30)->default('both')->index();
            $table->string('vehicle_type', 40)->nullable();
            $table->string('reward_level', 40)->default('Aprendiz')->index();
            $table->unsignedInteger('reward_points')->default(0);
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->decimal('acceptance_rate', 5, 2)->default(100.00);
            $table->decimal('completion_rate', 5, 2)->default(100.00);
            $table->boolean('auto_accept_enabled')->default(false);
            $table->decimal('auto_accept_max_distance_km', 8, 2)->default(5.00);
            $table->json('safe_zones')->nullable();
            $table->boolean('insurance_active')->default(true);
            $table->text('insurance_notes')->nullable();
            $table->string('bank_name', 120)->nullable();
            $table->string('bank_account_type', 40)->nullable();
            $table->string('bank_account_number', 120)->nullable();
            $table->string('bank_account_holder', 160)->nullable();
            $table->timestamp('last_online_at')->nullable();
            $table->timestamp('last_offline_at')->nullable();
            $table->timestamps();

            $table->index(['availability_status', 'service_scope']);
            $table->index(['reward_level', 'reward_points']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_profiles');
    }
};
