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
    /**
     * @var array<int, string>
     */
    private const MUNICIPIOS = [
        'Puerto Barrios',
        'Santo Tomas',
        'Morales',
        'Los Amates',
        'Livingston',
        'El Estor',
    ];

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'municipio' => ['nullable', 'string', Rule::in(self::MUNICIPIOS)],
        ]);

        if (empty($data['municipio'])) {
            $request->session()->forget('cliente_municipio');
        } else {
            $request->session()->put('cliente_municipio', $data['municipio']);
        }

        return back()->with('success', 'Ubicacion actualizada.');
    }
}
