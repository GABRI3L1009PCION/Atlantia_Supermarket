<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Cupon\StoreCuponRequest;
use App\Http\Requests\Admin\Cupon\UpdateCuponRequest;
use App\Models\Cupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Controlador administrativo de cupones.
 */
class CuponController extends Controller
{
    /**
     * Lista cupones y metricas de uso.
     */
    public function index(): View
    {
        return view('admin.cupones.index', [
            'cupones' => Cupon::query()->latest()->paginate(20),
        ]);
    }

    /**
     * Crea un nuevo cupon.
     */
    public function store(StoreCuponRequest $request): RedirectResponse
    {
        Cupon::query()->create([
            ...$request->validated(),
            'uuid' => (string) Str::uuid(),
        ]);

        return back()->with('success', 'Cupon creado correctamente.');
    }

    /**
     * Actualiza un cupon existente.
     */
    public function update(UpdateCuponRequest $request, Cupon $cupon): RedirectResponse
    {
        $cupon->update($request->validated());

        return back()->with('success', 'Cupon actualizado correctamente.');
    }

    /**
     * Elimina un cupon.
     */
    public function destroy(Cupon $cupon): RedirectResponse
    {
        $cupon->delete();

        return back()->with('success', 'Cupon eliminado correctamente.');
    }
}
