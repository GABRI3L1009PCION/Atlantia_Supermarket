<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Empleado\StoreEmpleadoRequest;
use App\Http\Requests\Admin\Empleado\UpdateEmpleadoRequest;
use App\Models\Empleado;
use App\Services\Empleados\EmpleadoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador administrativo de empleados.
 */
class EmpleadoController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly EmpleadoService $empleadoService)
    {
    }

    /**
     * Lista empleados internos.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Empleado::class);

        return view('admin.empleados.index', ['empleados' => $this->empleadoService->paginate($request->all())]);
    }

    /**
     * Registra un empleado.
     */
    public function store(StoreEmpleadoRequest $request): RedirectResponse
    {
        $this->authorize('create', Empleado::class);
        $this->empleadoService->create($request->validated());

        return back()->with('success', 'Empleado creado correctamente.');
    }

    /**
     * Actualiza un empleado.
     */
    public function update(UpdateEmpleadoRequest $request, Empleado $empleado): RedirectResponse
    {
        $this->authorize('update', $empleado);
        $this->empleadoService->update($empleado, $request->validated());

        return back()->with('success', 'Empleado actualizado correctamente.');
    }
}
