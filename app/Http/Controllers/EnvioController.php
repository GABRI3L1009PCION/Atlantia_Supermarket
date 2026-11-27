<?php

namespace App\Http\Controllers;

use App\Models\ExternalDeliveryOrder;
use App\Models\MarketCourierStatus;
use App\Services\Monetizacion\EnviosParticularesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Atlantia Envios: solicitud publica y seguimiento del envio.
 */
class EnvioController extends Controller
{
    private const PASOS = [
        'requested' => 0, 'offered' => 0, 'assigned' => 1, 'accepted' => 1,
        'arrived_pickup' => 1, 'pickup_not_ready' => 1, 'picked_up' => 2, 'arrived_customer' => 2, 'delivered' => 3,
    ];

    public function create(): View
    {
        return view('servicios.envios.create', [
            'municipios' => EnviosParticularesService::municipios(),
            'tarifas' => config('monetizacion.envios'),
        ]);
    }

    public function store(Request $request, EnviosParticularesService $envios): RedirectResponse
    {
        $municipios = EnviosParticularesService::municipios();
        $telefono = ['required', 'regex:/^(\+?502)?\s?[2-7]\d{3}[\s-]?\d{4}$/'];
        $data = $request->validate([
            'remitente_nombre' => ['required', 'string', 'max:120'],
            'remitente_telefono' => $telefono,
            'origen_municipio' => ['required', Rule::in($municipios)],
            'origen_direccion' => ['required', 'string', 'max:255'],
            'destinatario_nombre' => ['required', 'string', 'max:120'],
            'destinatario_telefono' => $telefono,
            'destino_municipio' => ['required', Rule::in($municipios)],
            'destino_direccion' => ['required', 'string', 'max:255'],
            'descripcion' => ['required', 'string', 'max:160'],
            'tamano' => ['required', Rule::in(array_keys((array) config('monetizacion.envios.tamanos')))],
            'quien_paga' => ['required', Rule::in(['remitente', 'destinatario'])],
            'notas_recogida' => ['nullable', 'string', 'max:255'],
            'notas_entrega' => ['nullable', 'string', 'max:255'],
            'acepta' => ['accepted'],
        ], [
            'acepta.accepted' => 'Confirma que el envío no contiene artículos prohibidos.',
            '*.regex' => 'Ingresa un teléfono de Guatemala válido (8 dígitos).',
        ]);

        $envio = $envios->solicitar($data);

        return redirect()->route('envios.show', $envio->uuid)->with('success', 'Recibimos tu envío. Te asignamos un repartidor en unos minutos.');
    }

    public function show(string $uuid): View
    {
        $envio = $this->envio($uuid);

        return view('servicios.envios.show', [
            'envio' => $envio,
            'paso' => self::PASOS[$envio->status] ?? 0,
            'cancelado' => $envio->status === 'cancelled',
        ]);
    }

    /**
     * Estado y ubicacion del repartidor (solo cuando el paquete ya va hacia el destino).
     */
    public function live(string $uuid): JsonResponse
    {
        $envio = $this->envio($uuid);
        $enCamino = in_array($envio->status, ['picked_up', 'arrived_customer'], true);
        $gps = $enCamino
            ? MarketCourierStatus::query()->where('external_delivery_order_id', $envio->id)->latest('timestamp_gps')->first()
            : null;

        return response()->json([
            'estado' => $envio->status,
            'paso' => self::PASOS[$envio->status] ?? 0,
            'repartidor' => $gps ? ['latitude' => (float) $gps->latitude, 'longitude' => (float) $gps->longitude] : null,
        ]);
    }

    private function envio(string $uuid): ExternalDeliveryOrder
    {
        return ExternalDeliveryOrder::query()
            ->where('uuid', $uuid)
            ->where('source_channel', 'atlantia_envios')
            ->firstOrFail();
    }
}
