<?php

namespace App\Services\Fel;

use App\Exceptions\DteCertificadorException;
use App\Exceptions\TransaccionFallidaException;
use App\Models\Dte\DteFactura;
use App\Models\Pedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleXMLElement;
use Throwable;

/**
 * Servicio de generacion y certificacion de DTE.
 */
class DteGeneradorService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(private readonly InfileCertificadorService $certificadorFel)
    {
    }

    /**
     * Genera y certifica factura FEL para un pedido de vendedor.
     *
     * @param Pedido $pedido
     * @return DteFactura
     *
     * @throws DteCertificadorException
     * @throws TransaccionFallidaException
     */
    public function emitirParaPedido(Pedido $pedido): DteFactura
    {
        try {
            return DB::transaction(function () use ($pedido): DteFactura {
                $pedido->loadMissing(['vendor.fiscalProfile', 'items.producto', 'cliente']);

                if ($pedido->dte_id !== null) {
                    return DteFactura::query()->findOrFail($pedido->dte_id);
                }

                $this->validarPedidoFacturable($pedido);

                $dte = DteFactura::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'pedido_id' => $pedido->id,
                    'vendor_id' => (int) $pedido->vendor_id,
                    'numero_dte' => $this->numeroInterno($pedido),
                    'tipo_dte' => 'FACT',
                    'monto_neto' => $pedido->subtotal,
                    'monto_iva' => $pedido->impuestos,
                    'monto_total' => $pedido->total,
                    'moneda' => 'GTQ',
                    'xml_dte' => '',
                    'estado' => 'borrador',
                ]);

                foreach ($pedido->items as $item) {
                    $dte->items()->create([
                        'producto_id' => $item->producto_id,
                        'descripcion' => $item->producto_nombre_snapshot,
                        'cantidad' => $item->cantidad,
                        'precio_unitario' => $item->precio_unitario_snapshot,
                        'descuento' => $item->descuento,
                        'monto_iva' => $item->impuestos,
                        'monto_total' => $item->subtotal + $item->impuestos - $item->descuento,
                    ]);
                }

                $dte->update(['xml_dte' => $this->generarXml($dte->fresh(['vendor.fiscalProfile', 'pedido.cliente', 'items']))]);
                $respuesta = $this->certificadorFel->certificar($dte->fresh(['vendor.fiscalProfile']));
                $dte->update([
                    'uuid_sat' => $respuesta['uuid_sat'] ?? null,
                    'serie' => $respuesta['serie'] ?? null,
                    'numero' => $respuesta['numero'] ?? null,
                    'pdf_path' => $respuesta['pdf_path'] ?? null,
                    'estado' => $respuesta['estado'] === 'certificado' ? 'certificado' : 'rechazado',
                    'fecha_certificacion' => $respuesta['fecha_certificacion'] ?? now(),
                    'certificador_respuesta' => $respuesta,
                ]);

                $pedido->update(['dte_id' => $dte->id]);

                return $dte->refresh();
            });
        } catch (DteCertificadorException|TransaccionFallidaException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new TransaccionFallidaException('No fue posible emitir el DTE.', previous: $exception);
        }
    }

    /**
     * Genera XML fiscal base compatible con el flujo FEL.
     *
     * @param DteFactura $dte
     * @return string
     */
    public function generarXml(DteFactura $dte): string
    {
        $dte->loadMissing(['vendor.fiscalProfile', 'pedido.cliente', 'items']);
        $profile = $dte->vendor->fiscalProfile;
        $xml = new SimpleXMLElement('<DTE/>');
        $datos = $xml->addChild('DatosEmision');

        $emisor = $datos->addChild('Emisor');
        $emisor->addChild('NITEmisor', htmlspecialchars((string) $profile->nit));
        $emisor->addChild('NombreEmisor', htmlspecialchars($profile->razon_social));
        $emisor->addChild('CodigoEstablecimiento', htmlspecialchars($profile->codigo_establecimiento));
        $emisor->addChild('AfiliacionIVA', htmlspecialchars($profile->afiliacion_iva));

        $receptor = $datos->addChild('Receptor');
        $receptor->addChild('NombreReceptor', htmlspecialchars($dte->pedido->cliente->name));
        $receptor->addChild('CorreoReceptor', htmlspecialchars($dte->pedido->cliente->email));

        $items = $datos->addChild('Items');
        foreach ($dte->items as $index => $item) {
            $xmlItem = $items->addChild('Item');
            $xmlItem->addAttribute('NumeroLinea', (string) ($index + 1));
            $xmlItem->addChild('Descripcion', htmlspecialchars($item->descripcion));
            $xmlItem->addChild('Cantidad', (string) $item->cantidad);
            $xmlItem->addChild('PrecioUnitario', (string) $item->precio_unitario);
            $xmlItem->addChild('Descuento', (string) $item->descuento);
            $xmlItem->addChild('MontoIVA', (string) $item->monto_iva);
            $xmlItem->addChild('MontoTotal', (string) $item->monto_total);
        }

        $totales = $datos->addChild('Totales');
        $totales->addChild('MontoNeto', (string) $dte->monto_neto);
        $totales->addChild('MontoIVA', (string) $dte->monto_iva);
        $totales->addChild('MontoTotal', (string) $dte->monto_total);

        return $xml->asXML() ?: '';
    }

    /**
     * Valida que el pedido tenga datos minimos para FEL.
     *
     * @param Pedido $pedido
     * @return void
     *
     * @throws DteCertificadorException
     */
    private function validarPedidoFacturable(Pedido $pedido): void
    {
        if ($pedido->vendor_id === null || $pedido->vendor?->fiscalProfile === null) {
            throw new DteCertificadorException('El pedido no tiene vendedor con perfil fiscal FEL.');
        }

        if (! $pedido->vendor->fiscalProfile->fel_activo) {
            throw new DteCertificadorException('El perfil FEL del vendedor no esta activo.');
        }

        if ($pedido->items->isEmpty()) {
            throw new DteCertificadorException('El pedido no tiene items facturables.');
        }
    }

    /**
     * Genera correlativo interno Atlantia para DTE.
     *
     * @param Pedido $pedido
     * @return string
     */
    private function numeroInterno(Pedido $pedido): string
    {
        return 'DTE-' . now()->format('Ymd') . '-' . str_pad((string) $pedido->id, 8, '0', STR_PAD_LEFT);
    }
}
