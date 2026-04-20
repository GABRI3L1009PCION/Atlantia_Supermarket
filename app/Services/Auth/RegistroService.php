<?php

namespace App\Services\Auth;

use App\Models\AuditLog;
use App\Models\Cliente\ClienteDetalle;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorFiscalProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Servicio de registro de usuarios y solicitudes de vendedor.
 */
class RegistroService
{
    /**
     * Registra un usuario y sus perfiles asociados.
     *
     * @param array<string, mixed> $data
     * @return User
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'],
                'email' => $data['email'],
                'email_verified_at' => null,
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'status' => 'active',
                'is_system_user' => false,
                'two_factor_enabled' => false,
            ]);

            $role = $data['role'] ?? 'cliente';
            $user->assignRole($role);

            if ($role === 'vendedor') {
                $this->createVendorRequest($user, $data);
            } else {
                $this->createClienteDetalle($user, $data);
            }

            $this->audit($user, 'auth.registered', ['role' => $role]);

            if (method_exists($user, 'sendEmailVerificationNotification')) {
                $user->sendEmailVerificationNotification();
            }

            return $user;
        });
    }

    /**
     * Crea detalle de cliente.
     *
     * @param User $user
     * @param array<string, mixed> $data
     */
    private function createClienteDetalle(User $user, array $data): void
    {
        ClienteDetalle::query()->create([
            'user_id' => $user->id,
            'dpi' => $data['dpi'] ?? null,
            'telefono' => $data['phone'] ?? null,
            'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
            'genero' => $data['genero'] ?? null,
            'preferencias' => $data['preferencias'] ?? null,
            'acepta_marketing' => (bool) ($data['acepta_marketing'] ?? false),
            'terminos_aceptados_at' => now(),
            'privacidad_aceptada_at' => now(),
        ]);
    }

    /**
     * Crea solicitud inicial de vendedor.
     *
     * @param User $user
     * @param array<string, mixed> $data
     */
    private function createVendorRequest(User $user, array $data): void
    {
        $businessName = $data['business_name'] ?? $user->name;
        $vendor = Vendor::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'business_name' => $businessName,
            'slug' => Str::slug($businessName) . '-' . Str::lower(Str::random(6)),
            'descripcion' => $data['descripcion'] ?? null,
            'logo_path' => null,
            'cover_path' => null,
            'telefono_publico' => $data['phone'] ?? null,
            'email_publico' => $user->email,
            'municipio' => $data['municipio'] ?? 'Puerto Barrios',
            'direccion_comercial' => $data['direccion_comercial'] ?? 'Pendiente de completar',
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'is_approved' => false,
            'status' => 'pending',
            'commission_percentage' => 0,
            'monthly_rent' => 0,
            'accepts_cash' => true,
            'accepts_transfer' => true,
            'accepts_card' => true,
        ]);

        $fallbackNit = 'CF-' . $vendor->id;

        VendorFiscalProfile::query()->create([
            'vendor_id' => $vendor->id,
            'nit' => $data['nit'] ?? $fallbackNit,
            'razon_social' => $data['razon_social'] ?? $businessName,
            'direccion_fiscal' => $data['direccion_fiscal'] ?? $vendor->direccion_comercial,
            'regimen_sat' => $data['regimen_sat'] ?? 'general',
            'codigo_establecimiento' => $data['codigo_establecimiento'] ?? 'PEND-' . $vendor->id,
            'certificador_fel' => 'infile',
            'fel_activo' => false,
        ]);
    }

    /**
     * Registra auditoria de registro.
     *
     * @param User $user
     * @param string $event
     * @param array<string, mixed> $metadata
     */
    private function audit(User $user, string $event, array $metadata): void
    {
        AuditLog::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'event' => $event,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'metadata' => $metadata,
            'method' => 'SERVICE',
        ]);
    }
}
