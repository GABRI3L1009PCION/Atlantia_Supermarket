<?php

namespace Tests\Feature\Auditoria;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ProductionReadinessAuditCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_rbac_audit_command_reports_ok_with_seeded_catalog(): void
    {
        $exitCode = Artisan::call('atlantia:audit-rbac', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exitCode);
        $this->assertSame('ok', $payload['status']);
        $this->assertSame([], $payload['missing_roles']);
        $this->assertSame([], $payload['missing_permissions']);
    }

    public function test_demo_data_audit_flags_suspicious_records(): void
    {
        User::factory()->create([
            'name' => 'Cliente Demo',
            'email' => 'cliente@atlantia.test',
            'phone' => '+502 5555-9999',
        ]);

        $exitCode = Artisan::call('atlantia:audit-demo-data', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(1, $exitCode);
        $this->assertSame('error', $payload['status']);
        $this->assertGreaterThan(0, $payload['suspicious_totals']['users']);
    }

    public function test_operational_readiness_reports_error_when_core_actors_are_missing(): void
    {
        $exitCode = Artisan::call('atlantia:operational-readiness', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(1, $exitCode);
        $this->assertSame('error', $payload['status']);
        $this->assertSame('error', $payload['actors']['admin']['status']);
        $this->assertSame('error', $payload['actors']['cliente']['status']);
        $this->assertSame('error', $payload['actors']['vendedor']['status']);
    }
}
