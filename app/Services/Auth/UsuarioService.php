<?php

namespace App\Services\Auth;

use App\Models\Cliente\ClienteDetalle;
use App\Models\Empleado;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorFiscalProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Servicio administrativo de usuarios.
 */
class UsuarioService
{
    /**
     * Pagina usuarios visibles para administracion operativa.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = [], ?User $viewer = null): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->where(function ($query): void {
                $query->where('is_system_user', false)
                    ->orWhereHas('roles', function ($roleQuery): void {
                        $roleQuery->whereIn('name', ['admin', 'super_admin']);
                    });
            })
            ->when(! $viewer?->isSuperAdmin(), function ($query): void {
                $query->visibleToOperationalAdmin();
            })
            ->when($filters['q'] ?? null, function ($query, string $q): void {
                $query->where(fn ($builder) => $builder
                    ->where('name', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%'));
            })
            ->when($filters['role'] ?? null, function ($query, string $role): void {
                $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', $role));
            })
            ->when($filters['status'] ?? null, function ($query, string $status): void {
                if ($status === 'pending') {
                    $query->whereNull('email_verified_at');

                    return;
                }

                $query->where('status', $status);
            })
            ->when($filters['created_range'] ?? null, function ($query, string $range) use ($filters): void {
                match ($range) {
                    '7_days' => $query->where('created_at', '>=', now()->subDays(7)),
                    'month' => $query->where('created_at', '>=', now()->subMonth()),
                    'custom' => $query
                        ->when($filters['created_from'] ?? null, fn ($dateQuery, string $date) => $dateQuery->whereDate('created_at', '>=', $date))
                        ->when($filters['created_to'] ?? null, fn ($dateQuery, string $date) => $dateQuery->whereDate('created_at', '<=', $date)),
                    default => null,
                };
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Devuelve detalle del usuario.
     */
    public function detail(User $user): User
    {
        return $user->load(['roles', 'vendor', 'empleado', 'clienteDetalle']);
    }

    /**
     * Crea una cuenta operativa desde administracion.
     *
     * @param array<string, mixed> $data
     * @return User
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'],
                'email_verified_at' => now(),
                'is_system_user' => in_array($data['role'], ['admin', 'super_admin'], true),
                'two_factor_enabled' => in_array($data['role'], ['admin', 'super_admin'], true),
                'two_factor_confirmed_at' => null,
            ]);

            $user->assignRole($data['role']);
            $this->createSupportProfiles($user, $data);

            return $this->detail($user);
        });
    }

    /**
     * Actualiza datos operativos de usuario.
     *
     * @param array<string, mixed> $data
     */
    public function update(User $user, array $data): User
    {
        $user->fill(collect($data)->only(['name', 'email', 'phone', 'status'])->all());

        if (! empty($data['password'])) {
            $user->password = Hash::make((string) $data['password']);
        }

        $user->save();

        if (isset($data['roles']) && ! $user->isSuperAdmin()) {
            $user->syncRoles($data['roles']);

            foreach ($data['roles'] as $role) {
                $this->createSupportProfiles($user, [...$data, 'role' => (string) $role]);
            }
        }

        return $this->detail($user->refresh());
    }

    /**
     * Elimina logicamente una cuenta operativa.
     */
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            if ($user->vendor !== null) {
                $user->vendor->update([
                    'status' => 'suspended',
                    'is_approved' => false,
                    'suspendido_at' => now(),
                    'motivo_suspension' => 'Cuenta eliminada por administracion.',
                ]);
            }

            if ($user->empleado !== null) {
                $user->empleado->update(['status' => 'inactive']);
            }

