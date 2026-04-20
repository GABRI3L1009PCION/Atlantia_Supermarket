<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea acciones de vendedores no aprobados o suspendidos.
 */
class VendedorAprobado
{
    /**
     * Verifica que el usuario vendedor tenga perfil aprobado.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('vendedor')) {
            abort(403, 'Acceso permitido solo para vendedores.');
        }

        $vendor = $user->vendor;

        if (! $vendor || ! $vendor->is_approved || $vendor->status !== 'approved') {
            abort(403, 'Tu perfil de vendedor aun no esta aprobado o fue suspendido.');
        }

        return $next($request);
    }
}
