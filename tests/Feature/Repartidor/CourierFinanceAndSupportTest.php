<?php

namespace Tests\Feature\Repartidor;

use App\Models\CourierSupportMessage;
use App\Models\CourierSupportTicket;
use App\Models\User;
use App\Services\Repartidores\CourierFinanceService;
use App\Services\Repartidores\CourierProfileService;
use App\Services\Repartidores\CourierWalletService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourierFinanceAndSupportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_repartidor_actualiza_cuenta_y_solicita_operaciones_financieras_por_api(): void
    {
        $courier = $this->createCourier();
        Passport::actingAs($courier, [], 'api');

        app(CourierWalletService::class)->recordMovement($courier, [
            'type' => 'earning',
            'amount' => 250,
            'description' => 'Saldo inicial',
        ]);
        app(CourierWalletService::class)->recordMovement($courier, [
            'type' => 'cash_collected',
            'amount' => 80,
            'description' => 'Efectivo cobrado',
        ]);

        $this->patchJson('/api/repartidor/wallet/bank-account', [
            'bank_name' => 'Banco Industrial',
            'bank_account_type' => 'monetaria',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => $courier->name,
            'payout_method' => 'transfer',
        ])->assertOk()
            ->assertJsonPath('data.bank_name', 'Banco Industrial');

        $this->postJson('/api/repartidor/wallet/withdrawals', [
            'amount' => 50,
        ])->assertStatus(422);

        app(CourierFinanceService::class)->verifyBankAccount(
            app(CourierProfileService::class)->ensure($courier),
            ['action' => 'verify', 'notes' => 'Cuenta validada'],
            $this->createInternalUser('contabilidad_finanzas')
        );

        $this->postJson('/api/repartidor/wallet/withdrawals', [
            'amount' => 50,
            'notes' => 'Primer retiro',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->postJson('/api/repartidor/wallet/cash-settlements', [
            'reported_amount' => 35,
            'notes' => 'Entrega de caja',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('courier_withdrawal_requests', [
            'user_id' => $courier->id,
            'requested_amount' => 50,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('courier_cash_settlements', [
            'user_id' => $courier->id,
            'reported_amount' => 35,
            'status' => 'pending',
        ]);
    }

    public function test_finanzas_transfiere_retiro_y_concilia_efectivo(): void
    {
        $courier = $this->createCourier();
        $financeUser = $this->createInternalUser('contabilidad_finanzas');

        app(CourierWalletService::class)->recordMovement($courier, [
            'type' => 'earning',
            'amount' => 240,
            'description' => 'Saldo para retiro',
        ]);
        app(CourierWalletService::class)->recordMovement($courier, [
            'type' => 'cash_collected',
            'amount' => 90,
            'description' => 'Cobro en efectivo',
        ]);

        app(CourierFinanceService::class)->updateBankAccount($courier, [
            'bank_name' => 'Banrural',
            'bank_account_type' => 'ahorro',
            'bank_account_number' => '99887766',
            'bank_account_holder' => $courier->name,
            'payout_method' => 'transfer',
        ]);
        app(CourierFinanceService::class)->verifyBankAccount(
            app(CourierProfileService::class)->ensure($courier),
            ['action' => 'verify'],
            $financeUser
        );

        $withdrawal = app(CourierFinanceService::class)->requestWithdrawal($courier, [
            'amount' => 100,
            'notes' => 'Retiro semanal',
        ]);
        $settlement = app(CourierFinanceService::class)->requestCashSettlement($courier, [
            'reported_amount' => 60,
            'notes' => 'Caja de la tarde',
        ]);

        $this->actingAs($financeUser)
            ->patch(route('empleado.finanzas-repartidores.withdrawals.update', $withdrawal), [
                'action' => 'transfer',
                'approved_amount' => 100,
                'transfer_reference' => 'TRX-1001',
            ])->assertRedirect();

        $this->actingAs($financeUser)
            ->patch(route('empleado.finanzas-repartidores.cash-settlements.update', $settlement), [
                'action' => 'approve',
                'approved_amount' => 60,
                'settlement_reference' => 'CAJA-60',
            ])->assertRedirect();

        $this->assertDatabaseHas('courier_withdrawal_requests', [
            'id' => $withdrawal->id,
            'status' => 'transferred',
            'transfer_reference' => 'TRX-1001',
        ]);
        $this->assertDatabaseHas('courier_cash_settlements', [
            'id' => $settlement->id,
            'status' => 'approved',
            'settlement_reference' => 'CAJA-60',
        ]);
        $this->assertDatabaseHas('courier_wallet_movements', [
            'user_id' => $courier->id,
            'type' => 'withdrawal',
            'amount' => -100,
        ]);
        $this->assertDatabaseHas('courier_wallet_movements', [
            'user_id' => $courier->id,
            'type' => 'cash_settlement',
            'amount' => -60,
        ]);

        $wallet = $courier->fresh()->courierWallet;
        $this->assertSame(140.0, (float) $wallet->available_balance);
        $this->assertSame(30.0, (float) $wallet->cash_balance);
    }

    public function test_soporte_maneja_ticket_asignacion_respuesta_y_cierre(): void
    {
        $courier = $this->createCourier();
        $supportUser = $this->createInternalUser('soporte');

        Passport::actingAs($courier, [], 'api');

        $ticketResponse = $this->postJson('/api/repartidor/support/tickets', [
            'type' => 'payment_problem',
            'priority' => 'high',
            'message' => 'No cuadra el cobro del pedido.',
        ])->assertCreated();

        $ticket = CourierSupportTicket::query()->where('uuid', $ticketResponse->json('data.id'))->firstOrFail();

        $this->actingAs($supportUser)
            ->patch(route('empleado.soporte-repartidores.assign', $ticket), [
                'assigned_to_user_id' => $supportUser->id,
            ])->assertRedirect();

        $this->actingAs($supportUser)
            ->post(route('empleado.soporte-repartidores.reply', $ticket), [
                'message' => 'Estamos revisando el cobro.',
                'status' => 'in_progress',
            ])->assertRedirect();

        Passport::actingAs($courier, [], 'api');

        $this->postJson('/api/repartidor/support/tickets/'.$ticket->uuid.'/reply', [
            'message' => 'Gracias, quedo pendiente.',
        ])->assertOk();

        $this->patchJson('/api/repartidor/support/tickets/'.$ticket->uuid.'/status', [
            'status' => 'closed',
        ])->assertOk();

        $this->assertDatabaseHas('courier_support_tickets', [
            'id' => $ticket->id,
            'assigned_to_user_id' => $supportUser->id,
            'status' => 'closed',
        ]);
        $this->assertSame(4, CourierSupportMessage::query()->where('courier_support_ticket_id', $ticket->id)->count());
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $courier->id,
            'type' => 'courier.support.reply',
        ]);
    }

    private function createCourier(): User
    {
        $user = User::factory()->repartidor()->create();
        $user->assignRole(Role::findByName('repartidor', 'web'));

        return $user;
    }

    private function createInternalUser(string $role): User
    {
        $user = User::factory()->empleado()->create();
        $user->assignRole(Role::findByName($role, 'web'));

        return $user;
    }
}
