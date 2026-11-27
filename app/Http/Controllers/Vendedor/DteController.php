<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendedor\Dte\AnularDteRequest;
use App\Models\Dte\DteFactura;
use App\Services\Fel\DteAnulacionService;
use App\Services\Fel\ReporteFiscalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de DTE propios del vendedor.
 */
class DteController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(
        private readonly ReporteFiscalService $reporteFiscalService,
        private readonly DteAnulacionService $dteAnulacionService
    ) {}

    /**
     * Lista DTE propios.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewOwnDtes', DteFactura::class);

        $filtros = $request->only(['estado', 'fecha_desde', 'fecha_hasta']);
        $filtros['estado'] = in_array($filtros['estado'] ?? null, ['certificado', 'anulado', 'rechazado', 'borrador'], true) ? $filtros['estado'] : null;

        return view('vendedor.dte.index', [
            'dtes' => $this->reporteFiscalService->paginateForVendor($request->user(), array_filter($filtros))->withQueryString(),
            'filtros' => $filtros,
            'perfil' => $request->user()->vendor?->fiscalProfile,
            'delMes' => DteFactura::query()->where('vendor_id', $request->user()->vendor?->id)->where('estado', 'certificado')
                ->where('created_at', '>=', now()->startOfMonth())->selectRaw('count(*) as n, coalesce(sum(monto_total), 0) as total')->first(),
            'mes' => $mes = self::mes($request),
            'resumenMes' => self::facturasDelMes($request->user()->vendor?->id, $mes)
                ->selectRaw('count(*) as n, coalesce(sum(monto_neto), 0) as neto, coalesce(sum(monto_iva), 0) as iva, coalesce(sum(monto_total), 0) as total')->first(),
            /* Ventas entregadas sin factura (para que la tienda no se olvide de ninguna). */
            'sinFactura' => \App\Models\Pedido::query()->where('vendor_id', $request->user()->vendor?->id)
                ->where('estado', 'entregado')->where('created_at', '>=', now()->subDays(60))
                ->whereDoesntHave('dteFacturas', fn ($q) => $q->where('estado', 'certificado'))
                ->latest('id')->limit(8)->get(['id', 'uuid', 'numero_pedido', 'pedido_padre_id', 'subtotal', 'descuento', 'created_at']),
        ]);
    }

    /**
     * Facturas del mes en un archivo para Excel: lo que la tienda lleva a su contador o declara ante la SAT.
     */
    public function exportar(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('viewOwnDtes', DteFactura::class);
        $vendor = $request->user()->vendor;
        $mes = self::mes($request);

        $facturas = self::facturasDelMes($vendor?->id, $mes)->with('pedido.pedidoPadre')->orderBy('fecha_certificacion')->get();

        return response()->streamDownload(function () use ($facturas): void {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, ['Fecha', 'Serie', 'Número', 'Autorización SAT', 'NIT cliente', 'Nombre cliente', 'Sin IVA', 'IVA', 'Total', 'Pedido', 'Origen'], ';');
            foreach ($facturas as $f) {
                $pedido = $f->pedido;
                $padre = $pedido?->pedidoPadre ?? $pedido;
                fputcsv($salida, [
                    ($f->fecha_certificacion ?? $f->created_at)?->format('d/m/Y'),
                    $f->serie,
                    $f->numero,
                    $f->uuid_sat,
                    $pedido?->facturacion_nit ?: ($padre?->facturacion_nit ?: 'CF'),
                    $pedido?->facturacion_nombre ?: ($padre?->facturacion_nombre ?: 'Consumidor final'),
                    number_format((float) $f->monto_neto, 2, '.', ''),
                    number_format((float) $f->monto_iva, 2, '.', ''),
                    number_format((float) $f->monto_total, 2, '.', ''),
                    $padre?->numero_pedido,
                    $f->origen === 'manual' ? 'Registrada por la tienda' : 'Automática',
                ], ';');
            }
            fclose($salida);
        }, 'facturas-'.$mes->format('Y-m').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private static function mes(Request $request): \Illuminate\Support\Carbon
    {
        $valor = (string) $request->query('mes', '');

        return preg_match('/^\d{4}-\d{2}$/', $valor) === 1
            ? \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $valor.'-01')->startOfMonth()
            : now()->startOfMonth();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<DteFactura>
     */
    private static function facturasDelMes(?int $vendorId, \Illuminate\Support\Carbon $mes): \Illuminate\Database\Eloquent\Builder
    {
        return DteFactura::query()->where('vendor_id', $vendorId)->where('estado', 'certificado')
            ->whereBetween(\Illuminate\Support\Facades\DB::raw('coalesce(fecha_certificacion, created_at)'), [$mes->copy()->startOfMonth(), $mes->copy()->endOfMonth()]);
    }

    /**
     * Muestra detalle de DTE propio.
     */
    public function show(DteFactura $dte): View
    {
        $this->authorize('view', $dte);

        return view('vendedor.dte.show', ['dte' => $this->reporteFiscalService->detail($dte)]);
    }

    /**
     * Solicita anulacion de DTE.
     */
    public function anular(AnularDteRequest $request, DteFactura $dte): RedirectResponse
    {
        $this->authorize('anular', $dte);
        $this->dteAnulacionService->anular($dte, $request->validated(), $request->user());

        return back()->with('success', 'Anulacion de DTE solicitada correctamente.');
    }
}
