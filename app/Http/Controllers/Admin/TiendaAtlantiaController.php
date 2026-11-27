<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\AccesoTiendaAtlantiaNotification;
use App\Services\Auth\ImpersonationService;
use App\Services\Vendedores\AccesoVendedorService;
use App\Support\HorarioTienda;
use App\Support\TiendaAtlantia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Atlantia Store se opera desde el mismo panel que cualquier tienda.
 * Aquí el administrador entra a ese panel y decide si un empleado tiene acceso directo.
 */
class TiendaAtlantiaController extends Controller
{
    public function index(): View
    {
        $vendor = $this->vendor();
        $operador = TiendaAtlantia::operador($vendor);

        return view('admin.tienda-atlantia.index', [
            'vendor' => $vendor,
            'operador' => $operador,
            'accesoDirecto' => $operador->email !== TiendaAtlantia::CORREO_OPERADOR,
            'horario' => HorarioTienda::estado($vendor),
            'resumen' => [
                'pedidos_pendientes' => Pedido::query()->where('vendor_id', $vendor->id)->whereIn('estado', ['pendiente', 'confirmado', 'preparando'])->count(),
                'productos' => Producto::query()->where('vendor_id', $vendor->id)->count(),
                'agotados' => Producto::query()->where('vendor_id', $vendor->id)->whereHas('inventario', fn ($q) => $q->whereColumn('stock_reservado', '>=', 'stock_actual'))->count(),
                'resenas_sin_responder' => \App\Models\Resena::query()
                    ->whereHas('producto', fn ($q) => $q->where('vendor_id', $vendor->id))
                    ->where('aprobada', true)->whereNull('respuesta_vendedor')->count(),
            ],
            'entradas' => AuditLog::query()->where('event', 'tienda_atlantia.entrada')->with('user')->latest('id')->limit(8)->get(),
        ]);
    }

    public function entrar(Request $request, ImpersonationService $sesion): RedirectResponse
    {
        abort_unless($request->user()->isAdministrator(), 403);
        $operador = TiendaAtlantia::operador($this->vendor());

        $ruta = $sesion->entrarATiendaAtlantia($request, $request->user(), $operador);

        return redirect()->route($ruta)->with('success', 'Estás en el panel de Atlantia Store. Usa “Volver al administrador” arriba para regresar.');
    }

    /**
     * Da acceso directo a un empleado: la cuenta de la tienda pasa a usar su correo
     * y le enviamos el enlace para crear su contraseña.
     */
    public function acceso(Request $request, AccesoVendedorService $acceso): RedirectResponse
    {
        abort_unless($request->user()->isAdministrator(), 403);
        $operador = TiendaAtlantia::operador($vendor = $this->vendor());

        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($operador->id)],
        ], [
            'email.required' => 'Escribe el correo del empleado.',
            'email.email' => 'Escribe un correo válido.',
            'email.unique' => 'Ese correo ya tiene otra cuenta en Atlantia. Usa un correo distinto para la tienda.',
        ]);

        $operador->forceFill([
            'email' => Str::lower(trim($data['email'])),
            'is_system_user' => false,
        ])->save();

        AuditLog::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'event' => 'tienda_atlantia.acceso_asignado',
            'auditable_type' => User::class,
            'auditable_id' => $operador->id,
            'metadata' => ['correo' => AccesoVendedorService::correoEnmascarado($operador->email)],
            'method' => 'SERVICE',
        ]);

        try {
            $operador->notify(new AccesoTiendaAtlantiaNotification($vendor, $acceso->enlaceCrearContrasena($operador)));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('warning', 'Guardamos el correo, pero no se pudo enviar el enlace. Revisa la configuración de correo.');
        }

        return back()->with('success', 'Listo. Enviamos el enlace para crear la contraseña a '.AccesoVendedorService::correoEnmascarado($operador->email).'.');
    }

    private function vendor(): Vendor
    {
        $vendor = TiendaAtlantia::vendor();
        abort_if($vendor === null, 404, 'Todavía no existe la tienda Atlantia Store. Crea un producto propio primero.');

        return $vendor;
    }
}
