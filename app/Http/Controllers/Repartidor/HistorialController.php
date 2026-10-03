<?php

namespace App\Http\Controllers\Repartidor;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRoute;
use App\Models\ExternalDeliveryOrder;
use App\Services\Repartidores\CourierProfileService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de historial y rendimiento.
 */
class HistorialController extends Controller
{
    public function __construct(private readonly CourierProfileService $profileService) {}

    /**
     * Muestra historial operativo.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $profile = $this->profileService->refreshPerformance($user);
        $internalCompleted = DeliveryRoute::query()
            ->with(['pedido.cliente', 'pedido.direccion'])
            ->where('repartidor_id', $user->id)
            ->where('estado', 'completada')
            ->latest('completada_at')
            ->limit(6)
            ->get()
            ->map(function (DeliveryRoute $route): array {
                $pedido = $route->pedido;
                $customerName = $pedido?->direccion?->nombre_contacto ?: $pedido?->cliente?->name ?: 'Cliente';
                $paymentMethod = strtolower((string) $route->payment_method);

                return [
                    'title' => $pedido?->numero_pedido ?? 'Ruta #'.$route->id,
                    'customer' => $customerName,
                    'date' => $route->completada_at ?? $route->updated_at,
                    'amount' => (float) $route->estimated_earning + (float) $route->tip_amount + (float) $route->bonus_amount,
                    'payment' => in_array($paymentMethod, ['cash', 'efectivo'], true) ? 'Efectivo' : 'Digital',
                    'url' => $pedido ? route('repartidor.pedidos.show', $pedido) : route('repartidor.historial.index'),
                ];
            });
        $externalCompleted = ExternalDeliveryOrder::query()
            ->where('repartidor_id', $user->id)
            ->where('status', 'delivered')
            ->latest('delivered_at')
            ->limit(6)
            ->get()
            ->map(function (ExternalDeliveryOrder $order): array {
                $paymentMethod = strtolower((string) $order->payment_method);

                return [
                    'title' => $order->external_reference ?: $order->store_name,
                    'customer' => $order->customer_name ?: 'Cliente',
                    'date' => $order->delivered_at ?? $order->updated_at,
                    'amount' => (float) $order->courier_earning + (float) $order->tip_amount,
                    'payment' => in_array($paymentMethod, ['cash', 'efectivo'], true) ? 'Efectivo' : 'Digital',
                    'url' => route('repartidor.externas.show', $order),
                ];
            });

        return view('repartidor.historial', [
            'profile' => $profile,
            'completedDeliveries' => $internalCompleted
                ->concat($externalCompleted)
                ->sortByDesc('date')
                ->take(4)
                ->values(),
            'routes' => DeliveryRoute::query()
                ->with(['pedido.cliente', 'pedido.direccion'])
                ->where('repartidor_id', $user->id)
                ->latest()
                ->paginate(15),
            'externalOrders' => ExternalDeliveryOrder::query()
                ->where('repartidor_id', $user->id)
                ->latest()
                ->limit(15)
                ->get(),
            'completedCount' => DeliveryRoute::query()->where('repartidor_id', $user->id)->where('estado', 'completada')->count()
                + ExternalDeliveryOrder::query()->where('repartidor_id', $user->id)->where('status', 'delivered')->count(),
            'cancelledCount' => DeliveryRoute::query()->where('repartidor_id', $user->id)->where('estado', 'cancelada')->count()
                + ExternalDeliveryOrder::query()->where('repartidor_id', $user->id)->where('status', 'cancelled')->count(),
            'kmTotal' => DeliveryRoute::query()->where('repartidor_id', $user->id)->sum('distancia_km')
                + ExternalDeliveryOrder::query()->where('repartidor_id', $user->id)->sum('estimated_distance_km'),
        ]);
    }
}
