<?php

namespace App\Services\Fel;

use App\Models\Dte\DteFactura;
use Illuminate\Support\Facades\Storage;

/**
 * Genera el comprobante PDF interno para DTE emulados.
 */
class DteComprobantePdf
{
    /**
     * Genera y guarda el PDF publico del DTE.
     */
    public function store(DteFactura $dte): string
    {
        $dte->loadMissing(['vendor.fiscalProfile', 'pedido.cliente', 'pedido.direccion', 'items.producto']);

        $path = 'dte/pdf/' . $dte->uuid . '.pdf';

        Storage::disk('public')->put($path, $this->output($dte));
        $dte->update(['pdf_path' => $path]);

        return $path;
    }

    /**
     * Devuelve los bytes PDF.
     */
    public function output(DteFactura $dte): string
    {
        $dte->loadMissing(['vendor.fiscalProfile', 'pedido.cliente', 'pedido.direccion', 'items.producto']);

        $commands = [];
        $pedido = $dte->pedido;
        $cliente = $pedido?->cliente;
        $direccion = $pedido?->direccion;
        $vendor = $dte->vendor;
        $profile = $vendor?->fiscalProfile;
        $isMock = (bool) data_get($dte->certificador_respuesta, 'respuesta_original.mock', data_get($dte->certificador_respuesta, 'mock', false));
        $receptorNombre = (string) ($pedido?->facturacion_nombre ?: $cliente?->name ?: 'Consumidor final');
        $receptorNit = (string) ($pedido?->facturacion_nit ?: 'CF');
        $receptorEmail = (string) ($pedido?->facturacion_email ?: $cliente?->email ?: 'Sin correo');
        $receptorDireccion = trim((string) ($direccion?->direccion_linea_1 ?? '')) ?: 'Direccion no registrada';
        $metodoPago = ucfirst(str_replace('_', ' ', (string) ($pedido?->metodoPagoValor() ?? 'pendiente')));
        $fecha = optional($dte->fecha_certificacion ?? $dte->created_at)->format('d/m/Y H:i:s') ?: now()->format('d/m/Y H:i:s');
        $serie = (string) ($dte->serie ?? 'AS');
        $numero = (string) ($dte->numero ?? abs(crc32($dte->numero_dte)));
        $uuidSat = (string) ($dte->uuid_sat ?? $dte->uuid);

        $this->fill($commands, 0.97, 0.93, 0.95);
        $this->rect($commands, 0, 0, 612, 792, 'f');

        $this->fill($commands, 1, 1, 1);
        $this->stroke($commands, 0.74, 0.14, 0.30);
        $this->rect($commands, 24, 22, 564, 748, 'B');

        $this->fill($commands, 0.55, 0.06, 0.24);
        $this->text($commands, 52, 720, 46, 'A', true);
        $this->fill($commands, 0.07, 0.12, 0.22);
        $this->text($commands, 116, 724, 28, 'ATLANTIA', true);
        $this->fill($commands, 0.55, 0.06, 0.24);
        $this->text($commands, 121, 704, 11, 'SUPERMARKET', true);
        $this->fill($commands, 0.18, 0.18, 0.18);
        $this->text($commands, 116, 688, 8.5, 'Calidad que se siente, ahorro que se ve.');

        $this->fill($commands, 0.55, 0.06, 0.24);
        $this->text($commands, 330, 724, 18, 'FACTURA ELECTRONICA FEL', true);
        $this->fill($commands, 0.07, 0.12, 0.22);
        $this->text($commands, 352, 704, 10, 'Documento Tributario Electronico');
        $this->stroke($commands, 0.55, 0.06, 0.24);
        $this->rect($commands, 518, 690, 50, 55, 'B');
        $this->fill($commands, 0.55, 0.06, 0.24);
        $this->rect($commands, 518, 723, 50, 22, 'f');
        $this->fill($commands, 1, 1, 1);
        $this->text($commands, 536, 730, 10, 'DTE', true);
        $this->fill($commands, 0.09, 0.07, 0.09);
        $this->text($commands, 530, 708, 17, 'FACT', true);
        $this->text($commands, 529, 696, 8, '(FACTURA)', true);

        $this->line($commands, 46, 674, 288, 674);

        $this->box($commands, 46, 538, 242, 126, 'EMISOR');
        $this->text($commands, 58, 634, 10, $profile?->razon_social ?? $vendor?->business_name ?? 'Atlantia Supermarket', true);
        $this->text($commands, 58, 618, 8.5, 'NIT: ' . ($profile?->nit ?? 'CF'));
        $this->text($commands, 58, 604, 8.5, 'Direccion: ' . $this->clip((string) ($profile?->direccion_fiscal ?? $vendor?->direccion_comercial ?? 'Izabal, Guatemala'), 43));
        $this->text($commands, 58, 590, 8.5, 'Ciudad: ' . ($vendor?->municipio ?? 'Puerto Barrios') . ', Guatemala');
        $this->text($commands, 58, 576, 8.5, 'Telefono: ' . ($vendor?->telefono_publico ?? '(502) 0000-0000'));
        $this->text($commands, 58, 562, 8.5, 'Correo: ' . ($vendor?->email_publico ?? 'facturacion@atlantia.com.gt'));
        $this->text($commands, 58, 548, 8.5, 'Regimen: ' . ($profile?->regimen_sat ?? 'General'));

        $this->box($commands, 306, 538, 262, 126, 'DATOS DEL DTE');
        $this->text($commands, 320, 636, 8.5, 'Serie:', true);
        $this->text($commands, 434, 636, 8.5, $serie);
        $this->text($commands, 320, 620, 8.5, 'Numero de DTE:', true);
        $this->text($commands, 434, 620, 8.5, $numero);
        $this->text($commands, 320, 604, 8.5, 'Numero de acceso:', true);
        $this->text($commands, 434, 604, 8.5, substr(hash('sha256', $dte->numero_dte), 0, 28));
        $this->text($commands, 320, 588, 8.5, 'UUID:', true);
        $this->text($commands, 434, 588, 8.5, $this->clip($uuidSat, 34));
        $this->text($commands, 320, 572, 8.5, 'Fecha emision:', true);
        $this->text($commands, 434, 572, 8.5, $fecha);
        $this->text($commands, 320, 556, 8.5, 'Moneda:', true);
        $this->text($commands, 434, 556, 8.5, $dte->moneda);

        $this->box($commands, 46, 438, 262, 84, 'RECEPTOR / CLIENTE');
        $this->text($commands, 58, 494, 8.5, 'Nombre o Razon Social:', true);
        $this->text($commands, 174, 494, 8.5, $this->clip($receptorNombre, 30));
        $this->text($commands, 58, 478, 8.5, 'NIT / CF:', true);
        $this->text($commands, 174, 478, 8.5, $receptorNit);
        $this->text($commands, 58, 462, 8.5, 'Direccion:', true);
        $this->text($commands, 174, 462, 8.5, $this->clip($receptorDireccion, 32));
        $this->text($commands, 58, 446, 8.5, 'Correo:', true);
        $this->text($commands, 174, 446, 8.5, $this->clip($receptorEmail, 32));

        $this->box($commands, 322, 438, 246, 84, 'CONDICIONES DE PAGO');
        $this->text($commands, 336, 494, 8.5, 'Condicion de Pago:', true);
        $this->text($commands, 444, 494, 8.5, $pedido?->metodoPagoValor() === 'efectivo' ? 'Contado' : 'Confirmacion de pago');
        $this->text($commands, 336, 478, 8.5, 'Metodo de Pago:', true);
        $this->text($commands, 444, 478, 8.5, $metodoPago);
        $this->text($commands, 336, 462, 8.5, 'Pedido:', true);
        $this->text($commands, 444, 462, 8.5, $pedido?->numero_pedido ?? 'N/D');
        $this->text($commands, 336, 446, 8.5, 'Estado:', true);
        $this->text($commands, 444, 446, 8.5, $isMock ? 'Emulado' : 'Certificado');

        $this->fill($commands, 0.55, 0.06, 0.24);
        $this->rect($commands, 46, 406, 522, 22, 'f');
        $this->fill($commands, 1, 1, 1);
        $this->text($commands, 58, 413, 8, 'No.', true);
        $this->text($commands, 91, 413, 8, 'Cantidad', true);
        $this->text($commands, 147, 413, 8, 'Unidad', true);
        $this->text($commands, 208, 413, 8, 'Descripcion', true);
        $this->text($commands, 385, 413, 8, 'Precio Unitario', true);
        $this->text($commands, 468, 413, 8, 'Subtotal', true);

        $y = 388;
        foreach ($dte->items->take(8) as $index => $item) {
            $this->stroke($commands, 0.86, 0.78, 0.82);
            $this->line($commands, 46, $y - 8, 568, $y - 8);
            $this->fill($commands, 0.10, 0.08, 0.10);
            $this->text($commands, 60, $y, 8, (string) ($index + 1));
            $this->text($commands, 96, $y, 8, number_format((float) $item->cantidad, 2));
            $this->text($commands, 152, $y, 8, 'Unidad');
            $this->text($commands, 208, $y, 8, $this->clip((string) $item->descripcion, 33), true);
            $this->text($commands, 392, $y, 8, 'Q ' . number_format((float) $item->precio_unitario, 2));
            $this->text($commands, 478, $y, 8, 'Q ' . number_format((float) $item->monto_total, 2));
            $y -= 23;
        }

        $this->stroke($commands, 0.55, 0.06, 0.24);
        $this->line($commands, 46, 214, 568, 214);
        $this->fill($commands, 0.55, 0.06, 0.24);
        $this->text($commands, 46, 198, 9, 'DATOS / FRASES TRIBUTARIAS', true);
        $this->fill($commands, 0.12, 0.10, 0.12);
        $this->text($commands, 46, 180, 7.5, 'Contribuyendo por el pais que todos queremos.');
        $this->text($commands, 46, 166, 7.5, $isMock ? 'DOCUMENTO EMULADO PARA PRUEBAS - NO ES CERTIFICACION SAT REAL.' : 'Documento certificado electronicamente.');
        $this->text($commands, 46, 152, 7.5, 'Centro de costo: 01 - Supermercado');
        $this->text($commands, 46, 138, 7.5, 'Vendedor: ' . $this->clip($vendor?->business_name ?? 'Atlantia Supermarket', 42));

        $this->stroke($commands, 0.55, 0.06, 0.24);
        $this->rect($commands, 318, 132, 250, 82, 'B');
        $this->text($commands, 334, 192, 9, 'SUBTOTAL:', true);
        $this->text($commands, 486, 192, 9, 'Q ' . number_format((float) $dte->monto_neto, 2), true);
        $this->text($commands, 334, 174, 9, 'DESCUENTO TOTAL:', true);
        $this->text($commands, 486, 174, 9, 'Q ' . number_format((float) ($pedido?->descuento ?? 0), 2), true);
        $this->text($commands, 334, 156, 9, 'IVA (12%):', true);
        $this->text($commands, 486, 156, 9, 'Q ' . number_format((float) $dte->monto_iva, 2), true);
        $this->fill($commands, 0.55, 0.06, 0.24);
        $this->rect($commands, 318, 132, 250, 20, 'f');
        $this->fill($commands, 1, 1, 1);
        $this->text($commands, 334, 139, 10, 'TOTAL A PAGAR:', true);
        $this->text($commands, 486, 139, 10, 'Q ' . number_format((float) $dte->monto_total, 2), true);

        $this->box($commands, 46, 58, 522, 58, 'CERTIFICACION ELECTRONICA');
        $this->text($commands, 58, 92, 7.5, $isMock
            ? 'Este comprobante fue emitido por el ambiente emulado de Atlantia para presentacion y pruebas.'
            : 'Esta factura ha sido certificada electronicamente por el certificador FEL configurado.');
        $this->text($commands, 58, 78, 7.5, 'UUID: ' . $this->clip($uuidSat, 54), true);
        $this->pseudoQr($commands, 268, 66, 42, $uuidSat);
        $this->text($commands, 326, 92, 7.5, 'Verificacion del DTE');
        $this->text($commands, 326, 78, 7.5, 'Numero de acceso: ' . substr(hash('sha256', $uuidSat), 0, 24));
        $this->text($commands, 326, 64, 7.5, 'Original: Cliente');

        $this->fill($commands, 0.35, 0.31, 0.35);
        $this->text($commands, 46, 38, 7.5, 'Atlantia Supermarket - Representacion impresa de un DTE');
        $this->fill($commands, 0.55, 0.06, 0.24);
        $this->text($commands, 506, 38, 7.5, 'Pagina 1 de 1', true);

        return $this->pdf(implode("\n", $commands));
    }

