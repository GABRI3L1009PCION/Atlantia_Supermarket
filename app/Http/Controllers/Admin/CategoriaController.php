<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Categoria\StoreCategoriaRequest;
use App\Http\Requests\Admin\Categoria\UpdateCategoriaRequest;
use App\Models\Categoria;
use App\Services\Catalogo\CategoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador administrativo de categorias.
 */
class CategoriaController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly CategoriaService $categoriaService)
    {
    }

    /**
     * Lista categorias.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Categoria::class);

        return view('admin.categorias.index', ['categorias' => $this->categoriaService->tree($request->all())]);
    }

    /**
     * Guarda una categoria.
     */
    public function store(StoreCategoriaRequest $request): RedirectResponse
    {
        $this->authorize('create', Categoria::class);
        $this->categoriaService->create($request->validated());

        return back()->with('success', 'Categoria creada correctamente.');
    }

    /**
     * Actualiza una categoria.
     */
    public function update(UpdateCategoriaRequest $request, Categoria $categoria): RedirectResponse
    {
        $this->authorize('update', $categoria);
        $this->categoriaService->update($categoria, $request->validated());

        return back()->with('success', 'Categoria actualizada correctamente.');
    }

    /**
     * Desactiva una categoria.
     */
    public function destroy(Categoria $categoria): RedirectResponse
    {
        $this->authorize('delete', $categoria);
        $this->categoriaService->delete($categoria);

        return back()->with('success', 'Categoria desactivada correctamente.');
    }
}
