<?php

namespace App\Support;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * La tienda propia de Atlantia y la cuenta con la que se opera desde el panel de vendedor.
 *
 * Atlantia Store usa el mismo panel que cualquier tienda (pedidos, inventario, horario,
 * reseñas, cupones...). Los administradores entran a ese panel con un botón, sin compartir
 * contraseñas; opcionalmente un empleado puede tener acceso directo con su correo.
 */
class TiendaAtlantia
{
    public const CORREO_OPERADOR = 'tienda@atlantia.local';

    public static function slug(): string
    {
        return (string) config('atlantia.vendor_propio_slug', 'atlantia-supermarket');
    }

    public static function vendor(): ?Vendor
    {
        return Vendor::query()->where('slug', self::slug())->first();
    }

    public static function es(?Vendor $vendor): bool
    {
        return $vendor !== null && $vendor->slug === self::slug();
    }

    /**
     * Cuenta de tienda que opera Atlantia Store. Si la tienda está a nombre de un
     * administrador (no vendedor), se crea una cuenta propia y se le pasa la tienda.
     */
    public static function operador(Vendor $vendor): User
    {
        return DB::transaction(function () use ($vendor): User {
            $vendor = Vendor::query()->whereKey($vendor->id)->lockForUpdate()->firstOrFail();
            $actual = $vendor->user;

            if ($actual !== null && $actual->hasRole('vendedor') && ! $actual->isAdministrator()) {
                return $actual;
            }

            $operador = User::withTrashed()->where('email', self::CORREO_OPERADOR)->first()
                ?? User::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'name' => 'Atlantia Store',
                    'email' => self::CORREO_OPERADOR,
                    'password' => Hash::make(Str::random(48)),
                    'status' => 'active',
                    'is_system_user' => true,
                    'two_factor_enabled' => false,
                ]);

            if ($operador->trashed()) {
                $operador->restore();
            }

            $operador->forceFill(['email_verified_at' => $operador->email_verified_at ?? now(), 'status' => 'active'])->save();

            if (Role::query()->where('name', 'vendedor')->exists() && ! $operador->hasRole('vendedor')) {
                $operador->assignRole('vendedor');
            }

            $vendor->forceFill(['user_id' => $operador->id])->save();

            return $operador->fresh();
        });
    }
}
