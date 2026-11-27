<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServicioContratado;
use App\Services\Monetizacion\ServiciosPagadosService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Administracion de lo que pagan comercios y anunciantes: planes y publicidad.
 */
class ServiciosContratadosController extends Controller
{
    public function __construct(private readonly ServiciosPagadosService $servicios) {}

    public function index(Request $request): View
    {
        $estado = $request->string('estado')->toString();

        return view('admin.servicios.index', [
            'servicios' => ServicioContratado::query()
                ->with(['vendor:id,business_name', 'referencia'])
                ->when($estado !== '', fn ($q) => $q->where('estado', $estado))
                ->latest('id')
                ->paginate(30)
                ->withQueryString(),
            'estado' => $estado,
            'ingresosMes' => (float) ServicioContratado::query()->whereNotNull('pagado_at')->where('pagado_at', '>=', now()->startOfMonth())->sum('monto'),
            'porRevisar' => ServicioContratado::query()->where('estado', 'en_revision')->count(),
        ]);
    }

    public function marcarPagado(ServicioContratado $servicio): RedirectResponse
    {
        abort_unless($servicio->estado === 'pendiente_pago', 422);
        $this->servicios->marcarPagado($servicio, 'manual');

        return back()->with('success', 'Pago registrado.');
    }

    public function aprobar(ServicioContratado $servicio): RedirectResponse
    {
        abort_unless($servicio->estado === 'en_revision', 422);
        $this->servicios->aprobarBanner($servicio);

        return back()->with('success', 'Banner aprobado: se publica en sus fechas.');
    }

    public function rechazar(ServicioContratado $servicio): RedirectResponse
    {
        $this->servicios->rechazar($servicio);

        return back()->with('success', 'Servicio rechazado. Si ya estaba pagado, gestiona el reembolso con el comercio.');
    }
}
