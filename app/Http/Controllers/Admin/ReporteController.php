<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Reportes\ReporteAdminService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controlador administrativo de reportes.
 */
class ReporteController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly ReporteAdminService $reporteAdminService) {}

    /**
     * Muestra reportes consolidados.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAdminReports', $request->user());

        return view('admin.reportes.index', [
            'reportes' => $this->reporteAdminService->summary($request->all()),
            'filters' => $request->only(['fecha_desde', 'fecha_hasta']),
        ]);
    }

    /**
     * Descarga el reporte del periodo en CSV (se abre en Excel).
     */
    public function exportar(Request $request): StreamedResponse
    {
        $this->authorize('viewAdminReports', $request->user());

        $r = $this->reporteAdminService->summary($request->all());
        $f = $r['filters'];
        $estado = fn ($v): string => ucfirst(str_replace('_', ' ', $v instanceof \BackedEnum ? $v->value : (string) $v));
        $nombre = 'reporte-atlantia-'.$f['fecha_desde'].'-al-'.$f['fecha_hasta'].'.csv';

        return response()->streamDownload(function () use ($r, $f, $estado): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            $fila = fn (array $datos) => fputcsv($out, $datos);

            $fila(['Reporte Atlantia Store', 'Del '.$f['fecha_desde'].' al '.$f['fecha_hasta']]);
            $fila([]);
            $fila(['Resumen']);
            $fila(['Ventas (Q)', number_format($r['ventas_total'], 2, '.', '')]);
            $fila(['Pedidos', $r['pedidos_total']]);
            $fila(['Ticket promedio (Q)', number_format($r['ticket_promedio'], 2, '.', '')]);
            $fila(['Pedidos cancelados o rechazados', $r['cancelados']]);
            $fila([]);
            $fila(['Ventas por '.($f['agrupacion'] === 'mes' ? 'mes' : 'día'), 'Pedidos', 'Total (Q)']);
            foreach ($r['ventas_por_periodo'] as $p) {
                $fila([$p->periodo, $p->pedidos, number_format((float) $p->total, 2, '.', '')]);
            }
            $fila([]);
            $fila(['Pedidos por estado', 'Pedidos', 'Total (Q)']);
            foreach ($r['pedidos_por_estado'] as $p) {
                $fila([$estado($p->estado), $p->pedidos, number_format((float) $p->total, 2, '.', '')]);
            }
            $fila([]);
            $fila(['Método de pago', 'Pedidos', 'Total (Q)']);
            foreach ($r['metodos_pago'] as $p) {
                $fila([$estado($p->metodo_pago), $p->pedidos, number_format((float) $p->total, 2, '.', '')]);
            }
            $fila([]);
            $fila(['Producto', 'Unidades', 'Venta (Q)']);
            foreach ($r['top_productos'] as $p) {
                $fila([$p->nombre, $p->unidades, number_format((float) $p->total, 2, '.', '')]);
            }
            $fila([]);
            $fila(['Tienda', 'Pedidos', 'Ventas (Q)']);
            foreach ($r['ingresos_por_vendedor'] as $v) {
                $fila([$v->business_name, $v->pedidos, number_format((float) $v->total_ventas, 2, '.', '')]);
            }
            fclose($out);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
