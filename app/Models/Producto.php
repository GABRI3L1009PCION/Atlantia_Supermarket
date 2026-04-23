<?php

namespace App\Models;

use App\Models\Ml\ProductRecommendation;
use App\Models\Ml\RestockSuggestion;
use App\Models\Ml\SalesPrediction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Producto vendido por un vendedor local.
 *
 * @property int $id
 * @property string $uuid
 * @property string $sku
 * @property string $nombre
 */
class Producto extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use Searchable;
    use SoftDeletes;

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'vendor_id',
        'categoria_id',
        'sku',
        'nombre',
        'slug',
        'descripcion',
        'precio_base',
        'precio_oferta',
        'peso_gramos',
        'unidad_medida',
        'requiere_refrigeracion',
        'is_active',
        'visible_catalogo',
        'publicado_at',
    ];

    /**
     * Obtiene los casts del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'precio_base' => 'decimal:2',
            'precio_oferta' => 'decimal:2',
            'peso_gramos' => 'integer',
            'requiere_refrigeracion' => 'boolean',
            'is_active' => 'boolean',
            'visible_catalogo' => 'boolean',
            'publicado_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Vendedor propietario del producto.
     *
     * @return BelongsTo<Vendor, Producto>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Categoria del producto.
     *
     * @return BelongsTo<Categoria, Producto>
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * Imagenes de negocio asociadas al producto.
     *
     * @return HasMany<ProductoImagen>
     */
    public function imagenes(): HasMany
    {
        return $this->hasMany(ProductoImagen::class);
    }

    /**
     * Imagen principal del producto.
     *
     * @return HasOne<ProductoImagen>
     */
    public function imagenPrincipal(): HasOne
    {
        return $this->hasOne(ProductoImagen::class)->where('es_principal', true);
    }

    /**
     * Inventario actual del producto.
     *
     * @return HasOne<Inventario>
     */
    public function inventario(): HasOne
    {
        return $this->hasOne(Inventario::class);
    }

    /**
     * Items de pedido que contienen el producto.
     *
     * @return HasMany<PedidoItem>
     */
    public function pedidoItems(): HasMany
    {
        return $this->hasMany(PedidoItem::class);
    }

    /**
     * Items de carrito que contienen el producto.
     *
     * @return HasMany<CarritoItem>
     */
    public function carritoItems(): HasMany
    {
        return $this->hasMany(CarritoItem::class);
    }

    /**
     * Resenas asociadas al producto.
     *
     * @return HasMany<Resena>
     */
    public function resenas(): HasMany
    {
        return $this->hasMany(Resena::class);
    }

    /**
     * Predicciones de venta del producto.
     *
     * @return HasMany<SalesPrediction>
     */
    public function salesPredictions(): HasMany
    {
        return $this->hasMany(SalesPrediction::class);
    }

    /**
     * Sugerencias de reabastecimiento del producto.
     *
     * @return HasMany<RestockSuggestion>
     */
    public function restockSuggestions(): HasMany
    {
        return $this->hasMany(RestockSuggestion::class);
    }

    /**
     * Recomendaciones donde aparece el producto.
     *
     * @return HasMany<ProductRecommendation>
     */
    public function recommendations(): HasMany
    {
        return $this->hasMany(ProductRecommendation::class);
    }

    /**
     * Filtra productos activos.
     *
     * @param Builder<Producto> $query
     * @return Builder<Producto>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Filtra productos visibles en catalogo.
     *
     * @param Builder<Producto> $query
     * @return Builder<Producto>
     */
    public function scopeVisibleCatalogo(Builder $query): Builder
    {
        return $query->where('visible_catalogo', true);
    }

    /**
     * Filtra productos publicados.
     *
     * @param Builder<Producto> $query
     * @return Builder<Producto>
     */
    public function scopePublicados(Builder $query): Builder
    {
        return $query->whereNotNull('publicado_at')->active()->visibleCatalogo();
    }

    /**
     * Filtra productos por vendedor.
     *
     * @param Builder<Producto> $query
     * @param int $vendorId
     * @return Builder<Producto>
     */
    public function scopeForVendor(Builder $query, int $vendorId): Builder
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Datos indexables en Meilisearch.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'vendor_id' => $this->vendor_id,
            'categoria_id' => $this->categoria_id,
            'sku' => $this->sku,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio_base' => $this->precio_base,
            'precio_oferta' => $this->precio_oferta,
            'is_active' => $this->is_active,
            'visible_catalogo' => $this->visible_catalogo,
        ];
    }

    /**
     * Registra conversiones WebP para catalogo responsive.
     *
     * @param Media|null $media
     * @return void
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('card')
            ->width(600)
            ->height(400)
            ->format('webp');

        $this->addMediaConversion('full')
            ->width(1200)
            ->format('webp');
    }
}
