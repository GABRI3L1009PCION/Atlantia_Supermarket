<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\SaldoMovimiento;
use App\Services\Clientes\ReferidosService;
use App\Services\Clientes\SaldoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Saldo a favor del cliente y programa "Invita a un amigo".
 */
class SaldoController extends Controller
{
    public function __construct(
        private readonly SaldoService $saldoService,
        private readonly ReferidosService $referidosService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $codigo = $this->referidosService->codigoDe($user);

        return view('cliente.saldo.index', [
            'saldo' => $this->saldoService->disponible($user),
            'movimientos' => SaldoMovimiento::query()->where('user_id', $user->id)->latest('id')->limit(30)->get(),
            'codigo' => $codigo,
            'enlace' => route('referidos.invitacion', $codigo),
            'recompensa' => ReferidosService::recompensa(),
            'invitados' => $user->newQuery()->where('referido_por_id', $user->id)->count(),
            'invitadosConCompra' => $user->newQuery()->where('referido_por_id', $user->id)->whereNotNull('referido_recompensado_at')->count(),
        ]);
    }

    /**
     * Enlace de invitacion: guarda el codigo y lleva al registro.
     */
    public function invitacion(Request $request, string $codigo): RedirectResponse
    {
        $request->session()->put('referido_codigo', Str::upper(Str::limit($codigo, 16, '')));

        return redirect()->route($request->user() ? 'catalogo.index' : 'register')
            ->with('success', 'Te invitaron a Atlantia Store: recibe Q '.number_format(ReferidosService::recompensa(), 2).' de saldo con tu primera compra.');
    }

    /**
     * Administracion: acredita saldo al cliente de un pedido (por ejemplo, producto faltante pagado en efectivo).
     */
    public function acreditarDesdePedido(Request $request, Pedido $pedido): RedirectResponse
    {
        $this->authorize('update', $pedido);
        $data = $request->validate([
            'monto' => ['required', 'numeric', 'min:0.01', 'max:5000'],
            'motivo' => ['required', 'string', 'max:200'],
        ]);

        abort_if($pedido->cliente === null, 422);

        $this->saldoService->acreditar(
            $pedido->cliente,
            (float) $data['monto'],
            'ajuste_pedido',
            $data['motivo'].' (pedido '.$pedido->numero_pedido.')',
            $pedido,
            $request->user()
        );

        return back()->with('success', 'Saldo acreditado al cliente.');
    }
}
