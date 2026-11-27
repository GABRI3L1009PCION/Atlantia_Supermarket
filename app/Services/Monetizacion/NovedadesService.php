<?php

namespace App\Services\Monetizacion;

use App\Models\Producto;
use App\Models\PushSuscripcion;
use App\Models\ServicioContratado;
use App\Services\Notificaciones\PushService;
use Illuminate\Support\Collection;

/**
 * Productos nuevos y ofertas: la pagina "Novedades" y el aviso a clientes.
 * Las ofertas destacadas pagadas por los comercios van primero.
 */
class NovedadesService
{
    public function __construct(private readonly PushService $push) {}

    /**
     * @return Collection<int, Producto>
     */
    public function destacados(): Collection
    {
        $ids = ServicioContratado::query()->vigentes('destacado')
            ->where('referencia_type', (new Producto)->getMorphClass())
            ->pluck('referencia_id');

        return $this->consulta()->whereIn('id', $ids)->get();
    }

    /**
     * @return Collection<int, Producto>
     */
    public function ofertas(int $limite = 24): Collection
    {
        return $this->consulta()
            ->whereNotNull('precio_oferta')
            ->whereColumn('precio_oferta', '<', 'precio_base')
            ->latest('updated_at')
            ->limit($limite)
            ->get();
    }

    /**
     * @return Collection<int, Producto>
     */
    public function nuevos(int $dias = 14, int $limite = 24): Collection
    {
        return $this->consulta()
            ->where('publicado_at', '>=', now()->subDays($dias))
            ->latest('publicado_at')
            ->limit($limite)
            ->get();
    }

    /**
     * Envia el aviso a los dispositivos que aceptaron ofertas. Devuelve cuantos lo recibieron.
     */
    public function enviarAviso(): int
    {
        $destacado = $this->destacados()->first();
        $ofertas = $this->ofertas(100)->count();
        $nuevos = $this->nuevos(7, 100)->count();

        if ($destacado === null && $ofertas === 0 && $nuevos === 0) {
            return 0;
        }

        $titulo = $destacado !== null
            ? 'Oferta destacada: '.$destacado->nombre
            : 'Novedades de la semana en Atlantia';
        $partes = array_filter([
            $destacado !== null ? 'A Q '.number_format((float) ($destacado->precio_oferta ?? $destacado->precio_base), 2) : null,
            $ofertas > 0 ? "{$ofertas} ".($ofertas === 1 ? 'oferta' : 'ofertas') : null,
            $nuevos > 0 ? "{$nuevos} ".($nuevos === 1 ? 'producto nuevo' : 'productos nuevos') : null,
        ]);

        $enviados = 0;
        PushSuscripcion::query()->where('ofertas', true)->chunkById(200, function ($suscripciones) use (&$enviados, $titulo, $partes): void {
            $enviados += $this->push->enviar($suscripciones, $titulo, implode(' · ', $partes), route('novedades'), 'novedades');
        });

        return $enviados;
    }

    private function consulta()
    {
        return Producto::query()->publicados()->with(['vendor', 'inventario', 'imagenPrincipal']);
    }
}
