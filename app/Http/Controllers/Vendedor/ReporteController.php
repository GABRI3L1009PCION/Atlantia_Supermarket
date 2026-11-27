<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Services\Reportes\ReporteVendedorService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controlador de reportes para vendedor.
 */
class ReporteController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly ReporteVendedorService $reporteVendedorService) {}

    /**
     * Muestra reportes del vendedor.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewVendorReports', $request->user());

        return view('vendedor.reportes.index', [
            'reportes' => $this->reporteVendedorService->summary($request->user(), $request->all()),
        ]);
    }

    /**
     * Descarga las ventas del periodo en un archivo que abre Excel (CSV con ; y acentos).
     */
    public function descargar(Request $request): StreamedResponse
    {
        $this->authorize('viewVendorReports', $request->user());
        $datos = $this->reporteVendedorService->ventasParaExportar($request->user(), $request->all());
        $estados = [
            'pendiente' => 'Por confirmar', 'en_revision' => 'En revisión', 'confirmado' => 'Confirmado',
            'preparando' => 'En preparación', 'listo_para_entrega' => 'Listo', 'en_ruta' => 'En camino',
            'entregado' => 'Entregado', 'cancelado' => 'Cancelado', 'rechazado' => 'Rechazado',
        ];
        $metodos = ['tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia', 'efectivo' => 'Efectivo'];
        $numero = fn (float $valor): string => number_format($valor, 2, '.', '');

        return response()->streamDownload(function () use ($datos, $estados, $metodos, $numero): void {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, ['Fecha', 'Hora', 'Pedido', 'Estado', 'Pago', 'Unidades', 'Tu venta (Q)', 'Envío (Q)', 'Total cobrado al cliente (Q)'], ';');
            foreach ($datos['filas'] as $pedido) {
                $venta = (float) $pedido->total - (float) $pedido->envio - (float) $pedido->propina;
                fputcsv($salida, [
                    $pedido->created_at?->format('d/m/Y'),
                    $pedido->created_at?->format('H:i'),
                    $pedido->numero_pedido,
                    $estados[$pedido->estadoValor()] ?? $pedido->estadoValor(),
                    $metodos[$pedido->metodoPagoValor()] ?? $pedido->metodoPagoValor(),
                    (int) $pedido->items->sum('cantidad'),
                    $numero(in_array($pedido->estadoValor(), ['cancelado', 'rechazado'], true) ? 0 : $venta),
                    $numero((float) $pedido->envio),
                    $numero((float) $pedido->total),
                ], ';');
            }
            fclose($salida);
        }, 'ventas-'.$datos['desde'].'-al-'.$datos['hasta'].'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
