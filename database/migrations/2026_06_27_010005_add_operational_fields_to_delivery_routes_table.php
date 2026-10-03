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
        Schema::table('delivery_routes', function (Blueprint $table): void {
            if (! Schema::hasColumn('delivery_routes', 'source_type')) {
                $table->string('source_type', 30)->default('internal')->after('repartidor_id')->index();
            }

            if (! Schema::hasColumn('delivery_routes', 'pickup_name')) {
                $table->string('pickup_name', 160)->nullable()->after('source_type');
                $table->string('pickup_address', 500)->nullable()->after('pickup_name');
                $table->decimal('pickup_latitude', 10, 8)->nullable()->after('pickup_address');
                $table->decimal('pickup_longitude', 11, 8)->nullable()->after('pickup_latitude');
                $table->text('pickup_notes')->nullable()->after('pickup_longitude');
                $table->timestamp('arrived_pickup_at')->nullable()->after('aceptada_at');
                $table->timestamp('picked_up_at')->nullable()->after('arrived_pickup_at');
                $table->timestamp('arrived_customer_at')->nullable()->after('iniciada_at');
                $table->timestamp('pickup_not_ready_at')->nullable()->after('arrived_pickup_at');
                $table->string('pickup_issue_reason', 255)->nullable()->after('pickup_not_ready_at');
                $table->decimal('estimated_earning', 12, 2)->default(0)->after('tiempo_real_min');
                $table->decimal('tip_amount', 12, 2)->default(0)->after('estimated_earning');
                $table->decimal('bonus_amount', 12, 2)->default(0)->after('tip_amount');
                $table->decimal('cash_to_collect', 12, 2)->default(0)->after('bonus_amount');
                $table->decimal('cash_to_pay_pickup', 12, 2)->default(0)->after('cash_to_collect');
                $table->decimal('change_required', 12, 2)->default(0)->after('cash_to_pay_pickup');
                $table->string('payment_method', 40)->nullable()->after('change_required');
                $table->string('proof_type', 40)->default('photo')->after('payment_method');
                $table->string('confirmation_code', 20)->nullable()->after('proof_type');
                $table->timestamp('delivered_code_confirmed_at')->nullable()->after('confirmation_code');
            }
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::table('delivery_routes', function (Blueprint $table): void {
            $columns = [
                'source_type',
                'pickup_name',
                'pickup_address',
                'pickup_latitude',
                'pickup_longitude',
                'pickup_notes',
                'arrived_pickup_at',
                'picked_up_at',
                'arrived_customer_at',
                'pickup_not_ready_at',
                'pickup_issue_reason',
                'estimated_earning',
                'tip_amount',
                'bonus_amount',
                'cash_to_collect',
                'cash_to_pay_pickup',
                'change_required',
                'payment_method',
                'proof_type',
                'confirmation_code',
                'delivered_code_confirmed_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('delivery_routes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
