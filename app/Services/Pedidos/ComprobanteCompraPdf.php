<?php

namespace App\Services\Pedidos;

use App\Models\Pedido;
use App\Models\Vendor;
use App\Support\Pdf\DocumentoPdf;
use App\Support\TiendaAtlantia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Comprobante de compra (no fiscal) de la parte de una tienda en un pedido.
 * Siempre disponible para el cliente, con el logo de la tienda. Dice claramente que no es factura.
 */
class ComprobanteCompraPdf
{
    public function generar(Pedido $parte): string
    {
        $parte->loadMissing(['vendor.fiscalProfile', 'items', 'cliente', 'direccion', 'pedidoPadre.payments', 'payments', 'dteFacturas']);
        $factura = $parte->dteFacturas->firstWhere('estado', 'certificado');
        $vendor = $parte->vendor;
        $padre = $parte->pedidoPadre ?? $parte;
        $perfil = $vendor?->fiscalProfile;
        $pdf = new DocumentoPdf;

        $vino = [0.37, 0.0, 0.03];
        $tinta = [0.13, 0.10, 0.12];
        $gris = [0.45, 0.42, 0.44];

        /* ===== Encabezado: logo + datos de la tienda (izquierda) y datos del comprobante (derecha) ===== */
        $tieneLogo = ($ruta = self::logoDe($vendor)) !== null && $pdf->imagen($ruta, 40, 704, 110, 52);
        $xDatos = $tieneLogo ? 162 : 40;
        $pdf->color(...$tinta)->texto($xDatos, 744, 12.5, Str::limit((string) ($vendor?->business_name ?? 'Atlantia Store'), 30), true);
        $y = 731;
        foreach (array_slice(array_values(array_filter([
            $perfil?->razon_social && $perfil->razon_social !== $vendor?->business_name ? Str::limit($perfil->razon_social, 40) : null,
            $perfil?->nit ? 'NIT: '.$perfil->nit : null,
            Str::limit((string) ($perfil?->direccion_fiscal ?: $vendor?->direccion_comercial ?: ''), 42) ?: null,
            $vendor?->telefono_publico ? 'Tel. '.$vendor->telefono_publico : null,
        ])), 0, 4) as $linea) {
            $pdf->color(...$gris)->texto($xDatos, $y, 8, $linea);
            $y -= 10.5;
        }

        $pdf->color(...$vino)->textoDerecha(572, 744, 14, 'COMPROBANTE DE COMPRA', true);
        $pdf->color(...$tinta)->textoDerecha(572, 729, 9, 'Pedido #'.$padre->numero_pedido, true);
        $pdf->color(...$gris)->textoDerecha(572, 717, 8, ($parte->created_at?->format('d/m/Y h:i a') ?? now()->format('d/m/Y h:i a')));
        $pdf->color(...$gris)->textoDerecha(572, 706, 8, 'Estado: '.self::estado($parte->estadoValor()));

        $pdf->colorLinea(0.37, 0.0, 0.03)->linea(40, 690, 572, 690, 1.2);

        /* ===== Recuadros: cliente y pago/entrega ===== */
        $recuadro = function (float $x, string $titulo, array $lineas) use ($pdf, $vino, $tinta, $gris): void {
            $pdf->color(0.99, 0.96, 0.96)->rect($x, 604, 260, 74);
            $pdf->colorLinea(0.90, 0.80, 0.84)->rect($x, 604, 260, 74, 'S');
            $pdf->color(...$vino)->texto($x + 10, 664, 8, $titulo, true);
            $yy = 650;
            foreach ($lineas as $i => [$texto, $negrita]) {
                $pdf->color(...($i === 0 ? $tinta : $gris))->texto($x + 10, $yy, $i === 0 ? 9.5 : 8, $texto, $negrita);
                $yy -= 12;
            }
        };

        $direccion = $parte->tipo_entrega === 'recoger'
            ? 'Recoge en la tienda'
            : trim(implode(', ', array_filter([$parte->direccion?->direccion_linea_1, $parte->direccion?->zona_o_barrio, $parte->direccion?->municipio])));
        $lineasDireccion = array_slice($pdf->partir($direccion ?: 'Sin dirección registrada', 238, 8), 0, 2);
        $recuadro(40, 'CLIENTE', array_merge([
            [Str::limit((string) ($parte->facturacion_nombre ?: $padre->facturacion_nombre ?: $parte->cliente?->name ?: 'Consumidor final'), 44), true],
            ['NIT: '.($parte->facturacion_nit ?: $padre->facturacion_nit ?: 'CF'), false],
        ], array_map(fn ($l) => [$l, false], $lineasDireccion)));

        $pago = $padre->payments->sortByDesc('id')->first();
        $recuadro(312, 'PAGO Y ENTREGA', [
            [self::metodo((string) ($pago?->metodo?->value ?? $pago?->metodo ?? $parte->metodoPagoValor())), true],
            ['Estado del pago: '.self::estadoPago((string) ($pago?->estadoValor() ?? $parte->estadoPagoValor())), false],
            [$parte->tipo_entrega === 'recoger' ? 'Entrega: recoger en la tienda' : 'Entrega: a domicilio', false],
        ]);

        /* ===== Productos ===== */
        $y = 578;
        $pdf->color(...$vino)->rect(40, $y - 6, 532, 20);
        $pdf->color(1, 1, 1)->texto(50, $y, 8, 'CANT.', true)->texto(92, $y, 8, 'DESCRIPCIÓN', true)
            ->textoDerecha(470, $y, 8, 'PRECIO', true)->textoDerecha(562, $y, 8, 'SUBTOTAL', true);
        $y -= 22;

        foreach ($parte->items as $item) {
            if ($y < 170) {
                $pdf->nuevaPagina();
                $y = 740;
            }
            $lineas = array_slice($pdf->partir((string) $item->producto_nombre_snapshot, 300, 9), 0, 2);
            $pdf->color(...$tinta)->textoCentrado(64, $y, 9, (string) (float) $item->cantidad);
            foreach ($lineas as $i => $linea) {
                $pdf->texto(92, $y - $i * 11, 9, $linea);
            }
            $pdf->textoDerecha(470, $y, 9, 'Q'.number_format((float) $item->precio_unitario_snapshot, 2))
                ->textoDerecha(562, $y, 9, 'Q'.number_format((float) $item->subtotal - (float) $item->descuento, 2), true);
            $y -= 11 * count($lineas) + 7;
            $pdf->colorLinea(0.92, 0.88, 0.90)->linea(40, $y + 5, 572, $y + 5, 0.5);
        }

        /* ===== Totales (cada fila con su espacio; el total va debajo, sin taparlas) ===== */
        if ($y < 210) {
            $pdf->nuevaPagina();
            $y = 740;
        }
        $y -= 12;
        foreach (array_filter([
            ['Subtotal', (float) $parte->subtotal],
            (float) $parte->descuento > 0 ? ['Descuento', -(float) $parte->descuento] : null,
            (float) $parte->envio > 0 ? ['Envío', (float) $parte->envio] : null,
            (float) $parte->propina > 0 ? ['Propina para el repartidor', (float) $parte->propina] : null,
        ]) as [$etiqueta, $monto]) {
            $pdf->color(...$gris)->textoDerecha(470, $y, 9, $etiqueta)
                ->color(...$tinta)->textoDerecha(562, $y, 9, ($monto < 0 ? '- ' : '').'Q'.number_format(abs($monto), 2));
            $y -= 15;
        }
        $y -= 10;
        $pdf->color(...$vino)->rect(372, $y - 8, 200, 26);
        $pdf->color(1, 1, 1)->texto(384, $y, 11, 'TOTAL', true)->textoDerecha(562, $y, 12.5, 'Q'.number_format((float) $parte->total, 2), true);
        $pdf->color(...$gris)->textoDerecha(572, $y - 22, 7.5, 'Precios con IVA incluido.');

        /* ===== Pie en un solo bloque: QR para encontrar el pedido, aviso y ayuda ===== */
        $pdf->color(0.99, 0.96, 0.96)->rect(40, 38, 532, 96);
        $pdf->colorLinea(0.37, 0.0, 0.03)->rect(40, 38, 532, 96, 'S');
        $pdf->color(...$vino)->rect(40, 38, 4, 96);
        $pdf->qr(self::urlPedido($padre), 52, 46, 80);
        $xTexto = 146;
        $pdf->color(...$vino)->texto($xTexto, 114, 9, $factura !== null
            ? 'Este documento no es una factura. Tu factura FEL está en tu pedido.'
            : 'Este documento es un comprobante de compra y no sustituye una factura.', true);
        $pdf->color(...$tinta)->texto($xTexto, 99, 8.5, 'Escanea el código para ver tu pedido, este comprobante y tu factura en Atlantia Store.');
        $pdf->color(...$gris)->texto($xTexto, 86, 8, '¿Necesitas factura? Pídela a la tienda indicando tu NIT; cuando la emita aparecerá en tu pedido.');
        if ($soporte = config('atlantia.support.phone')) {
            $pdf->texto($xTexto, 73, 8, 'Ayuda: '.$soporte.(config('atlantia.support.email') ? ' · '.config('atlantia.support.email') : ''));
        }
        $pdf->color(0.6, 0.56, 0.58)->texto($xTexto, 54, 7, 'Compra realizada en Atlantia Store · atlantiasystems.com · Generado el '.now()->format('d/m/Y h:i a'));

        return $pdf->salida();
    }

