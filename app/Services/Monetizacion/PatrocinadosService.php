<?php

namespace App\Services\Monetizacion;

use App\Models\Producto;
use App\Models\ServicioContratado;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Productos patrocinados que se muestran arriba de una busqueda o de una categoria.
 */
class PatrocinadosService
{
    /**
     * @return Collection<int, array{servicio: ServicioContratado, producto: Producto}>
     */
    public function paraResultados(?string $busqueda, ?int $categoriaId): Collection
    {
        $busqueda = trim((string) $busqueda);
        if ($busqueda === '' && ! $categoriaId) {
            return collect();
        }

        $palabras = collect(preg_split('/\s+/', Str::lower(Str::ascii($busqueda))) ?: [])
            ->filter(fn (string $palabra): bool => mb_strlen($palabra) >= 3);

        $elegidos = ServicioContratado::query()
            ->vigentes('patrocinado')
            ->where('referencia_type', (new Producto)->getMorphClass())
            ->get()
            ->map(function (ServicioContratado $servicio): ?array {
                $producto = Producto::query()->publicados()->with(['vendor', 'inventario', 'imagenPrincipal'])->find($servicio->referencia_id);

                return $producto ? ['servicio' => $servicio, 'producto' => $producto] : null;
            })
            ->filter()
            ->filter(function (array $item) use ($palabras, $categoriaId): bool {
                $producto = $item['producto'];
                if ($categoriaId && (int) $producto->categoria_id === (int) $categoriaId) {
                    return true;
                }

                $nombre = Str::lower(Str::ascii($producto->nombre.' '.$producto->descripcion));

                return $palabras->isNotEmpty() && $palabras->contains(fn (string $palabra): bool => str_contains($nombre, $palabra));
            })
            ->shuffle()
            ->take((int) config('monetizacion.patrocinado.maximo_en_resultados', 2))
            ->values();

        if ($elegidos->isNotEmpty()) {
            ServicioContratado::query()->whereIn('id', $elegidos->pluck('servicio.id'))->increment('vistas');
        }

        return $elegidos;
    }
}