    /**
     * Caja de resumen monetario.
     *
     * @param array<int, string> $commands
     */
    private function summaryBox(array &$commands, int $x, int $y, string $label, float $amount): void
    {
        $this->fill($commands, 1, 1, 1);
        $this->stroke($commands, 0.92, 0.78, 0.84);
        $this->rect($commands, $x, $y, 112, 48, 'B');
        $this->fill($commands, 0.35, 0.31, 0.35);
        $this->text($commands, $x + 15, $y + 30, 8.5, $label);
        $this->fill($commands, 0.55, 0.06, 0.24);
        $this->text($commands, $x + 15, $y + 12, 14, 'Q' . number_format($amount, 2), true);
    }

    /**
     * Caja de informacion.
     *
     * @param array<int, string> $commands
     */
    private function box(array &$commands, int $x, int $y, int $w, int $h, string $label): void
    {
        $this->fill($commands, 1, 1, 1);
        $this->stroke($commands, 0.92, 0.78, 0.84);
        $this->rect($commands, $x, $y, $w, $h, 'B');
        $this->fill($commands, 0.55, 0.06, 0.24);
        $this->text($commands, $x + 15, $y + $h - 20, 9, $label, true);
    }

    /**
     * Dibuja un QR visual deterministico para la representacion impresa.
     *
     * @param array<int, string> $commands
     */
    private function pseudoQr(array &$commands, int $x, int $y, int $size, string $seed): void
    {
        $this->fill($commands, 1, 1, 1);
        $this->stroke($commands, 0.92, 0.78, 0.84);
        $this->rect($commands, $x, $y, $size, $size, 'B');

        $hash = hash('sha256', $seed);
        $cell = 4;
        $padding = 3;
        $cursor = 0;

        $this->fill($commands, 0.08, 0.07, 0.08);

        for ($row = 0; $row < 9; $row++) {
            for ($col = 0; $col < 9; $col++) {
                $isFinder = ($row < 3 && $col < 3)
                    || ($row < 3 && $col > 5)
                    || ($row > 5 && $col < 3);
                $hex = hexdec($hash[$cursor % strlen($hash)]);

                if ($isFinder || ($hex + $row + $col) % 3 === 0) {
                    $this->rect(
                        $commands,
                        $x + $padding + ($col * $cell),
                        $y + $padding + ($row * $cell),
                        3,
                        3,
                        'f'
                    );
                }

                $cursor++;
            }
        }
    }

