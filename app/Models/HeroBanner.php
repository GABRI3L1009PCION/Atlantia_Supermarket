<?php

namespace App\Models;

use Database\Factories\HeroBannerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Banner principal administrable del storefront.
 *
 * @property int $id
 * @property string $uuid
 * @property string $nombre
 * @property bool $is_active
 */
class HeroBanner extends Model implements HasMedia
{
    /** @use HasFactory<HeroBannerFactory> */
    use HasFactory;
    use InteractsWithMedia;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'nombre',
        'is_active',
        'orden',
        'inicia_en',
        'termina_en',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'orden' => 'integer',
            'inicia_en' => 'datetime',
            'termina_en' => 'datetime',
        ];
    }

    /**
     * Bootstrap del modelo.
     */
    protected static function booted(): void
    {
        static::creating(function (HeroBanner $banner): void {
            $banner->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * Filtra banners activos.
     *
     * @param Builder<HeroBanner> $query
     * @return Builder<HeroBanner>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Filtra banners vigentes a la fecha actual.
     *
     * @param Builder<HeroBanner> $query
     * @return Builder<HeroBanner>
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query
            ->where(fn (Builder $builder) => $builder
                ->whereNull('inicia_en')
                ->orWhere('inicia_en', '<=', now()))
            ->where(fn (Builder $builder) => $builder
                ->whereNull('termina_en')
                ->orWhere('termina_en', '>=', now()));
    }

    /**
     * Orden natural de banners.
     *
     * @param Builder<HeroBanner> $query
     * @return Builder<HeroBanner>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('orden')->orderByDesc('updated_at');
    }

    /**
     * Registra colecciones de medios del banner.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero_desktop')->singleFile();
        $this->addMediaCollection('hero_mobile')->singleFile();
    }

    /**
     * Nombre de ruta por defecto.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
