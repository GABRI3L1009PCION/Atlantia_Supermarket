<?php

namespace App\Support;

use App\Models\Producto;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

class WishlistStore
{
    private const SESSION_KEY = 'wishlist.product_ids';

    public static function toggle(Request $request, int $productoId, ?User $user): bool
    {
        if ($user !== null) {
            self::syncSessionToDatabase($request, $user);

            $registro = Wishlist::query()
                ->where('user_id', $user->id)
                ->where('producto_id', $productoId)
                ->first();

            if ($registro !== null) {
                $registro->delete();

                return false;
            }

            Wishlist::query()->create([
                'user_id' => $user->id,
                'producto_id' => $productoId,
            ]);

            return true;
        }

        $ids = self::sessionIds($request);

        if (in_array($productoId, $ids, true)) {
            $request->session()->put(self::SESSION_KEY, array_values(array_diff($ids, [$productoId])));

            return false;
        }

        $ids[] = $productoId;
        $request->session()->put(self::SESSION_KEY, array_values(array_unique($ids)));

        return true;
    }

    public static function contains(Request $request, int $productoId, ?User $user): bool
    {
        if ($user !== null) {
            self::syncSessionToDatabase($request, $user);

            return Wishlist::query()
                ->where('user_id', $user->id)
                ->where('producto_id', $productoId)
                ->exists();
        }

        return in_array($productoId, self::sessionIds($request), true);
    }

    public static function products(Request $request, ?User $user): Collection
    {
        if ($user !== null) {
            self::syncSessionToDatabase($request, $user);

            return Producto::query()
                ->with(['vendor', 'categoria', 'inventario', 'imagenPrincipal'])
                ->whereHas('wishlists', fn ($query) => $query->where('user_id', $user->id))
                ->latest()
                ->get();
        }

        $ids = self::sessionIds($request);

        if ($ids === []) {
            return collect();
        }

        $productos = Producto::query()
            ->with(['vendor', 'categoria', 'inventario', 'imagenPrincipal'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(fn (int $id) => $productos->get($id))
            ->filter();
    }

    public static function paginateProducts(Request $request, ?User $user, int $perPage = 20): LengthAwarePaginator
    {
        $productos = self::products($request, $user)->values();
        $currentPage = Paginator::resolveCurrentPage() ?: 1;
        $offset = max(0, ($currentPage - 1) * $perPage);
        $items = $productos->slice($offset, $perPage)->values();

        return new Paginator(
            $items,
            $productos->count(),
            $perPage,
            $currentPage,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    private static function syncSessionToDatabase(Request $request, User $user): void
    {
        $ids = self::sessionIds($request);

        if ($ids === []) {
            return;
        }

        foreach ($ids as $productoId) {
            Wishlist::query()->firstOrCreate([
                'user_id' => $user->id,
                'producto_id' => $productoId,
            ]);
        }

        $request->session()->forget(self::SESSION_KEY);
    }

    /**
     * @return array<int, int>
     */
    private static function sessionIds(Request $request): array
    {
        return collect($request->session()->get(self::SESSION_KEY, []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
