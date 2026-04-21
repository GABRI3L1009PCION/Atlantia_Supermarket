<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Resena\BatchModerateResenaRequest;
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

        return view('admin.resenas.index', [
            'resenas' => $this->resenaModerationService->paginate($request->all()),
            'dashboard' => $this->resenaModerationService->dashboard($request->all()),
        ]);
    }

    /**
     * Muestra una resena para moderacion.
     */
    public function show(Resena $resena): View
    {
        $this->authorize('view', $resena);

        return view('admin.resenas.show', ['resena' => $this->resenaModerationService->detail($resena)]);
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

    /**
     * Modera varias resenas desde la bandeja administrativa.
     */
    public function moderateBatch(BatchModerateResenaRequest $request): RedirectResponse
    {
        $this->authorize('moderateAny', Resena::class);
        $procesadas = $this->resenaModerationService->moderateBatch(
            $request->validated('resenas'),
            $request->validated('accion'),
            $request->validated('notas'),
            $request->user()
        );

        return back()->with('success', "Se moderaron {$procesadas} resenas del lote.");
    }
}
