<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table): void {
            $table->decimal('fraud_score', 5, 4)
                ->nullable()
                ->after('estado_pago')
                ->comment('Puntaje antifraude calculado por ML o reglas locales.');
            $table->boolean('fraud_revisado')
                ->default(false)
                ->after('fraud_score')
                ->comment('Indica si el riesgo antifraude ya fue revisado automaticamente o por operador.');

            $table->index(['fraud_revisado', 'fraud_score'], 'pedidos_fraud_review_index');
        });

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement(
            "ALTER TABLE pedidos MODIFY estado ENUM(
                'pendiente',
                'confirmado',
                'en_revision',
                'preparando',
                'listo_para_entrega',
                'en_ruta',
                'entregado',
                'cancelado',
                'rechazado'
            ) NOT NULL DEFAULT 'pendiente'"
        );
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        DB::table('pedidos')->where('estado', 'en_revision')->update(['estado' => 'confirmado']);

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('pedidos', function (Blueprint $table): void {
                $table->dropIndex('pedidos_fraud_review_index');
                $table->dropColumn(['fraud_score', 'fraud_revisado']);
            });

            return;
        }

        DB::statement(
            "ALTER TABLE pedidos MODIFY estado ENUM(
                'pendiente',
                'confirmado',
                'preparando',
                'listo_para_entrega',
                'en_ruta',
                'entregado',
                'cancelado',
                'rechazado'
            ) NOT NULL DEFAULT 'pendiente'"
        );

        Schema::table('pedidos', function (Blueprint $table): void {
            $table->dropIndex('pedidos_fraud_review_index');
            $table->dropColumn(['fraud_score', 'fraud_revisado']);
        });
    }
};
