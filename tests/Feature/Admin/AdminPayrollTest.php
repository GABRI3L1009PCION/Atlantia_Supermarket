<?php

namespace Tests\Feature\Admin;

use App\Models\Empleado;
use App\Models\Nomina;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pruebas del flujo administrativo de nominas.
 */
class AdminPayrollTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function testAdminCanGenerateAdjustAndPayPayroll(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');

        $employeeUser = User::factory()->empleado()->create();
        $employeeUser->assignRole('empleado');

        $empleado = Empleado::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $employeeUser->id,
            'codigo_empleado' => 'EMP-001',
            'departamento' => 'operaciones',
            'puesto' => 'Auxiliar operativo',
            'salario_base' => 3500,
            'fecha_contratacion' => '2026-05-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.nominas.store'), [
            'periodo_inicio' => '2026-05-16',
            'periodo_fin' => '2026-05-31',
            'tipo_periodo' => 'quincenal',
            'notas' => 'Segunda quincena de mayo.',
        ]);

        $nomina = Nomina::query()->with('detalles')->firstOrFail();
        $detalle = $nomina->detalles->firstOrFail();

        $response->assertRedirect(route('admin.nominas.show', $nomina->uuid));
        $this->assertDatabaseHas('nominas', [
            'id' => $nomina->id,
            'estado' => 'borrador',
            'total_neto' => 3500,
        ]);

        $this->actingAs($admin)->put(route('admin.nominas.detalles.update', [$nomina->uuid, $detalle]), [
            'bonificaciones' => 250,
            'descuentos' => 100,
            'observaciones' => 'Bono operativo y descuento autorizado.',
        ])->assertRedirect();

        $this->assertDatabaseHas('nomina_detalles', [
            'nomina_id' => $nomina->id,
            'empleado_id' => $empleado->id,
            'total_neto' => 3650,
        ]);

        $this->actingAs($admin)->patch(route('admin.nominas.pay', $nomina->uuid))
            ->assertRedirect();

        $this->assertDatabaseHas('nominas', [
            'id' => $nomina->id,
            'estado' => 'pagada',
            'total_neto' => 3650,
            'pagada_por' => $admin->id,
        ]);

        $this->actingAs($admin)->put(route('admin.nominas.detalles.update', [$nomina->uuid, $detalle]), [
            'bonificaciones' => 999,
            'descuentos' => 0,
        ])->assertSessionHasErrors('nomina');

        $this->assertDatabaseHas('nomina_detalles', [
            'id' => $detalle->id,
            'total_neto' => 3650,
        ]);
    }

    public function testPayrollGenerationRequiresConfiguredSalary(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');

        $employeeUser = User::factory()->empleado()->create();
        $employeeUser->assignRole('empleado');

        Empleado::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $employeeUser->id,
            'codigo_empleado' => 'EMP-002',
            'departamento' => 'operaciones',
            'puesto' => 'Auxiliar operativo',
            'salario_base' => 0,
            'fecha_contratacion' => '2026-05-01',
            'status' => 'active',
        ]);

        $this->actingAs($admin)->post(route('admin.nominas.store'), [
            'periodo_inicio' => '2026-05-16',
            'periodo_fin' => '2026-05-31',
            'tipo_periodo' => 'quincenal',
        ])->assertSessionHasErrors('periodo_inicio');

        $this->assertDatabaseCount('nominas', 0);
    }

    public function testAdminCanCreateEmployeeWithPayrollSalary(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('admin.empleados.store'), [
            'name' => 'Empleado de Nomina',
            'email' => 'empleado.nomina@atlantia.test',
            'phone' => '+502 5555-1100',
            'password' => 'Atlantia2026!',
            'password_confirmation' => 'Atlantia2026!',
            'codigo_empleado' => 'EMP-NOM-001',
            'departamento' => 'operaciones',
            'puesto' => 'Auxiliar operativo',
            'salario_base' => 4200,
            'fecha_contratacion' => '2026-05-29',
            'status' => 'active',
        ])->assertRedirect();

        $this->assertDatabaseHas('empleados', [
            'codigo_empleado' => 'EMP-NOM-001',
            'departamento' => 'operaciones',
            'salario_base' => 4200,
        ]);
    }
}
