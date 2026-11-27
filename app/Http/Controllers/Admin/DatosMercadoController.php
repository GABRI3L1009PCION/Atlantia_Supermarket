<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Vendor;
use App\Services\Reportes\DatosMercadoService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reporte de mercado para vender a marcas y proveedores (datos agregados y anonimos).
 */
class DatosMercadoController extends Controller
{
    public function __construct(private readonly DatosMercadoService $datos) {}

    public function index(Request $request): View
    {
        $filtros = $this->filtros($request);

        return view('admin.datos-mercado.index', [
            'filtros' => $filtros,
            'resumen' => $this->datos->resumen($filtros),
            'categorias' => Categoria::query()->orderBy('nombre')->get(['id', 'nombre']),
            'vendors' => Vendor::query()->orderBy('business_name')->get(['id', 'business_name']),
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $resumen = $this->datos->resumen($this->filtros($request));

        return response()->streamDownload(function () use ($resumen): void {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\u{FEFF}");
            fputcsv($salida, ['Producto', 'Unidades', 'Monto (Q)', 'Pedidos']);
            foreach ($resumen['productos'] as $fila) {
                fputcsv($salida, [$fila->producto, $fila->unidades, round((float) $fila->monto, 2), $fila->pedidos]);
            }
            fputcsv($salida, []);
            fputcsv($salida, ['Municipio', 'Colonia', 'Pedidos', 'Monto (Q)']);
            foreach ($resumen['colonias'] as $fila) {
                fputcsv($salida, [$fila->municipio, $fila->colonia, $fila->pedidos, round((float) $fila->monto, 2)]);
            }
            fclose($salida);
        }, 'datos-mercado-atlantia.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{desde: string, hasta: string, categoria_id: ?int, vendor_id: ?int}
     */
    private function filtros(Request $request): array
    {
        $data = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'categoria_id' => ['nullable', 'integer'],
            'vendor_id' => ['nullable', 'integer'],
        ]);

        return [
            'desde' => $data['desde'] ?? now()->subDays(30)->toDateString(),
            'hasta' => $data['hasta'] ?? now()->toDateString(),
            'categoria_id' => isset($data['categoria_id']) ? (int) $data['categoria_id'] : null,
            'vendor_id' => isset($data['vendor_id']) ? (int) $data['vendor_id'] : null,
        ];
    }
}
