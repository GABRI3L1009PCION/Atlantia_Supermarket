<?php

namespace App\Support;

use App\Models\User;
use App\Services\Clientes\MembresiaService;

/**
 * Opciones del menú "Mi cuenta" (barra superior y barra de secciones de la cuenta del cliente).
 */
final class MenuCuenta
{
    /**
     * Secciones de la cuenta del cliente.
     *
     * @return array<int, array{clave: string, texto: string, ruta: string, activa: array<int, string>, icono: string}>
     */
    public static function cliente(): array
    {
        return [
            ['clave' => 'pedidos', 'texto' => 'Mis pedidos', 'ruta' => 'cliente.pedidos.index', 'activa' => ['cliente.pedidos.*', 'cliente.devoluciones.*'], 'icono' => 'M6 7h12l-1 13H7L6 7Zm3 0a3 3 0 0 1 6 0'],
            ['clave' => 'direcciones', 'texto' => 'Mis direcciones', 'ruta' => 'cliente.direcciones.index', 'activa' => ['cliente.direcciones.*'], 'icono' => 'M12 21s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12Zm0-9.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z'],
            ['clave' => 'favoritos', 'texto' => 'Favoritos', 'ruta' => 'cliente.wishlist.index', 'activa' => ['cliente.wishlist.*'], 'icono' => 'M12 20s-7-4.4-7-10a4.2 4.2 0 0 1 7-3.1A4.2 4.2 0 0 1 19 10c0 5.6-7 10-7 10Z'],
            ['clave' => 'saldo', 'texto' => 'Mi saldo', 'ruta' => 'cliente.saldo.index', 'activa' => ['cliente.saldo.*'], 'icono' => 'M3 7h18v10H3zM3 11h18M7 15h3'],
            ['clave' => 'membresia', 'texto' => MembresiaService::habilitada() ? 'Membresía' : 'Tarjeta guardada', 'ruta' => 'cliente.membresia.index', 'activa' => ['cliente.membresia.*', 'cliente.tarjeta.*'], 'icono' => MembresiaService::habilitada() ? 'M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.4l-5.2 2.7 1-5.8L3.5 9.2l5.9-.9L12 3Z' : 'M3 6h18v12H3zM3 10h18M7 15h4'],
            ['clave' => 'resenas', 'texto' => 'Mis reseñas', 'ruta' => 'cliente.resenas.index', 'activa' => ['cliente.resenas.*'], 'icono' => 'M4 5h16v11H8l-4 4V5Z'],
            ['clave' => 'perfil', 'texto' => 'Mi perfil', 'ruta' => 'cliente.perfil.edit', 'activa' => ['cliente.perfil.*'], 'icono' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21a8 8 0 0 1 16 0'],
            ['clave' => 'seguridad', 'texto' => 'Seguridad', 'ruta' => 'cuenta.seguridad', 'activa' => ['cuenta.seguridad*'], 'icono' => 'M12 3 5 6v5c0 4.5 3 8.3 7 10 4-1.7 7-5.5 7-10V6l-7-3Z'],
        ];
    }

    /**
     * Ruta del panel de trabajo según el rol (null para clientes).
     */
    public static function panelDe(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        return match (true) {
            $user->isAdministrator() => 'admin.dashboard',
            $user->hasRole('vendedor') => 'vendedor.dashboard',
            $user->hasRole('repartidor') => 'repartidor.dashboard',
            $user->hasAnyRole(['empleado', 'bodeguero', 'soporte', 'contabilidad_finanzas', 'supervisor_logistica']) => 'empleado.dashboard',
            default => null,
        };
    }

    public static function iniciales(?string $nombre): string
    {
        $partes = preg_split('/\s+/', trim((string) $nombre)) ?: [];
        $letras = mb_substr($partes[0] ?? '', 0, 1).mb_substr($partes[1] ?? '', 0, 1);

        return mb_strtoupper($letras !== '' ? $letras : 'A');
    }
}
