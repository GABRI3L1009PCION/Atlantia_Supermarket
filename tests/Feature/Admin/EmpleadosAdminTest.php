<?php

namespace Tests\Feature\Admin;

use App\Models\Empleado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmpleadosAdminTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        Role::findOrCreate('super_admin', 'web');
        $user = User::factory()->admin()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    public function test_admin_creates_employee_with_chosen_role(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.empleados.store'), [
            'name' => 'Pedro Bodega',
            'email' => 'pedro.bodega@atlantia.test',
            'password' => 'Mango#Lluvia84',
            'password_confirmation' => 'Mango#Lluvia84',
            'codigo_empleado' => 'ATL-BOD-900',
            'departamento' => 'operaciones',
            'rol' => 'bodeguero',
            'puesto' => 'Bodeguero',
            'salario_base' => 3500,
            'fecha_contratacion' => now()->toDateString(),
            'status' => 'active',
        ])->assertSessionHasNoErrors();

        $empleado = Empleado::query()->where('codigo_empleado', 'ATL-BOD-900')->firstOrFail();
        $this->assertTrue($empleado->user->hasRole('bodeguero'));

        $this->actingAs($admin)->get(route('admin.empleados.index'))
            ->assertOk()
            ->assertSee('Pedro Bodega')
            ->assertSee('Bodeguero')
            ->assertSee('Nómina mensual')
            ->assertSee('Q 3,500.00')
            ->assertSee('Activo');
    }

    public function test_employee_list_filters_by_status(): void
    {
        $admin = $this->superAdmin();
        foreach (['suspended' => 'Puesto Suspendido', 'active' => 'Puesto Activo'] as $estado => $puesto) {
            Empleado::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => User::factory()->create()->id,
                'codigo_empleado' => 'ATL-T-'.$estado,
                'departamento' => 'operaciones',
                'puesto' => $puesto,
                'fecha_contratacion' => now()->toDateString(),
                'status' => $estado,
            ]);
        }

        $this->actingAs($admin)->get(route('admin.empleados.index', ['estado' => 'suspended']))
            ->assertOk()
            ->assertSee('Puesto Suspendido')
            ->assertDontSee('Puesto Activo');
    }
}
