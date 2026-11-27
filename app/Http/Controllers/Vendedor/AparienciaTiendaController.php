<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Support\CategoriasNegocio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Imagen pública de la tienda: portada (estilo Facebook), logo y descripción.
 */
class AparienciaTiendaController extends Controller
{
    /** Carpeta del disco público donde se guardan las imágenes de cada tienda. */
    private const CARPETA = 'comercios';

    public function edit(Request $request): View
    {
        $vendor = $this->vendor($request);

        return view('vendedor.apariencia.edit', [
            'vendor' => $vendor,
            'portadaUrl' => $this->url($vendor->cover_path),
            'logoUrl' => $this->url($vendor->logo_path),
            'opcionesCategorias' => CategoriasNegocio::OPCIONES,
            'categoriasElegidas' => (array) ($vendor->categorias_negocio ?: array_filter([CategoriasNegocio::claveDe($vendor->business_category)])),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $vendor = $this->vendor($request);
        $data = $request->validate([
            'portada' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=900,min_height=150'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=120,min_height=120'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'categorias' => ['required', 'array', 'min:1', 'max:'.CategoriasNegocio::MAXIMO],
            'categorias.*' => ['string', 'distinct', 'in:'.implode(',', CategoriasNegocio::claves())],
        ], [
            'portada.image' => 'La portada debe ser una imagen.',
            'portada.mimes' => 'La portada debe ser JPG, PNG o WEBP.',
            'portada.max' => 'La portada no puede pesar más de 5 MB.',
            'portada.dimensions' => 'La portada es muy pequeña: usa una imagen de 1800 × 300 píxeles.',
            'logo.image' => 'El logo debe ser una imagen.',
            'logo.mimes' => 'El logo debe ser JPG, PNG o WEBP.',
            'logo.max' => 'El logo no puede pesar más de 2 MB.',
            'logo.dimensions' => 'El logo es muy pequeño: usa una imagen de al menos 120 × 120 píxeles.',
            'descripcion.max' => 'La descripción puede tener hasta 500 caracteres.',
            'categorias.required' => 'Elige al menos una categoría para tu tienda.',
            'categorias.min' => 'Elige al menos una categoría para tu tienda.',
            'categorias.max' => 'Puedes elegir hasta '.CategoriasNegocio::MAXIMO.' categorías.',
            'categorias.*.in' => 'Esa categoría no está disponible.',
        ]);

        $categorias = array_values(array_unique($data['categorias']));
        $cambios = [
            'descripcion' => filled($data['descripcion'] ?? null) ? trim($data['descripcion']) : null,
            'categorias_negocio' => $categorias,
            'business_category' => $categorias[0],
        ];

        if ($request->hasFile('portada')) {
            $cambios['cover_path'] = $this->guardar($vendor, $request->file('portada'), 'portada', $vendor->cover_path);
        }

        if ($request->hasFile('logo')) {
            $cambios['logo_path'] = $this->guardar($vendor, $request->file('logo'), 'logo', $vendor->logo_path);
        }

        $vendor->update($cambios);

        return back()->with('success', 'Listo, tu tienda ya muestra los cambios.');
    }

    public function quitarPortada(Request $request): RedirectResponse
    {
        $vendor = $this->vendor($request);
        $this->borrarPropia($vendor, $vendor->cover_path);
        $vendor->update(['cover_path' => null]);

        return back()->with('success', 'Quitamos la portada. Tu tienda usará el fondo de Atlantia.');
    }

    private function guardar(Vendor $vendor, UploadedFile $archivo, string $tipo, ?string $anterior): string
    {
        $ruta = $archivo->storeAs(
            self::CARPETA.'/'.$vendor->uuid,
            $tipo.'-'.Str::lower(Str::random(10)).'.'.$archivo->extension(),
            'public',
        );

        $this->borrarPropia($vendor, $anterior);

        return $ruta;
    }

    /**
     * Borra solo archivos subidos desde esta pantalla (nunca documentos de la solicitud).
     */
    private function borrarPropia(Vendor $vendor, ?string $ruta): void
    {
        if ($ruta && str_starts_with($ruta, self::CARPETA.'/'.$vendor->uuid.'/')) {
            Storage::disk('public')->delete($ruta);
        }
    }

    private function url(?string $ruta): ?string
    {
        if (! $ruta) {
            return null;
        }

        if (Str::startsWith($ruta, ['http://', 'https://'])) {
            return $ruta;
        }

        $ruta = Str::after(ltrim(Str::after($ruta, 'storage/'), '/'), 'public/');

        return Storage::disk('public')->exists($ruta) ? '/storage/'.$ruta : null;
    }

    private function vendor(Request $request): Vendor
    {
        $vendor = $request->user()->vendor;
        abort_if($vendor === null, 403);

        return $vendor;
    }
}
