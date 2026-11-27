<?php

namespace App\Services\Fel;

use App\Models\Dte\DteFactura;
use App\Models\Pedido;
use App\Models\User;
use App\Notifications\FacturaDisponibleNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * La tienda emitió la factura por su cuenta (Agencia Virtual de la SAT u otro sistema)
 * y la registra en el pedido: queda en su historial y el cliente la ve en su pedido.
 */
class FacturaManualService
{
    /**
     * Monto que factura la tienda: sus productos con sus descuentos (el envío lo cobra Atlantia).
     */
    public static function montoSugerido(Pedido $pedido): float
    {
        return round(max(0, (float) $pedido->subtotal - (float) $pedido->descuento), 2);
    }

    /**
     * Datos del cliente para copiarlos en la SAT.
     *
     * @return array<string, string>
     */
    public static function datosCliente(Pedido $pedido): array
    {
        $pedido->loadMissing(['pedidoPadre', 'cliente', 'direccion', 'items']);
        $padre = $pedido->pedidoPadre ?? $pedido;
        $direccion = $pedido->direccion ?? $padre->direccion;

        return [
            'NIT' => (string) ($pedido->facturacion_nit ?: $padre->facturacion_nit ?: 'CF'),
            'Nombre' => (string) ($pedido->facturacion_nombre ?: $padre->facturacion_nombre ?: $pedido->cliente?->name ?: 'Consumidor final'),
            'Dirección' => trim(implode(', ', array_filter([$direccion?->direccion_linea_1, $direccion?->zona_o_barrio, $direccion?->municipio]))) ?: 'Ciudad',
            'Correo' => (string) ($pedido->facturacion_email ?: $padre->facturacion_email ?: (str_ends_with((string) $pedido->cliente?->email, '.atlantia.local') ? '' : (string) $pedido->cliente?->email)),
            'Total a facturar' => number_format(self::montoSugerido($pedido), 2, '.', ''),
        ];
    }

    /**
     * @param  array{serie: string, numero: string, autorizacion: string, fecha: string, monto_total: string|float}  $datos
     */
    public function registrar(Pedido $pedido, User $usuario, array $datos, ?UploadedFile $pdf): DteFactura
    {
        if ($pedido->dteFacturas()->where('estado', 'certificado')->exists()) {
            throw ValidationException::withMessages(['autorizacion' => 'Este pedido ya tiene una factura registrada. Quítala primero si necesitas corregirla.']);
        }

        $total = round((float) $datos['monto_total'], 2);
        $iva = round($total - $total / 1.12, 2);
        $ruta = null;

        try {
            return DB::transaction(function () use ($pedido, $usuario, $datos, $pdf, $total, $iva, &$ruta): DteFactura {
                $dte = DteFactura::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'pedido_id' => $pedido->id,
                    'vendor_id' => (int) $pedido->vendor_id,
                    'numero_dte' => 'MAN-'.now()->format('Ymd').'-'.str_pad((string) $pedido->id, 8, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(4)),
                    'uuid_sat' => Str::upper(trim($datos['autorizacion'])),
                    'serie' => Str::upper(trim($datos['serie'])),
                    'numero' => (int) preg_replace('/\D+/', '', $datos['numero']),
                    'tipo_dte' => 'FACT',
                    'origen' => 'manual',
                    'monto_neto' => round($total - $iva, 2),
                    'monto_iva' => $iva,
                    'monto_total' => $total,
                    'moneda' => 'GTQ',
                    'xml_dte' => '',
                    'estado' => 'certificado',
                    'fecha_certificacion' => Carbon::parse($datos['fecha'])->setTimeFrom(now()),
                    'certificador_respuesta' => ['manual' => true, 'registrada_por' => $usuario->id, 'registrada_at' => now()->toIso8601String()],
                ]);

                if ($pdf !== null) {
                    $ruta = 'dte/manual/'.$dte->uuid.'.pdf';
                    Storage::disk((string) config('filesystems.private_disk', 'local'))->putFileAs('dte/manual', $pdf, $dte->uuid.'.pdf');
                    $dte->update(['pdf_path' => $ruta]);
                }

                $pedido->forceFill(['dte_id' => $dte->id])->save();

                DB::afterCommit(function () use ($pedido, $dte): void {
                    try {
                        $pedido->cliente?->notify(new FacturaDisponibleNotification($pedido, $dte));
                    } catch (Throwable $exception) {
                        report($exception);
                    }
                });

                return $dte->refresh();
            });
        } catch (Throwable $exception) {
            if ($ruta !== null) {
                Storage::disk((string) config('filesystems.private_disk', 'local'))->delete($ruta);
            }
            throw $exception;
        }
    }

    /**
     * Quita una factura registrada a mano (por ejemplo, si se escribió mal el número).
     */
    public function quitar(DteFactura $dte): void
    {
        abort_unless($dte->origen === 'manual', 422);

        DB::transaction(function () use ($dte): void {
            Pedido::query()->where('dte_id', $dte->id)->update(['dte_id' => null]);
            if ($dte->pdf_path) {
                Storage::disk((string) config('filesystems.private_disk', 'local'))->delete($dte->pdf_path);
            }
            $dte->items()->delete();
            $dte->forceDelete();
        });
    }
}
