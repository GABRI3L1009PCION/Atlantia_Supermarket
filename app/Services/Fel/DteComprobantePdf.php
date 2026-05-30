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
        $dte->loadMissing(['vendor.fiscalProfile', 'pedido.cliente', 'items.producto']);

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
        $dte->loadMissing(['vendor.fiscalProfile', 'pedido.cliente', 'items.producto']);

        $commands = [];

        $this->fill($commands, 0.97, 0.93, 0.95);
        $this->rect($commands, 0, 0, 612, 792, 'f');

        $this->fill($commands, 1, 1, 1);
        $this->stroke($commands, 0.92, 0.78, 0.84);
        $this->rect($commands, 42, 40, 528, 712, 'B');

        $this->fill($commands, 0.55, 0.06, 0.24);
        $this->text($commands, 60, 718, 10, 'ATLANTIA SUPERMARKET', true);
        $this->fill($commands, 0.13, 0.07, 0.11);
        $this->text($commands, 60, 688, 26, 'Comprobante de compra', true);

        $isMock = (bool) data_get($dte->certificador_respuesta, 'respuesta_original.mock', data_get($dte->certificador_respuesta, 'mock', false));
        $label = $isMock ? 'COMPROBANTE INTERNO EMULADO' : 'DTE FEL CERTIFICADO';
        $this->fill($commands, 0.55, 0.06, 0.24);
        $this->text($commands, 390, 718, 9, $label, true);
        $this->fill($commands, 0.30, 0.27, 0.30);
        $this->text($commands, 390, 698, 9, 'Documento: ' . $dte->numero_dte);
        $this->text($commands, 390, 682, 9, 'Fecha: ' . optional($dte->fecha_certificacion ?? $dte->created_at)->format('d/m/Y H:i'));
        $this->text($commands, 390, 666, 9, 'Serie: ' . ($dte->serie ?? 'Interna'));

        $this->line($commands, 60, 642, 552, 642);

        $cliente = $dte->pedido?->cliente;
        $vendor = $dte->vendor;
        $profile = $vendor?->fiscalProfile;

        $this->box($commands, 60, 555, 230, 70, 'Cliente');
        $this->text($commands, 75, 592, 10, $cliente?->name ?? 'Consumidor final', true);
        $this->text($commands, 75, 576, 9, $cliente?->email ?? 'Sin correo');
        $this->text($commands, 75, 560, 9, 'Pedido: ' . ($dte->pedido?->numero_pedido ?? 'N/D'));

        $this->box($commands, 322, 555, 230, 70, 'Emisor');
        $this->text($commands, 337, 592, 10, $profile?->razon_social ?? $vendor?->business_name ?? 'Atlantia Supermarket', true);
        $this->text($commands, 337, 576, 9, 'NIT: ' . ($profile?->nit ?? 'CF'));
        $this->text($commands, 337, 560, 9, 'Vendedor: ' . ($vendor?->business_name ?? 'Atlantia'));

        $this->summaryBox($commands, 60, 475, 'Subtotal', (float) $dte->monto_neto);
        $this->summaryBox($commands, 192, 475, 'IVA', (float) $dte->monto_iva);
        $this->summaryBox($commands, 324, 475, 'Total', (float) $dte->monto_total);

        $this->fill($commands, 0.55, 0.06, 0.24);
        $this->text($commands, 60, 438, 13, 'Detalle de productos', true);
        $this->fill($commands, 0.55, 0.06, 0.24);
        $this->rect($commands, 60, 410, 492, 22, 'f');
        $this->fill($commands, 1, 1, 1);
        $this->text($commands, 72, 417, 8, 'Producto', true);
        $this->text($commands, 350, 417, 8, 'Cant.', true);
        $this->text($commands, 410, 417, 8, 'Precio', true);
        $this->text($commands, 485, 417, 8, 'Total', true);

        $y = 388;
        foreach ($dte->items->take(8) as $item) {
            $this->stroke($commands, 0.94, 0.84, 0.88);
            $this->line($commands, 60, $y - 8, 552, $y - 8);
            $this->fill($commands, 0.13, 0.07, 0.11);
            $this->text($commands, 72, $y, 8.5, $this->clip((string) $item->descripcion, 44), true);
            $this->text($commands, 356, $y, 8.5, (string) $item->cantidad);
            $this->text($commands, 410, $y, 8.5, 'Q' . number_format((float) $item->precio_unitario, 2));
            $this->text($commands, 485, $y, 8.5, 'Q' . number_format((float) $item->monto_total, 2));
            $y -= 24;
        }

        if ($dte->items->count() > 8) {
            $this->fill($commands, 0.30, 0.27, 0.30);
            $this->text($commands, 72, $y, 8, 'Mas productos incluidos en el pedido: ' . ($dte->items->count() - 8));
        }

        $this->fill($commands, 1, 0.98, 0.93);
        $this->stroke($commands, 0.91, 0.66, 0.22);
        $this->rect($commands, 60, 92, 492, 48, 'B');
        $this->fill($commands, 0.43, 0.25, 0.05);
        $notice = $isMock
            ? 'Este comprobante fue generado en modo emulado. No sustituye una certificacion FEL/SAT real.'
            : 'Documento generado desde Atlantia Supermarket.';
        $this->text($commands, 78, 120, 9, $notice, true);
        $this->text($commands, 78, 104, 8.5, 'UUID de referencia: ' . ($dte->uuid_sat ?? $dte->uuid));

        $this->fill($commands, 0.35, 0.31, 0.35);
        $this->text($commands, 60, 62, 8, 'Atlantia Supermarket - Comprobante interno');
        $this->fill($commands, 0.55, 0.06, 0.24);
        $this->text($commands, 500, 62, 8, 'Pagina 1 de 1', true);

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
