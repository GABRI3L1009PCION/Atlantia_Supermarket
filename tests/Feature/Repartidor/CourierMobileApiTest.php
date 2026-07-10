<?php

namespace Tests\Feature\Repartidor;

use App\Models\CourierDevice;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CourierMobileApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seedPassportClient();
    }

    public function test_repartidor_puede_iniciar_sesion_y_registrar_dispositivo(): void
    {
        $repartidor = User::factory()->repartidor()->create([
            'email' => 'driver@atlantia.test',
            'password' => 'Atlantia2026!',
        ]);
        $repartidor->assignRole('repartidor');

        $login = $this->postJson('/api/repartidor/login', [
            'email' => 'driver@atlantia.test',
            'password' => 'Atlantia2026!',
            'device_name' => 'Pixel 8 Atlantia',
        ]);

        $login->assertOk()
            ->assertJsonPath('message', 'Sesion iniciada.')
            ->assertJsonStructure([
                'data' => [
                    'token_type',
                    'access_token',
                    'user' => [
                        'id',
                        'name',
                        'profile',
                        'wallet',
                    ],
                ],
            ]);

        $token = $login->json('data.access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/repartidor/device', [
                'platform' => 'android',
                'device_uuid' => 'android-device-001',
                'device_name' => 'Pixel 8',
                'app_version' => '0.1.0',
                'push_provider' => 'fcm',
                'push_token' => 'fcm-token-demo',
                'notifications_enabled' => true,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Dispositivo registrado.')
            ->assertJsonPath('data.platform', 'android')
            ->assertJsonPath('data.notifications_enabled', true);

        $device = CourierDevice::query()->where('device_uuid', 'android-device-001')->firstOrFail();

        $this->assertSame($repartidor->id, $device->user_id);
        $this->assertSame('fcm-token-demo', $device->push_token);
        $this->assertTrue($device->notifications_enabled);
    }

    public function test_login_movil_rechaza_usuarios_que_no_son_repartidores(): void
    {
        $cliente = User::factory()->cliente()->create([
            'email' => 'cliente@atlantia.test',
            'password' => 'Atlantia2026!',
        ]);
        $cliente->assignRole('cliente');

        $this->postJson('/api/repartidor/login', [
            'email' => 'cliente@atlantia.test',
            'password' => 'Atlantia2026!',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Esta app es solo para repartidores.');
    }

    private function seedPassportClient(): void
    {
        DB::table('oauth_clients')->insert([
            'id' => 'repartidor-mobile-test-client',
            'user_id' => null,
            'name' => 'Atlantia Repartidor Mobile Test',
            'secret' => 'test-secret',
            'provider' => 'users',
            'redirect' => 'http://localhost',
            'personal_access_client' => true,
            'password_client' => false,
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('oauth_personal_access_clients')->insert([
            'client_id' => 'repartidor-mobile-test-client',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
