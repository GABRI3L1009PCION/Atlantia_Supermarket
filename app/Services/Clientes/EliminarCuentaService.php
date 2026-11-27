<?php

namespace App\Services\Clientes;

use App\Enums\EstadoPedido;
use App\Exceptions\PagoRechazadoException;
use App\Models\AuditLog;
use App\Models\Carrito;
use App\Models\Cliente\ClienteDetalle;
use App\Models\Cliente\Direccion;
use App\Models\Pedido;
use App\Models\PushSuscripcion;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\Pagos\Recurrente\RecurrenteClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

/**
 * El cliente elimina su cuenta: se borran sus datos personales y la cuenta deja de existir.
 * Los pedidos y facturas se conservan (lo exige la ley contable), pero sin su nombre ni contacto.
 */
class EliminarCuentaService
{
    /**
     * Por qué todavía no se puede eliminar (null = sí se puede).
     */
    public function impedimento(User $user): ?string
    {
        $activos = Pedido::query()->where('cliente_id', $user->id)->whereNull('pedido_padre_id')
            ->whereNotIn('estado', [EstadoPedido::Entregado->value, EstadoPedido::Cancelado->value])
            ->count();

        return $activos > 0
            ? 'Tienes '.($activos === 1 ? 'un pedido en curso' : $activos.' pedidos en curso').'. Espera a recibirlo o cancélalo antes de eliminar tu cuenta.'
            : null;
    }

    public function eliminar(User $user): void
    {
        /* Primero se detienen los cobros de la membresía, fuera de la transacción (llama a la pasarela). */
        if (filled($user->membresia_suscripcion_id) && RecurrenteClient::habilitado()) {
            try {
                app(RecurrenteClient::class)->cancelarSuscripcion((string) $user->membresia_suscripcion_id);
            } catch (PagoRechazadoException $exception) {
                throw new PagoRechazadoException('No pudimos cancelar los cobros de tu membresía. Intenta de nuevo en unos minutos.', previous: $exception);
            }
        }

        DB::transaction(function () use ($user): void {
            /* Las direcciones siguen ligadas a pedidos anteriores: se vacían en lugar de borrarse. */
            Direccion::query()->withTrashed()->where('user_id', $user->id)->get()->each(function (Direccion $direccion): void {
                $direccion->forceFill([
                    'alias' => 'Eliminada', 'nombre_contacto' => 'Cliente eliminado', 'telefono_contacto' => '00000000',
                    'direccion_linea_1' => 'Dirección eliminada', 'direccion_linea_2' => null,
                    'referencia' => null, 'latitude' => null, 'longitude' => null, 'mapbox_place_id' => null,
                    'es_principal' => false, 'activa' => false,
                ])->save();
                if (! $direccion->trashed()) {
                    $direccion->delete();
                }
            });

            ClienteDetalle::query()->where('user_id', $user->id)->delete();
            Wishlist::query()->where('user_id', $user->id)->delete();
            PushSuscripcion::query()->where('user_id', $user->id)->delete();
            Carrito::query()->where('user_id', $user->id)->get()->each(function (Carrito $carrito): void {
                $carrito->items()->delete();
                $carrito->delete();
            });
            $user->notifications()->delete();

            try {
                DB::table('oauth_access_tokens')->where('user_id', $user->id)->update(['revoked' => true]);
            } catch (Throwable) {
                /* Sin tabla de tokens (pruebas): no hay nada que revocar. */
            }

            $sello = (string) Str::uuid();
            $user->forceFill([
                'name' => 'Cliente eliminado',
                'email' => 'eliminado-'.$sello.'@eliminados.atlantia.local',
                'phone' => null,
                'password' => Hash::make(Str::random(64)),
                'remember_token' => Str::random(60),
                'status' => 'inactive',
                'last_login_ip' => null,
                'two_factor_enabled' => false,
                'recurrente_customer_id' => null,
                'recurrente_metodo_pago_id' => null,
                'tarjeta_descripcion' => null,
                'membresia_suscripcion_id' => null,
            ])->save();

            AuditLog::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'event' => 'cuenta.eliminada_por_cliente',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'metadata' => ['motivo' => 'Solicitud del cliente desde su perfil'],
                'method' => 'SERVICE',
            ]);

            $user->delete();
        });
    }
}
