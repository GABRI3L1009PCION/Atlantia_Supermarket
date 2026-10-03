<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Guarda la ubicacion activa del cliente para filtrar el marketplace.
 */
class UbicacionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $municipios = $this->marketplaceMunicipios();

        $data = $request->validate([
            'municipio' => ['nullable', 'string', Rule::in($municipios)],
        ]);

        if (empty($data['municipio'])) {
            $request->session()->forget('cliente_municipio');
        } else {
            $request->session()->put('cliente_municipio', $data['municipio']);
        }

        return back()->with('success', 'Ubicacion actualizada.');
    }

    /**
     * @return array<int, string>
     */
    private function marketplaceMunicipios(): array
    {
        $municipios = config('atlantia.marketplace.municipios', ['Puerto Barrios', 'Santo Tomas']);

        return array_values(array_filter(
            array_map(static fn (mixed $municipio): string => trim((string) $municipio), (array) $municipios),
            static fn (string $municipio): bool => $municipio !== ''
        ));
    }
}
