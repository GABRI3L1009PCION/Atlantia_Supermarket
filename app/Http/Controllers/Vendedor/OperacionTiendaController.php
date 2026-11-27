<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Support\HorarioTienda;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Horario, pausa y preferencias operativas de la tienda del vendedor.
 */
class OperacionTiendaController extends Controller
{
    /**
     * Duraciones de pausa disponibles (minutos). "dia" pausa hasta el cierre.
     *
     * @var array<string, string>
     */
    public const PAUSAS = [
        '30' => '30 minutos',
        '60' => '1 hora',
        '120' => '2 horas',
        'dia' => 'Resto del día',
    ];

    public function edit(Request $request): View
    {
        $vendor = $this->vendor($request);

        return view('vendedor.operacion.edit', [
            'vendor' => $vendor,
            'estado' => HorarioTienda::estado($vendor),
            'pausas' => self::PAUSAS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $vendor = $this->vendor($request);
        $data = $request->validate([
            'hora_apertura' => ['required', 'date_format:H:i'],
            'hora_cierre' => ['required', 'date_format:H:i', 'after:hora_apertura'],
            'tiempo_preparacion_min' => ['required', 'integer', 'between:5,180'],
            'alerta_sonora_pedidos' => ['nullable', 'boolean'],
            'pedido_minimo' => ['nullable', 'numeric', 'min:0', 'max:5000'],
        ], [
            'hora_cierre.after' => 'La hora de cierre debe ser después de la apertura.',
        ]);

        $vendor->update([
            ...$data,
            'alerta_sonora_pedidos' => $request->boolean('alerta_sonora_pedidos'),
            'pedido_minimo' => filled($data['pedido_minimo'] ?? null) && (float) $data['pedido_minimo'] > 0 ? round((float) $data['pedido_minimo'], 2) : null,
        ]);

        return back()->with('success', 'Horario y preferencias guardados.');
    }

    public function pausar(Request $request): RedirectResponse
    {
        $vendor = $this->vendor($request);
        $data = $request->validate([
            'duracion' => ['required', 'in:'.implode(',', array_keys(self::PAUSAS))],
            'motivo' => ['nullable', 'string', 'max:160'],
        ]);

        self::aplicarPausa($vendor, $data['duracion'], $data['motivo'] ?? null);

        return back()->with('success', 'Tienda en pausa: no recibirás pedidos nuevos hasta que la reanudes.');
    }

    public function reanudar(Request $request): RedirectResponse
    {
        $this->vendor($request)->update(['pausado_hasta' => null, 'pausa_motivo' => null]);

        return back()->with('success', 'Tu tienda vuelve a recibir pedidos.');
    }

    /**
     * Pausa una tienda (lo usa tambien el panel de administracion).
     */
    public static function aplicarPausa(Vendor $vendor, string $duracion, ?string $motivo = null): void
    {
        $hasta = $duracion === 'dia'
            ? now()->setTimeFromTimeString(HorarioTienda::estado($vendor)['cierre'])
            : now()->addMinutes((int) $duracion);

        if ($hasta->isPast()) {
            $hasta = now()->addDay()->setTimeFromTimeString(HorarioTienda::estado($vendor)['apertura']);
        }

        $vendor->update(['pausado_hasta' => $hasta, 'pausa_motivo' => $motivo]);
    }

    private function vendor(Request $request): Vendor
    {
        $vendor = $request->user()->vendor;
        abort_if($vendor === null, 403);

        return $vendor;
    }
}
