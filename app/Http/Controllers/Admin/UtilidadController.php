<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GastoFijo;
use App\Services\Reportes\UtilidadMensualService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Reporte de utilidad mensual y registro de gastos fijos.
 */
class UtilidadController extends Controller
{
    public function index(Request $request, UtilidadMensualService $utilidad): View
    {
        $mes = $this->mes($request->query('mes'));

        return view('admin.utilidad.index', [
            'reporte' => $utilidad->calcular($mes),
            'mesValor' => $mes->format('Y-m'),
            'categorias' => GastoFijo::CATEGORIAS,
        ]);
    }

    public function guardarGasto(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mes' => ['required', 'date_format:Y-m'],
            'categoria' => ['required', Rule::in(array_keys(GastoFijo::CATEGORIAS))],
            'concepto' => ['required', 'string', 'max:160'],
            'monto' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'notas' => ['nullable', 'string', 'max:255'],
        ]);

        GastoFijo::query()->create([
            ...$data,
            'mes' => Carbon::createFromFormat('Y-m', $data['mes'])->startOfMonth(),
            'registrado_por' => $request->user()->id,
        ]);

        return redirect()->route('admin.utilidad.index', ['mes' => $data['mes']])->with('success', 'Gasto registrado.');
    }

    public function eliminarGasto(GastoFijo $gasto): RedirectResponse
    {
        $mes = $gasto->mes->format('Y-m');
        $gasto->delete();

        return redirect()->route('admin.utilidad.index', ['mes' => $mes])->with('success', 'Gasto eliminado.');
    }

    private function mes(mixed $valor): Carbon
    {
        if (is_string($valor) && preg_match('/^\d{4}-\d{2}$/', $valor)) {
            return Carbon::createFromFormat('Y-m', $valor)->startOfMonth();
        }

        return now()->startOfMonth();
    }
}
