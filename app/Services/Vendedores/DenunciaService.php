<?php

namespace App\Services\Vendedores;

use App\Models\DenunciaTienda;
use App\Models\Pedido;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\DenunciaTiendaNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * Denuncias de clientes contra tiendas. Con varias denuncias de clientes distintos en pocos días,
 * la tienda se pausa sola (no recibe pedidos) y sus pagos se retienen hasta que Atlantia revise.
 */
class DenunciaService
{
    public const MOTIVO_PAUSA = 'En revisión por denuncias de clientes';

    /**
     * @param  array{motivo: string, descripcion: string, pedido_uuid?: string|null}  $datos
     */
    public function crear(Vendor $vendor, User $cliente, array $datos): DenunciaTienda
    {
        if (DenunciaTienda::query()->where('vendor_id', $vendor->id)->where('cliente_id', $cliente->id)->where('estado', 'pendiente')->exists()) {
            throw ValidationException::withMessages(['descripcion' => 'Ya tienes una denuncia en revisión contra esta tienda. Te avisaremos cuando la revisemos.']);
        }

        $pedido = filled($datos['pedido_uuid'] ?? null)
            ? Pedido::query()->where('uuid', $datos['pedido_uuid'])->where('cliente_id', $cliente->id)->where('vendor_id', $vendor->id)->first()
            : null;

        $denuncia = DB::transaction(function () use ($vendor, $cliente, $datos, $pedido): DenunciaTienda {
            $denuncia = DenunciaTienda::query()->create([
                'uuid' => (string) Str::uuid(),
                'vendor_id' => $vendor->id,
                'cliente_id' => $cliente->id,
                'pedido_id' => $pedido?->id,
                'motivo' => $datos['motivo'],
                'descripcion' => trim($datos['descripcion']),
                'estado' => 'pendiente',
            ]);

            $this->evaluarPausa($vendor);

            return $denuncia;
        });

        $this->avisarAdministradores($denuncia->fresh('vendor'));

        return $denuncia;
    }

    /**
     * Pausa la tienda y retiene sus pagos si acumula denuncias de varios clientes en pocos días.
     */
    public function evaluarPausa(Vendor $vendor): bool
    {
        $minimo = (int) config('atlantia.vendedores.denuncias_para_pausa', 3);
        $clientes = DenunciaTienda::query()->where('vendor_id', $vendor->id)->where('estado', '!=', 'descartada')
            ->where('created_at', '>=', now()->subDays((int) config('atlantia.vendedores.dias_denuncias', 7)))
            ->distinct()->count('cliente_id');

        if ($clientes < $minimo || $vendor->pagos_retenidos_at !== null) {
            return false;
        }

        $vendor->forceFill([
            'pausado_hasta' => now()->addYears(5),
            'pausa_motivo' => self::MOTIVO_PAUSA,
            'pagos_retenidos_at' => now(),
            'motivo_retencion' => self::MOTIVO_PAUSA,
        ])->save();

        return true;
    }

    /**
     * Atlantia revisó la tienda y la deja operar de nuevo.
     */
    public function reactivar(Vendor $vendor): void
    {
        $vendor->forceFill(array_merge(
            ['pagos_retenidos_at' => null, 'motivo_retencion' => null],
            $vendor->pausa_motivo === self::MOTIVO_PAUSA ? ['pausado_hasta' => null, 'pausa_motivo' => null] : [],
        ))->save();
    }

    public function resolver(DenunciaTienda $denuncia, User $revisor, string $estado, ?string $resolucion): DenunciaTienda
    {
        $denuncia->update([
            'estado' => $estado,
            'resolucion' => filled($resolucion) ? trim((string) $resolucion) : null,
            'revisada_por' => $revisor->id,
            'revisada_at' => now(),
        ]);

        return $denuncia->refresh();
    }

    private function avisarAdministradores(DenunciaTienda $denuncia): void
    {
        try {
            $roles = Role::query()->whereIn('name', ['super_admin', 'admin'])->pluck('name')->all();
            if ($roles !== []) {
                Notification::send(User::query()->role($roles)->get(), new DenunciaTiendaNotification($denuncia));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
