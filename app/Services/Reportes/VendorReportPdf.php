<?php

namespace App\Services\Reportes;

/**
 * Genera el reporte administrativo de vendedores en PDF.
 */
class VendorReportPdf
{
    private const WIDTH = 612.0;
    private const HEIGHT = 792.0;
    private const MARGIN = 42.0;
    private const CONTENT_WIDTH = 528.0;

    /** @var array<int, string> */
    private array $pages = [];

    /**
     * Construye el archivo PDF con datos reales.
     *
     * @param array<string, mixed> $report
     */
    public function make(array $report): string
    {
        $this->pages = [];

        $vendors = collect($report['vendors'] ?? []);
        $firstRows = $vendors->take(6)->values();
        $remainingRows = $vendors->slice(6)->values();
        $totalPages = 1 + (int) ceil(max(0, $vendors->count() - 6) / 12);

        $this->addPage();
        $this->drawPageBackground();
        $this->drawHeader($report);
        $this->drawMetricCards($report);
        $this->drawStatusPanel($report);
        $this->drawSalesPanel($report);
        $this->drawVendorTable(42, 448, $firstRows->all(), 6);
        $this->drawObservations($report, 42, 684);
        $this->drawFooter(1, $totalPages);

        $page = 2;
        foreach ($remainingRows->chunk(12) as $chunk) {
            $this->addPage();
            $this->drawPageBackground();
            $this->text(42, 54, 'ATLANTIA SUPERMARKET', 8, 'F2', [207, 95, 128]);
            $this->text(42, 80, 'Detalle de vendedores', 27, 'F2', [42, 16, 30]);
            $this->text(42, 108, 'Continuacion del reporte con registros reales del sistema.', 10, 'F1', [92, 78, 88]);
            $this->line(42, 132, 570, 132, [215, 148, 174], 1.2);
            $this->drawVendorTable(42, 160, $chunk->values()->all(), 12);
            $this->drawFooter($page, $totalPages);
            $page++;
        }

        return $this->render();
    }

