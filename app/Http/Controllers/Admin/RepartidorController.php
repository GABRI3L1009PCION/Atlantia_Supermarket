<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Repartidor\StoreRepartidorRequest;
use App\Http\Requests\Admin\Repartidor\UpdateRepartidorRequest;
use App\Models\CourierProfile;
use App\Models\DeliveryRoute;
use App\Models\User;
use App\Services\Logistica\RepartidorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador administrativo de repartidores.
 */
class RepartidorController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly RepartidorService $repartidorService) {}

    /**
     * Lista repartidores.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $repartidores = $this->repartidorService->paginate($request->all());
        $ids = $repartidores->getCollection()->pluck('id');
        $inicioMes = now()->startOfMonth();

        // Entregas y ganancia del mes por repartidor (rutas completadas).
        $delMes = DeliveryRoute::query()
            ->whereIn('repartidor_id', $ids)
            ->where('estado', 'completada')
            ->where('completada_at', '>=', $inicioMes)
            ->selectRaw('repartidor_id, COUNT(*) as entregas, COALESCE(SUM(estimated_earning + COALESCE(tip_amount, 0) + COALESCE(bonus_amount, 0)), 0) as ganancia')
            ->groupBy('repartidor_id')
            ->get()
            ->keyBy('repartidor_id');
        $enRuta = DeliveryRoute::query()
            ->whereIn('repartidor_id', $ids)
            ->whereIn('estado', ['asignada', 'iniciada', 'pausada'])
            ->selectRaw('repartidor_id, COUNT(*) as total')
            ->groupBy('repartidor_id')
            ->pluck('total', 'repartidor_id');

        $todos = User::role('repartidor');

        return view('admin.repartidores.index', [
            'repartidores' => $repartidores,
            'delMes' => $delMes,
            'enRuta' => $enRuta,
            'resumen' => [
                'total' => (clone $todos)->count(),
                'disponibles' => CourierProfile::query()->where('availability_status', 'available')->whereIn('user_id', (clone $todos)->select('users.id'))->count(),
                'en_ruta' => DeliveryRoute::query()->whereIn('estado', ['asignada', 'iniciada', 'pausada'])->count(),
                'entregas_hoy' => DeliveryRoute::query()->where('estado', 'completada')->whereDate('completada_at', today())->count(),
            ],
        ]);
    }

    /**
     * Registra un repartidor.
     */
    public function store(StoreRepartidorRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);
        $this->repartidorService->create($request->validated());

        return back()->with('success', 'Repartidor creado correctamente.');
    }

    /**
     * Muestra actividad de un repartidor.
     */
    public function show(User $repartidor): View
    {
        $this->authorize('view', $repartidor);

        return view('admin.repartidores.show', ['repartidor' => $this->repartidorService->detail($repartidor)]);
    }

    /**
     * Actualiza un repartidor.
     */
    public function update(UpdateRepartidorRequest $request, User $repartidor): RedirectResponse
    {
        $this->authorize('update', $repartidor);
        $this->repartidorService->update($repartidor, $request->validated());

        return back()->with('success', 'Repartidor actualizado correctamente.');
    }

    /**
     * Elimina logicamente un repartidor.
     */
    public function destroy(User $repartidor): RedirectResponse
    {
        $this->authorize('delete', $repartidor);
        $this->repartidorService->delete($repartidor);

        return redirect()->route('admin.repartidores.index')->with('success', 'Repartidor eliminado correctamente.');
    }
}
