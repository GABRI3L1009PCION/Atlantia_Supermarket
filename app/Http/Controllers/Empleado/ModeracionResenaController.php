<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use App\Http\Requests\Empleado\Resena\ModerateResenaRequest;
use App\Models\Resena;
use App\Services\Antifraude\AnalisisResenaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de moderacion de resenas para empleados.
 */
class ModeracionResenaController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly AnalisisResenaService $analisisResenaService)
    {
    }

    /**
     * Lista resenas pendientes o marcadas por ML.
     */
    public function index(Request $request): View
    {
        $this->authorize('moderateAny', Resena::class);

        return view('empleado.resenas.index', ['resenas' => $this->analisisResenaService->pending($request->all())]);
    }

    /**
     * Modera una resena.
     */
    public function update(ModerateResenaRequest $request, Resena $resena): RedirectResponse
    {
        $this->authorize('moderate', $resena);
        $this->analisisResenaService->moderate($resena, $request->validated(), $request->user());

        return back()->with('success', 'Resena moderada correctamente.');
    }
}
