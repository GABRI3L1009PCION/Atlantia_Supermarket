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
        if (Schema::hasTable('external_delivery_orders')) {
            return;
        }

        Schema::create('external_delivery_orders', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->string('source_channel', 80)->default('manual')->index();
            $table->string('external_reference', 120)->nullable()->index();
            $table->string('store_name', 160);
            $table->string('store_contact_name', 160)->nullable();
            $table->string('store_phone', 40)->nullable();
            $table->string('store_email', 160)->nullable();
            $table->string('pickup_address', 500);
            $table->decimal('pickup_latitude', 10, 8)->nullable();
            $table->decimal('pickup_longitude', 11, 8)->nullable();
            $table->text('pickup_notes')->nullable();
            $table->string('customer_name', 160);
            $table->string('customer_phone', 40)->nullable();
            $table->string('delivery_address', 500);
            $table->decimal('delivery_latitude', 10, 8)->nullable();
            $table->decimal('delivery_longitude', 11, 8)->nullable();
            $table->text('delivery_notes')->nullable();
            $table->string('payment_method', 40)->default('digital')->index();
            $table->decimal('amount_to_collect', 12, 2)->default(0);
            $table->decimal('amount_to_pay_store', 12, 2)->default(0);
            $table->decimal('change_required', 12, 2)->default(0);
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('courier_earning', 12, 2)->default(0);
            $table->decimal('tip_amount', 12, 2)->default(0);
            $table->decimal('estimated_distance_km', 8, 2)->nullable();
            $table->unsignedSmallInteger('estimated_time_min')->nullable();
            $table->string('status', 40)->default('requested')->index();
            $table->foreignId('repartidor_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('offered_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('arrived_pickup_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('arrived_customer_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('pickup_not_ready_at')->nullable();
            $table->string('pickup_issue_reason', 255)->nullable();
            $table->string('proof_photo_path', 500)->nullable();
            $table->string('confirmation_code', 20)->nullable();
            $table->timestamp('confirmation_code_verified_at')->nullable();
            $table->timestamp('cash_issue_reported_at')->nullable();
            $table->text('cash_issue_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['repartidor_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['source_channel', 'external_reference']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_delivery_orders');
    }
};
