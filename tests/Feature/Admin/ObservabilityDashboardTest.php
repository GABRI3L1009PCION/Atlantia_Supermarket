<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ObservabilityDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_can_open_observability_dashboard(): void
    {
        config([
            'scout.meilisearch.host' => 'http://search.test',
            'services.ml.base_url' => 'http://ml.test/api/v1',
            'services.firebase.enabled' => true,
            'services.firebase.project_id' => 'firebase-test-project',
            'services.firebase.service_account_email' => 'firebase@test.iam.gserviceaccount.com',
            'services.firebase.private_key' => "-----BEGIN PRIVATE KEY-----\nTEST\n-----END PRIVATE KEY-----\n",
            'services.mapbox.token' => 'pk.test.mapbox',
            'atlantia.support.phone' => '+50255550101',
            'atlantia.support.emergency_phone' => '+50255550191',
            'atlantia.support.channels' => ['app', 'phone'],
            'atlantia.payments.pos.provider' => 'POS bancario',
            'atlantia.payments.transfer.bank_name' => 'Banco Industrial',
            'atlantia.payments.transfer.account_number' => '0000-000000-000',
        ]);

        Cache::put(
            config('observability.scheduler.heartbeat_key'),
            now()->toIso8601String(),
            now()->addMinutes(10)
        );

        Redis::shouldReceive('connection->ping')->once()->andReturn('PONG');
        Http::fake([
            'http://search.test/health' => Http::response(['status' => 'available']),
            'http://ml.test/api/v1/health' => Http::response(['status' => 'ok']),
        ]);

        $superAdmin = User::factory()->admin()->create(['email' => 'root.observabilidad@atlantia.test']);
        $superAdmin->assignRole('super_admin');

        $response = $this->actingAs($superAdmin)->get(route('admin.observabilidad.index'));

        $response->assertOk();
        $response->assertSee('Observabilidad operativa');
        $response->assertSee('Chequeos operativos');
        $response->assertSee('Release y despliegue');
        $response->assertSee('Base de datos');
    }
}