            $user->status = 'inactive';
            $user->save();
            $user->delete();
        });
    }

    /**
     * Crea perfiles auxiliares segun rol base.
     *
     * @param User $user
     * @param array<string, mixed> $data
     * @return void
     */
    private function createSupportProfiles(User $user, array $data): void
    {
        switch ($data['role']) {
            case 'cliente':
                ClienteDetalle::query()->firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'telefono' => $data['phone'] ?? null,
                        'acepta_marketing' => false,
                        'terminos_aceptados_at' => now(),
                        'privacidad_aceptada_at' => now(),
                    ]
                );
                break;

            case 'empleado':
            case 'bodeguero':
            case 'soporte':
            case 'contabilidad_finanzas':
            case 'supervisor_logistica':
                Empleado::query()->firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'uuid' => (string) Str::uuid(),
                        'codigo_empleado' => $this->employeeCodePrefix((string) $data['role']) . str_pad((string) $user->id, 3, '0', STR_PAD_LEFT),
                        'departamento' => $this->employeeDepartment((string) $data['role'], $data),
                        'puesto' => $this->employeePosition((string) $data['role']),
                        'telefono_interno' => $data['phone'] ?? null,
                        'fecha_contratacion' => now()->toDateString(),
                        'status' => 'active',
                    ]
                );
                break;

            case 'vendedor':
                $vendor = Vendor::query()->firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'uuid' => (string) Str::uuid(),
                        'business_name' => $user->name,
                        'slug' => Str::slug($user->name) . '-' . Str::lower(Str::random(6)),
                        'descripcion' => 'Vendedor creado por administracion.',
                        'telefono_publico' => $data['phone'] ?? null,
                        'email_publico' => $user->email,
                        'municipio' => 'Puerto Barrios',
                        'direccion_comercial' => 'Pendiente de completar',
                        'is_approved' => true,
                        'approved_at' => now(),
                        'status' => 'approved',
                        'commission_percentage' => 0,
                        'monthly_rent' => 0,
                        'accepts_cash' => true,
                        'accepts_transfer' => true,
                        'accepts_card' => true,
                    ]
                );

                VendorFiscalProfile::query()->firstOrCreate(
                    ['vendor_id' => $vendor->id],
                    [
                        'nit' => 'CF-' . $vendor->id,
                        'razon_social' => $vendor->business_name,
                        'direccion_fiscal' => 'Pendiente de completar',
                        'regimen_sat' => 'general',
                        'codigo_establecimiento' => 'ADM-' . $vendor->id,
                        'certificador_fel' => 'infile',
                        'fel_activo' => false,
                    ]
                );
                break;
        }
    }

    /**
     * Prefijo formal para codigos internos segun rol operativo.
     */
    private function employeeCodePrefix(string $role): string
    {
        return match ($role) {
            'bodeguero' => 'ATL-BOD-',
            'soporte' => 'ATL-SOP-',
            'contabilidad_finanzas' => 'ATL-FIN-',
            'supervisor_logistica' => 'ATL-LOG-',
            default => 'ATL-EMP-',
        };
    }

    /**
     * Departamento interno sugerido segun rol operativo.
     *
     * @param array<string, mixed> $data
     */
    private function employeeDepartment(string $role, array $data): string
    {
        return match ($role) {
            'bodeguero' => 'operaciones',
            'soporte' => 'soporte_cliente',
            'contabilidad_finanzas' => 'finanzas',
            'supervisor_logistica' => 'logistica',
            default => $this->normalizeEmployeeDepartment((string) ($data['department'] ?? 'operaciones')),
        };
    }

    /**
     * Traduce opciones visibles del formulario a los departamentos soportados por empleados.
     */
    private function normalizeEmployeeDepartment(string $department): string
    {
        return match ($department) {
            'soporte' => 'soporte_cliente',
            'contabilidad' => 'finanzas',
            'bodega', 'compras', 'ventas' => 'operaciones',
            'tecnologia' => 'administracion',
            'administracion', 'operaciones', 'soporte_cliente', 'finanzas', 'logistica', 'moderacion' => $department,
            default => 'operaciones',
        };
    }

    /**
     * Puesto visible en el perfil interno segun rol operativo.
     */
    private function employeePosition(string $role): string
    {
        return match ($role) {
            'bodeguero' => 'Bodeguero',
            'soporte' => 'Agente de soporte',
            'contabilidad_finanzas' => 'Analista de contabilidad y finanzas',
            'supervisor_logistica' => 'Supervisor de logistica',
            default => 'Colaborador Atlantia',
        };
    }

    /**
     * Aplica una accion masiva sobre usuarios seleccionados.
     *
     * @param array<int, int> $ids
     */
    public function batch(array $ids, string $action, array $payload = [], ?User $viewer = null): int
    {
        $users = User::query()->whereIn('id', $ids)->get();

        return DB::transaction(function () use ($users, $action, $payload, $viewer): int {
            $affected = 0;

            /** @var Collection<int, User> $users */
            foreach ($users as $user) {
                if ($user->isSuperAdmin() || (! $viewer?->isSuperAdmin() && $user->hasRole('admin'))) {
                    continue;
                }

                match ($action) {
                    'role' => $this->assignBatchRole($user, (string) $payload['role'], $payload),
                    'activate' => $user->update(['status' => 'active']),
                    'deactivate' => $user->update(['status' => 'inactive']),
                    'delete' => $this->delete($user),
                    default => null,
                };

                $affected++;
            }

            return $affected;
        });
    }

    /**
     * Cambia rol desde acciones masivas y crea perfiles auxiliares cuando corresponde.
     *
     * @param array<string, mixed> $payload
     */
    private function assignBatchRole(User $user, string $role, array $payload): void
    {
        $user->syncRoles([$role]);
        $this->createSupportProfiles($user, [
            ...$payload,
            'role' => $role,
            'phone' => $user->phone,
        ]);
    }
}
