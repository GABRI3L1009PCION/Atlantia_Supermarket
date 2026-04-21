<?php

namespace App\Services\Auth;

use App\Models\Cliente\ClienteDetalle;
use App\Models\Empleado;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorFiscalProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
            ->when(! $viewer?->isSuperAdmin(), function ($query): void {
                $query->visibleToOperationalAdmin();
            })
            ->when($filters['q'] ?? null, function ($query, string $q): void {
                $query->where(fn ($builder) => $builder
                    ->where('name', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%'));
            })
            ->latest()
            ->paginate(25)
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
                'two_factor_confirmed_at' => in_array($data['role'], ['admin', 'super_admin'], true) ? now() : null,
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
                Empleado::query()->firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'uuid' => (string) Str::uuid(),
                        'codigo_empleado' => 'ATL-EMP-' . str_pad((string) $user->id, 3, '0', STR_PAD_LEFT),
                        'departamento' => 'operaciones',
                        'puesto' => 'Colaborador Atlantia',
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
}