    /**
     * Enlace del QR: el pedido en Atlantia Store (para invitados, un enlace firmado que lo abre sin cuenta).
     */
    public static function urlPedido(Pedido $padre): string
    {
        if (! $padre->uuid) {
            return route('home');
        }

        return $padre->cliente_id === null || str_ends_with((string) $padre->cliente?->email, '@invitados.atlantia.local')
            ? \Illuminate\Support\Facades\URL::signedRoute('cliente.pedidos.guest-show', $padre)
            : route('cliente.pedidos.show', $padre);
    }

    /**
     * Ruta del logo de la tienda (o el de Atlantia para su tienda propia), si existe.
     */
    public static function logoDe(?Vendor $vendor): ?string
    {
        $ruta = $vendor?->logo_path;
        if ($ruta && ! Str::startsWith($ruta, ['http://', 'https://'])) {
            $relativa = Str::after(ltrim(Str::after($ruta, 'storage/'), '/'), 'public/');
            if (Storage::disk('public')->exists($relativa)) {
                return Storage::disk('public')->path($relativa);
            }
        }

        if (TiendaAtlantia::es($vendor) || $vendor === null) {
            foreach (['images/logo-atlantia-delivery.png', 'images/logo.png'] as $logo) {
                if (is_file(public_path($logo))) {
                    return public_path($logo);
                }
            }
        }

        return null;
    }

    private static function metodo(string $metodo): string
    {
        return match ($metodo) {
            'tarjeta' => 'Tarjeta',
            'transferencia' => 'Transferencia',
            'efectivo' => 'Efectivo contra entrega',
            default => ucfirst($metodo ?: 'Por definir'),
        };
    }

    private static function estadoPago(string $estado): string
    {
        return match ($estado) {
            'aprobado', 'pagado' => 'Pagado',
            'reembolsado' => 'Devuelto',
            'anulado', 'rechazado' => 'Anulado',
            'validando' => 'En validación',
            default => 'Pendiente (se paga al recibir)',
        };
    }

    private static function estado(string $estado): string
    {
        return match ($estado) {
            'pendiente', 'en_revision' => 'Recibido',
            'confirmado' => 'Confirmado',
            'preparando' => 'En preparación',
            'listo_para_entrega' => 'Listo',
            'en_ruta' => 'En camino',
            'entregado' => 'Entregado',
            'cancelado', 'rechazado' => 'Cancelado',
            default => ucfirst($estado),
        };
    }
}
