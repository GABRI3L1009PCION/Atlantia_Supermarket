<?php

namespace App\Services\Storefront;

use App\Models\HeroBanner;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Gestion administrativa y resolucion publica de banners hero.
 */
class HeroBannerService
{
    /**
     * Lista banners ordenados para administracion.
     *
     * @return Collection<int, HeroBanner>
     */
    public function all(): Collection
    {
        return HeroBanner::query()->with('media')->ordered()->get();
    }

    /**
     * Guarda un banner nuevo.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): HeroBanner
    {
        return DB::transaction(function () use ($data): HeroBanner {
            $banner = HeroBanner::query()->create([
                'nombre' => $data['nombre'],
                'orden' => (int) ($data['orden'] ?? 0),
                'is_active' => (bool) ($data['is_active'] ?? false),
                'inicia_en' => $data['inicia_en'] ?? null,
                'termina_en' => $data['termina_en'] ?? null,
            ]);

            $this->syncImages($banner, $data);

            return $banner->refresh();
        });
    }

    /**
     * Actualiza un banner existente.
     *
     * @param array<string, mixed> $data
     */
    public function update(HeroBanner $banner, array $data): HeroBanner
    {
        return DB::transaction(function () use ($banner, $data): HeroBanner {
            $banner->update([
                'nombre' => $data['nombre'],
                'orden' => (int) ($data['orden'] ?? 0),
                'is_active' => (bool) ($data['is_active'] ?? false),
                'inicia_en' => $data['inicia_en'] ?? null,
                'termina_en' => $data['termina_en'] ?? null,
            ]);

            $this->syncImages($banner, $data);

            return $banner->refresh();
        });
    }

    /**
     * Elimina un banner.
     */
    public function delete(HeroBanner $banner): void
    {
        $banner->clearMediaCollection('hero_desktop');
        $banner->clearMediaCollection('hero_mobile');
        $banner->delete();
    }

    /**
     * Devuelve el banner vigente o un fallback visual por defecto.
     *
     * @return array<string, mixed>
     */
    public function resolveForStorefront(): array
    {
        $banner = HeroBanner::query()
            ->active()
            ->current()
            ->ordered()
            ->first();

        $desktopImage = $banner?->getFirstMediaUrl('hero_desktop');
        $mobileImage = $banner?->getFirstMediaUrl('hero_mobile') ?: $desktopImage;

        if ($banner === null || $desktopImage === '') {
            return [
                'name' => 'Fallback Atlantia',
                'desktop_image' => 'https://images.unsplash.com/photo-1604719312566-8912e9227c6a?auto=format&fit=crop&w=1800&q=80',
                'mobile_image' => 'https://images.unsplash.com/photo-1604719312566-8912e9227c6a?auto=format&fit=crop&w=900&q=80',
                'is_fallback' => true,
            ];
        }

        return [
            'name' => $banner->nombre,
            'desktop_image' => $desktopImage,
            'mobile_image' => $mobileImage,
            'is_fallback' => false,
        ];
    }

    /**
     * Guarda las imagenes del banner.
     *
     * @param array<string, mixed> $data
     */
    private function syncImages(HeroBanner $banner, array $data): void
    {
        if (($data['desktop_image'] ?? null) instanceof UploadedFile) {
            $banner->addMedia($data['desktop_image'])->toMediaCollection('hero_desktop');
        }

        if (($data['mobile_image'] ?? null) instanceof UploadedFile) {
            $banner->addMedia($data['mobile_image'])->toMediaCollection('hero_mobile');
        }
    }
}