    private function drawPageBackground(): void
    {
        $this->rect(0, 0, self::WIDTH, self::HEIGHT, [255, 255, 255]);
        $this->rect(24, 22, 564, 748, [255, 255, 255], [236, 214, 224], 0.8);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function drawHeader(array $report): void
    {
        $generatedAt = $report['generated_at'] ?? now();

        $this->text(42, 54, 'ATLANTIA SUPERMARKET', 8, 'F2', [207, 95, 128]);
        $this->text(42, 80, 'Reporte de vendedores', 28, 'F2', [42, 16, 30]);
        $this->text(42, 118, 'Resumen operativo de solicitudes, aprobaciones, comisiones y desempeno', 10, 'F1', [92, 78, 88]);

        $this->metaRow(390, 58, 'Fecha de generacion:', $generatedAt->format('d/m/Y | H:i'));
        $this->metaRow(390, 86, 'Periodo:', 'Ultimos 30 dias');
        $this->metaRow(390, 114, 'Generado por:', 'Administracion Atlantia');
        $this->line(42, 142, 570, 142, [215, 148, 174], 1.2);
    }

    private function metaRow(float $x, float $y, string $label, string $value): void
    {
        $this->circle($x, $y - 2, 7, [255, 246, 250], [207, 95, 128], 0.7);
        $this->text($x + 18, $y - 5, $label, 7, 'F2', [92, 78, 88]);
        $this->text($x + 96, $y - 5, $value, 7, 'F1', [64, 55, 62]);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function drawMetricCards(array $report): void
    {
        $metrics = $report['metrics'] ?? [];
        $cards = [
            ['Total vendedores', (string) ($metrics['total'] ?? 0), ($metrics['approved'] ?? 0) . ' activos', [160, 24, 82]],
            ['Ventas 30 dias', 'Q' . number_format((float) ($metrics['sales_30'] ?? 0), 2), 'Suma de vendedores visibles', [160, 24, 82]],
            ['Comision pendiente', 'Q' . number_format((float) ($metrics['pending_commission'] ?? 0), 2), 'Pendiente/facturada', [160, 24, 82]],
            ['Rating promedio', number_format((float) ($metrics['avg_rating'] ?? 0), 1) . '*', 'Resenas aprobadas registradas', [160, 24, 82]],
        ];

        foreach ($cards as $index => $card) {
            $x = 42 + ($index * 137);
            $this->roundedRect($x, 166, 124, 72, 5, [255, 255, 255], [239, 207, 219], 0.8);
            $this->circle($x + 22, 202, 14, [252, 232, 240], null);
            $this->text($x + 48, 184, $card[0], 7, 'F2', [105, 88, 99]);
            $this->text($x + 48, 203, $card[1], 19, 'F2', $card[3]);
            $this->text($x + 48, 226, $this->clip($card[2], 24), 7, 'F1', [105, 88, 99]);
        }
    }

    /**
     * @param array<string, mixed> $report
     */
    private function drawStatusPanel(array $report): void
    {
        $counts = $report['status_counts'] ?? [];
        $total = max(1, array_sum($counts));
        $colors = [
            'pending' => [132, 28, 70],
            'approved' => [220, 122, 160],
            'rejected' => [210, 210, 210],
            'suspended' => [246, 213, 126],
        ];
        $labels = [
            'pending' => 'Pendientes',
            'approved' => 'Aprobados',
            'rejected' => 'Rechazados',
            'suspended' => 'Suspendidos',
        ];

        $this->roundedRect(42, 260, 255, 166, 5, [255, 255, 255], [239, 207, 219], 0.8);
        $this->text(54, 280, 'Estado de vendedores', 12, 'F2', [132, 28, 70]);

        $start = -90.0;
        foreach ($labels as $status => $label) {
            $value = (int) ($counts[$status] ?? 0);
            if ($value <= 0) {
                continue;
            }
            $end = $start + (($value / $total) * 360);
            $this->pieSegment(112, 348, 48, $start, $end, $colors[$status]);
            $start = $end;
        }
        $this->circle(112, 348, 24, [255, 255, 255], null);
        $this->text(107, 336, (string) array_sum($counts), 18, 'F2', [132, 28, 70]);

        $legendY = 310;
        foreach ($labels as $status => $label) {
            $this->circle(218, $legendY + 4, 4, $colors[$status], null);
            $this->text(228, $legendY, $label, 8, 'F1', [64, 55, 62]);
            $this->text(276, $legendY, (string) ($counts[$status] ?? 0), 8, 'F2', [64, 55, 62]);
            $legendY += 20;
        }

        $this->text(210, 404, 'Total:', 8, 'F2', [132, 28, 70]);
        $this->text(236, 404, array_sum($counts) . ' vendedores', 8, 'F1', [64, 55, 62]);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function drawSalesPanel(array $report): void
    {
        $vendors = collect($report['vendors'] ?? [])
            ->sortByDesc(fn (array $vendor): float => (float) ($vendor['sales_30'] ?? 0))
            ->take(4)
            ->values();
        $max = max(1, (float) $vendors->max('sales_30'));
        $total = (float) ($report['metrics']['sales_30'] ?? 0);

        $this->roundedRect(315, 260, 255, 166, 5, [255, 255, 255], [239, 207, 219], 0.8);
        $this->text(327, 280, 'Ventas por vendedor', 12, 'F2', [132, 28, 70]);
        $this->text(327, 296, 'Ultimos 30 dias (Q)', 8, 'F1', [92, 78, 88]);

        for ($i = 0; $i <= 4; $i++) {
            $x = 388 + ($i * 38);
            $this->line($x, 310, $x, 390, [224, 224, 224], 0.5);
            $this->text($x - 5, 302, (string) round(($max / 4) * $i), 7, 'F1', [92, 78, 88]);
        }

        foreach ($vendors as $index => $vendor) {
            $y = 326 + ($index * 25);
            $bar = (float) ($vendor['sales_30'] ?? 0);
            $this->text(327, $y + 2, $this->clip((string) ($vendor['business'] ?? 'Vendedor'), 16), 7, 'F1', [64, 55, 62]);
            $this->rect(388, $y, 150 * ($bar / $max), 10, [132, 28, 70]);
            $this->text(542, $y - 1, number_format($bar, 2), 7, 'F1', [64, 55, 62]);
        }

        if ($vendors->isEmpty()) {
            $this->text(327, 344, 'No hay ventas registradas para graficar.', 8, 'F1', [92, 78, 88]);
        }

        $this->text(470, 404, 'Total ventas:', 8, 'F1', [64, 55, 62]);
        $this->text(526, 404, 'Q' . number_format($total, 2), 8, 'F2', [132, 28, 70]);
    }

    /**
     * @param array<int, array<string, mixed>> $vendors
     */
    private function drawVendorTable(float $x, float $y, array $vendors, int $maxRows): void
    {
        $this->text($x, $y - 18, 'Detalle de vendedores', 12, 'F2', [132, 28, 70]);
        $widths = [124, 120, 58, 67, 49, 55, 55];
        $headers = ['Vendedor / Negocio', 'Correo', 'Telefono', 'Estado', 'Docs', 'Ventas', 'Comision'];
        $headerH = 26;
        $rowH = $maxRows > 6 ? 38 : 34;

        $this->roundedRect($x, $y, self::CONTENT_WIDTH, $headerH + ($rowH * max(1, min($maxRows, max(1, count($vendors))))), 4, [255, 255, 255], [239, 207, 219], 0.8);
        $this->rect($x, $y, self::CONTENT_WIDTH, $headerH, [132, 28, 70]);

        $cursor = $x;
        foreach ($headers as $index => $header) {
            $this->text($cursor + 8, $y + 10, $header, 7, 'F2', [255, 255, 255]);
            if ($index > 0) {
                $this->line($cursor, $y, $cursor, $y + $headerH + ($rowH * max(1, count($vendors))), [239, 207, 219], 0.5);
            }
            $cursor += $widths[$index];
        }

        if (count($vendors) === 0) {
            $this->text($x + 16, $y + 48, 'No hay vendedores registrados.', 9, 'F2', [132, 28, 70]);

            return;
        }

        foreach (array_slice($vendors, 0, $maxRows) as $row => $vendor) {
            $rowY = $y + $headerH + ($row * $rowH);
            $this->line($x, $rowY, $x + self::CONTENT_WIDTH, $rowY, [239, 207, 219], 0.5);
            $status = (string) ($vendor['status'] ?? 'pending');
            $statusStyle = $this->statusStyle($status);
            $initials = $this->initials((string) ($vendor['business'] ?? 'VD'));

            $this->circle($x + 16, $rowY + 17, 10, [252, 232, 240], null);
            $this->text($x + 10, $rowY + 13, $initials, 6, 'F2', [132, 28, 70]);
            $this->text($x + 32, $rowY + 9, $this->clip((string) ($vendor['business'] ?? 'Sin nombre'), 17), 8, 'F2', [42, 16, 30]);
            $this->text($x + 32, $rowY + 23, $this->clip((string) ($vendor['category'] ?? 'Sin categoria'), 18), 6, 'F1', [92, 78, 88]);
            $this->text($x + 132, $rowY + 15, $this->clip((string) ($vendor['email'] ?? 'No registrado'), 24), 6, 'F1', [64, 55, 62]);
            $this->text($x + 252, $rowY + 15, $this->clip((string) ($vendor['phone'] ?? 'No registrado'), 11), 6, 'F1', [64, 55, 62]);
            $this->roundedRect($x + 315, $rowY + 9, 48, 16, 4, $statusStyle['fill'], $statusStyle['stroke'], 0.5);
            $this->text($x + 322, $rowY + 14, $this->clip((string) ($vendor['status_label'] ?? 'Pendiente'), 11), 6, 'F2', $statusStyle['text']);
            $this->text($x + 382, $rowY + 15, ($vendor['documents'] ?? 0) . '/' . ($vendor['documents_total'] ?? 0), 7, 'F1', [64, 55, 62]);
            $this->text($x + 431, $rowY + 15, 'Q' . number_format((float) ($vendor['sales_30'] ?? 0), 2), 7, 'F1', [64, 55, 62]);
            $this->text($x + 486, $rowY + 15, 'Q' . number_format((float) ($vendor['commission_owed'] ?? 0), 2), 7, 'F1', [64, 55, 62]);
        }
    }

    /**
     * @param array<string, mixed> $report
     */
    private function drawObservations(array $report, float $x, float $y): void
    {
        $counts = $report['status_counts'] ?? [];
        $metrics = $report['metrics'] ?? [];

        $this->roundedRect($x, $y, self::CONTENT_WIDTH, 66, 5, [255, 255, 255], [239, 207, 219], 0.8);
        $this->circle($x + 28, $y + 33, 17, [252, 232, 240], null);
        $this->text($x + 54, $y + 15, 'Observaciones', 12, 'F2', [132, 28, 70]);
        $this->text($x + 54, $y + 34, '- ' . (int) ($counts['pending'] ?? 0) . ' vendedor(es) pendiente(s) de revision documental.', 7, 'F1', [64, 55, 62]);
        $this->text($x + 54, $y + 48, '- ' . (int) ($counts['approved'] ?? 0) . ' vendedor(es) aprobado(s) y activo(s).', 7, 'F1', [64, 55, 62]);
        $this->text($x + 292, $y + 34, '- Comision pendiente: Q' . number_format((float) ($metrics['pending_commission'] ?? 0), 2) . '.', 7, 'F1', [64, 55, 62]);
    }

    private function drawFooter(int $page, int $totalPages): void
    {
        $this->line(42, 765, 570, 765, [215, 148, 174], 1);
        $this->text(42, 778, 'Atlantia Supermarket · Reporte interno', 8, 'F2', [105, 88, 99]);
        $this->text(516, 778, "Pagina {$page} de {$totalPages}", 8, 'F2', [132, 28, 70]);
    }

    /**
     * @return array{fill: array<int, int>, stroke: array<int, int>, text: array<int, int>}
     */
    private function statusStyle(string $status): array
    {
        return match ($status) {
            'approved' => ['fill' => [233, 250, 242], 'stroke' => [134, 220, 172], 'text' => [0, 128, 92]],
            'rejected' => ['fill' => [255, 239, 241], 'stroke' => [255, 170, 184], 'text' => [196, 34, 56]],
            'suspended' => ['fill' => [245, 245, 245], 'stroke' => [204, 204, 204], 'text' => [92, 78, 88]],
            default => ['fill' => [255, 248, 235], 'stroke' => [246, 211, 148], 'text' => [199, 101, 0]],
        };
    }

    private function addPage(): void
    {
        $this->pages[] = '';
    }

    /**
     * @param array<int, int>|null $fill
     * @param array<int, int>|null $stroke
     */
    private function roundedRect(float $x, float $y, float $w, float $h, float $r, ?array $fill = null, ?array $stroke = null, float $lineWidth = 1): void
    {
        $k = 0.5522847498;
        $points = [
            sprintf('%.2F %.2F m', $x + $r, $this->py($y)),
            sprintf('%.2F %.2F l', $x + $w - $r, $this->py($y)),
            sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', $x + $w - $r + ($r * $k), $this->py($y), $x + $w, $this->py($y + $r - ($r * $k)), $x + $w, $this->py($y + $r)),
            sprintf('%.2F %.2F l', $x + $w, $this->py($y + $h - $r)),
            sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', $x + $w, $this->py($y + $h - $r + ($r * $k)), $x + $w - $r + ($r * $k), $this->py($y + $h), $x + $w - $r, $this->py($y + $h)),
            sprintf('%.2F %.2F l', $x + $r, $this->py($y + $h)),
            sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', $x + $r - ($r * $k), $this->py($y + $h), $x, $this->py($y + $h - $r + ($r * $k)), $x, $this->py($y + $h - $r)),
            sprintf('%.2F %.2F l', $x, $this->py($y + $r)),
            sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', $x, $this->py($y + $r - ($r * $k)), $x + $r - ($r * $k), $this->py($y), $x + $r, $this->py($y)),
        ];

        $this->path(implode("\n", $points) . "\nh", $fill, $stroke, $lineWidth);
    }

    /**
     * @param array<int, int>|null $fill
     * @param array<int, int>|null $stroke
     */
    private function rect(float $x, float $y, float $w, float $h, ?array $fill = null, ?array $stroke = null, float $lineWidth = 1): void
    {
        $path = sprintf('%.2F %.2F %.2F %.2F re', $x, $this->py($y + $h), $w, $h);
        $this->path($path, $fill, $stroke, $lineWidth);
    }

    /**
     * @param array<int, int>|null $fill
     * @param array<int, int>|null $stroke
     */
    private function circle(float $cx, float $cy, float $r, ?array $fill = null, ?array $stroke = null, float $lineWidth = 1): void
    {
        $k = 0.5522847498;
        $path = sprintf(
            "%.2F %.2F m\n%.2F %.2F %.2F %.2F %.2F %.2F c\n%.2F %.2F %.2F %.2F %.2F %.2F c\n%.2F %.2F %.2F %.2F %.2F %.2F c\n%.2F %.2F %.2F %.2F %.2F %.2F c\nh",
            $cx + $r,
            $this->py($cy),
            $cx + $r,
            $this->py($cy + ($k * $r)),
            $cx + ($k * $r),
            $this->py($cy + $r),
            $cx,
            $this->py($cy + $r),
            $cx - ($k * $r),
            $this->py($cy + $r),
            $cx - $r,
            $this->py($cy + ($k * $r)),
            $cx - $r,
            $this->py($cy),
            $cx - $r,
            $this->py($cy - ($k * $r)),
            $cx - ($k * $r),
            $this->py($cy - $r),
            $cx,
            $this->py($cy - $r),
            $cx + ($k * $r),
            $this->py($cy - $r),
            $cx + $r,
            $this->py($cy - ($k * $r)),
            $cx + $r,
            $this->py($cy)
        );
        $this->path($path, $fill, $stroke, $lineWidth);
    }

    /**
     * @param array<int, int> $fill
     */
    private function pieSegment(float $cx, float $cy, float $r, float $startDeg, float $endDeg, array $fill): void
    {
        $segments = max(1, (int) ceil(abs($endDeg - $startDeg) / 60));
        $step = ($endDeg - $startDeg) / $segments;
        $angle = $startDeg;
        $start = $this->pointOnCircle($cx, $cy, $r, $startDeg);
        $path = sprintf('%.2F %.2F m %.2F %.2F l', $cx, $this->py($cy), $start[0], $this->py($start[1]));

        for ($i = 0; $i < $segments; $i++) {
            $next = $angle + $step;
            $path .= "\n" . $this->arcCurve($cx, $cy, $r, $angle, $next);
            $angle = $next;
        }

        $path .= "\nh";
        $this->path($path, $fill, null);
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function pointOnCircle(float $cx, float $cy, float $r, float $angle): array
    {
        $rad = deg2rad($angle);

        return [$cx + ($r * cos($rad)), $cy + ($r * sin($rad))];
    }

    private function arcCurve(float $cx, float $cy, float $r, float $startDeg, float $endDeg): string
    {
        $start = deg2rad($startDeg);
        $end = deg2rad($endDeg);
        $delta = $end - $start;
        $k = 4 / 3 * tan($delta / 4);
        $p0 = [$cx + ($r * cos($start)), $cy + ($r * sin($start))];
        $p3 = [$cx + ($r * cos($end)), $cy + ($r * sin($end))];
        $p1 = [$p0[0] - ($k * $r * sin($start)), $p0[1] + ($k * $r * cos($start))];
        $p2 = [$p3[0] + ($k * $r * sin($end)), $p3[1] - ($k * $r * cos($end))];

        return sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', $p1[0], $this->py($p1[1]), $p2[0], $this->py($p2[1]), $p3[0], $this->py($p3[1]));
    }

    /**
     * @param array<int, int> $color
     */
    private function line(float $x1, float $y1, float $x2, float $y2, array $color, float $lineWidth = 1): void
    {
        $this->write($this->color($color, 'RG') . $lineWidth . " w\n" . sprintf('%.2F %.2F m %.2F %.2F l S', $x1, $this->py($y1), $x2, $this->py($y2)) . "\n");
    }

    /**
     * @param array<int, int> $color
     */
    private function text(float $x, float $y, string $text, int $size, string $font, array $color): void
    {
        $this->write("BT\n" . $this->color($color, 'rg') . sprintf("/%s %d Tf\n%.2F %.2F Td\n(%s) Tj\nET\n", $font, $size, $x, $this->py($y + $size), $this->escape($text)));
    }

    /**
     * @param array<int, int>|null $fill
     * @param array<int, int>|null $stroke
     */
    private function path(string $path, ?array $fill = null, ?array $stroke = null, float $lineWidth = 1): void
    {
        $command = '';
        if ($fill) {
            $command .= $this->color($fill, 'rg');
        }
        if ($stroke) {
            $command .= $this->color($stroke, 'RG') . $lineWidth . " w\n";
        }

        $command .= $path . "\n";
        $command .= $fill && $stroke ? "B\n" : ($fill ? "f\n" : "S\n");
        $this->write($command);
    }

    /**
     * @param array<int, int> $rgb
     */
    private function color(array $rgb, string $operator): string
    {
        return sprintf("%.3F %.3F %.3F %s\n", $rgb[0] / 255, $rgb[1] / 255, $rgb[2] / 255, $operator);
    }

    private function write(string $command): void
    {
        $last = array_key_last($this->pages);
        $this->pages[$last] .= $command;
    }

    private function render(): string
    {
        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            '',
            "3 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>\nendobj\n",
            "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>\nendobj\n",
        ];

        $kids = [];
        $objectNumber = 5;

        foreach ($this->pages as $content) {
            $pageObject = $objectNumber++;
            $contentObject = $objectNumber++;
            $kids[] = "{$pageObject} 0 R";
            $objects[] = "{$pageObject} 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 " . self::WIDTH . ' ' . self::HEIGHT . "] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents {$contentObject} 0 R >>\nendobj\n";
            $objects[] = "{$contentObject} 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream\nendobj\n";
        }

        $objects[1] = "2 0 obj\n<< /Type /Pages /Count " . count($this->pages) . ' /Kids [' . implode(' ', $kids) . "] >>\nendobj\n";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefPosition = strlen($pdf);
        $pdf .= "xref\n0 " . count($offsets) . "\n0000000000 65535 f \n";

        for ($i = 1; $i < count($offsets); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        return $pdf . "trailer\n<< /Size " . count($offsets) . " /Root 1 0 R >>\nstartxref\n{$xrefPosition}\n%%EOF";
    }

    private function py(float $y): float
    {
        return self::HEIGHT - $y;
    }

    private function escape(string $text): string
    {
        $text = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text) ?: $text;

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function initials(string $text): string
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $letters = array_map(fn (string $word): string => strtoupper(substr($word, 0, 1)), array_slice(array_filter($words), 0, 2));

        return implode('', $letters) ?: 'VD';
    }

    private function clip(string $text, int $length): string
    {
        return strlen($text) > $length ? substr($text, 0, max(0, $length - 3)) . '...' : $text;
    }
}
