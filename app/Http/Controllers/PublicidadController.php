<?php

namespace App\Http\Controllers;

use App\Models\HeroBanner;
use App\Models\Producto;
use App\Models\ServicioContratado;
use Illuminate\Http\RedirectResponse;

/**
 * Cuenta los clics de la publicidad antes de llevar al destino.
 */
class PublicidadController extends Controller
{
    public function banner(string $uuid): RedirectResponse
    {
        $banner = HeroBanner::query()->where('uuid', $uuid)->firstOrFail();
        abort_if(blank($banner->url_destino), 404);

        $banner->increment('clics');

        return redirect()->away($banner->url_destino);
    }

    public function patrocinado(ServicioContratado $servicio): RedirectResponse
    {
        abort_unless($servicio->tipo === 'patrocinado', 404);
        $producto = $servicio->referencia;
        abort_unless($producto instanceof Producto, 404);

        $servicio->increment('clics');

        return redirect()->route('productos.show', $producto);
    }
}