    /**
     * Escribe texto.
     *
     * @param array<int, string> $commands
     */
    private function text(array &$commands, int $x, int $y, float $size, string $text, bool $bold = false): void
    {
        $font = $bold ? 'F2' : 'F1';
        $commands[] = sprintf('BT /%s %.2F Tf %d %d Td (%s) Tj ET', $font, $size, $x, $y, $this->escape($text));
    }

    /**
     * Dibuja rectangulo.
     *
     * @param array<int, string> $commands
     */
    private function rect(array &$commands, int $x, int $y, int $w, int $h, string $operator): void
    {
        $commands[] = sprintf('%d %d %d %d re %s', $x, $y, $w, $h, $operator);
    }

    /**
     * Dibuja linea.
     *
     * @param array<int, string> $commands
     */
    private function line(array &$commands, int $x1, int $y1, int $x2, int $y2): void
    {
        $commands[] = sprintf('%d %d m %d %d l S', $x1, $y1, $x2, $y2);
    }

    /**
     * Color de relleno.
     *
     * @param array<int, string> $commands
     */
    private function fill(array &$commands, float $r, float $g, float $b): void
    {
        $commands[] = sprintf('%.3F %.3F %.3F rg', $r, $g, $b);
    }

    /**
     * Color de trazo.
     *
     * @param array<int, string> $commands
     */
    private function stroke(array &$commands, float $r, float $g, float $b): void
    {
        $commands[] = sprintf('%.3F %.3F %.3F RG', $r, $g, $b);
    }

    /**
     * Construye un PDF simple de una pagina.
     */
    private function pdf(string $content): string
    {
        $objects = [
            '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj',
            '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj',
            '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >> endobj',
            '4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj',
            '5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >> endobj',
            '6 0 obj << /Length ' . strlen($content) . " >> stream\n" . $content . "\nendstream endobj",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object . "\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    /**
     * Escapa texto PDF con salida ASCII segura.
     */
    private function escape(string $text): string
    {
        $text = (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    /**
     * Recorta texto largo.
     */
    private function clip(string $text, int $length): string
    {
        return strlen($text) > $length ? substr($text, 0, $length - 3) . '...' : $text;
    }
}
