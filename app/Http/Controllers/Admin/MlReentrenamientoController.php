<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DispararReentrenamientoRequest;
use App\Services\Ml\MlTrainingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de reentrenamiento ML.
 */
class MlReentrenamientoController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly MlTrainingService $mlTrainingService)
    {
    }

    /**
     * Lista jobs de entrenamiento.
     */
    public function index(Request $request): View
    {
        $this->authorize('trainMl', $request->user());

        return view('admin.ml.training', ['jobs' => $this->mlTrainingService->paginate($request->all())]);
    }

    /**
     * Inicia un job de reentrenamiento.
     */
    public function store(DispararReentrenamientoRequest $request): RedirectResponse
    {
        $this->authorize('trainMl', $request->user());
        $this->mlTrainingService->start($request->validated(), $request->user());

        return back()->with('success', 'Reentrenamiento solicitado correctamente.');
    }
}
