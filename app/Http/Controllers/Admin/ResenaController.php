<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Resena\ModerateResenaRequest;
use App\Models\Resena;
use App\Services\Resenas\ResenaModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador administrativo de resenas.
 */
class ResenaController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly ResenaModerationService $resenaModerationService)
    {
    }

    /**
     * Lista resenas para moderacion.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Resena::class);

        return view('admin.resenas.index', ['resenas' => $this->resenaModerationService->paginate($request->all())]);
    }

    /**
     * Modera una resena.
     */
    public function moderate(ModerateResenaRequest $request, Resena $resena): RedirectResponse
    {
        $this->authorize('moderate', $resena);
        $this->resenaModerationService->moderate($resena, $request->validated(), $request->user());

        return back()->with('success', 'Resena moderada correctamente.');
    }
}
