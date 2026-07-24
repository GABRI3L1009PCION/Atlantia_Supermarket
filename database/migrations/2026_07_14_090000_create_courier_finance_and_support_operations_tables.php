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
        if (! Schema::hasColumn('courier_profiles', 'payout_method')) {
            Schema::table('courier_profiles', function (Blueprint $table): void {
                $table->string('payout_method', 30)->default('transfer')->after('bank_account_holder');
                $table->string('bank_document_path')->nullable()->after('payout_method');
                $table->timestamp('bank_account_verified_at')->nullable()->after('bank_document_path');
                $table->foreignId('bank_account_verified_by')
                    ->nullable()
                    ->after('bank_account_verified_at')
                    ->constrained('users')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
                $table->string('bank_verification_notes', 255)->nullable()->after('bank_account_verified_by');
            });
        }

        if (! Schema::hasTable('courier_withdrawal_requests')) {
            Schema::create('courier_withdrawal_requests', function (Blueprint $table): void {
                $table->id();
                $table->char('uuid', 36)->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('courier_wallet_id')->nullable()->constrained('courier_wallets')->nullOnDelete()->cascadeOnUpdate();
                $table->string('status', 30)->default('pending')->index();
                $table->string('payout_method', 30)->default('transfer')->index();
                $table->decimal('requested_amount', 12, 2);
                $table->decimal('approved_amount', 12, 2)->nullable();
                $table->decimal('transferred_amount', 12, 2)->nullable();
                $table->string('bank_name', 120)->nullable();
                $table->string('bank_account_type', 40)->nullable();
                $table->string('bank_account_number_last4', 4)->nullable();
                $table->string('bank_account_holder', 120)->nullable();
                $table->text('courier_notes')->nullable();
                $table->text('admin_notes')->nullable();
                $table->string('transfer_reference', 120)->nullable();
                $table->string('receipt_path')->nullable();
                $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
                $table->foreignId('transferred_by_user_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('transferred_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['status', 'requested_at']);
            });
        }

        if (! Schema::hasTable('courier_cash_settlements')) {
            Schema::create('courier_cash_settlements', function (Blueprint $table): void {
                $table->id();
                $table->char('uuid', 36)->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('courier_wallet_id')->nullable()->constrained('courier_wallets')->nullOnDelete()->cascadeOnUpdate();
                $table->string('status', 30)->default('pending')->index();
                $table->decimal('expected_amount', 12, 2)->default(0);
                $table->decimal('reported_amount', 12, 2);
                $table->decimal('approved_amount', 12, 2)->nullable();
                $table->text('courier_notes')->nullable();
                $table->text('admin_notes')->nullable();
                $table->string('settlement_reference', 120)->nullable();
                $table->string('receipt_path')->nullable();
                $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
                $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['status', 'requested_at']);
            });
        }

        if (! Schema::hasColumn('courier_support_tickets', 'assigned_to_user_id')) {
            Schema::table('courier_support_tickets', function (Blueprint $table): void {
                $table->foreignId('assigned_to_user_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
                $table->string('channel', 30)->default('app')->after('status');
                $table->timestamp('last_message_at')->nullable()->after('support_response');
                $table->timestamp('closed_at')->nullable()->after('resolved_at');
            });
        }

        if (! Schema::hasTable('courier_support_messages')) {
            Schema::create('courier_support_messages', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('courier_support_ticket_id')
                    ->constrained('courier_support_tickets')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
                $table->string('sender_type', 30);
                $table->text('message');
                $table->boolean('is_internal')->default(false);
                $table->json('attachments')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasIndex('courier_support_messages', ['sender_type'])) {
            Schema::table('courier_support_messages', function (Blueprint $table): void {
                $table->index('sender_type', 'courier_support_sender_type_idx');
            });
        }

        if (! Schema::hasIndex('courier_support_messages', ['courier_support_ticket_id', 'created_at'])) {
            Schema::table('courier_support_messages', function (Blueprint $table): void {
                $table->index(
                    ['courier_support_ticket_id', 'created_at'],
                    'courier_support_ticket_created_idx'
                );
            });
        }
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_support_messages');

        if (Schema::hasColumn('courier_support_tickets', 'assigned_to_user_id')) {
            Schema::table('courier_support_tickets', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('assigned_to_user_id');
                $table->dropColumn(['channel', 'last_message_at', 'closed_at']);
            });
        }

        Schema::dropIfExists('courier_cash_settlements');
        Schema::dropIfExists('courier_withdrawal_requests');

        if (Schema::hasColumn('courier_profiles', 'payout_method')) {
            Schema::table('courier_profiles', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('bank_account_verified_by');
                $table->dropColumn([
                    'payout_method',
                    'bank_document_path',
                    'bank_account_verified_at',
                    'bank_verification_notes',
                ]);
            });
        }
    }
};
