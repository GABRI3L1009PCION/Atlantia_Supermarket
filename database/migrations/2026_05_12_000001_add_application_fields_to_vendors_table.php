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
        Schema::table('vendors', function (Blueprint $table): void {
            $table->string('application_code', 40)->nullable()->unique()->after('uuid');
            $table->string('document_type', 30)->nullable()->after('user_id');
            $table->string('document_number', 80)->nullable()->unique()->after('document_type');
            $table->date('birthdate')->nullable()->after('document_number');
            $table->string('gender', 40)->nullable()->after('birthdate');
            $table->json('personal_address')->nullable()->after('gender');
            $table->string('business_category', 120)->nullable()->after('descripcion');
            $table->boolean('has_nit')->default(false)->after('business_category');
            $table->json('business_address')->nullable()->after('direccion_comercial');
            $table->json('banking_info')->nullable()->after('business_address');
            $table->string('payment_frequency', 40)->nullable()->after('banking_info');
            $table->string('preferred_payment_method', 40)->nullable()->after('payment_frequency');
            $table->json('documents')->nullable()->after('preferred_payment_method');
            $table->timestamp('terms_accepted_at')->nullable()->after('documents');
            $table->timestamp('truth_accepted_at')->nullable()->after('terms_accepted_at');
            $table->timestamp('data_consent_at')->nullable()->after('truth_accepted_at');
            $table->string('rejection_reason', 500)->nullable()->after('motivo_suspension');
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->dropUnique(['application_code']);
            $table->dropUnique(['document_number']);
            $table->dropColumn([
                'application_code',
                'document_type',
                'document_number',
                'birthdate',
                'gender',
                'personal_address',
                'business_category',
                'has_nit',
                'business_address',
                'banking_info',
                'payment_frequency',
                'preferred_payment_method',
                'documents',
                'terms_accepted_at',
                'truth_accepted_at',
                'data_consent_at',
                'rejection_reason',
            ]);
        });
    }
};
