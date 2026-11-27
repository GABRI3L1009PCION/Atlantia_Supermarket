<?php

namespace Tests\Unit\Catalogo;

use App\Services\Catalogo\LimpiadorImagenCategoria;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LimpiadorImagenCategoriaTest extends TestCase
{
    public function test_light_background_becomes_transparent_and_artwork_stays(): void
    {
        Storage::fake('public');

        $imagen = imagecreatetruecolor(200, 200);
        imagefill($imagen, 0, 0, imagecolorallocate($imagen, 236, 236, 236));
        imagefilledrectangle($imagen, 60, 60, 140, 140, imagecolorallocate($imagen, 139, 21, 56));
        ob_start();
        imagepng($imagen);
        Storage::disk('public')->put('categorias/original.png', (string) ob_get_clean());

        $ruta = app(LimpiadorImagenCategoria::class)->procesar('categorias/original.png', true);

        $this->assertStringEndsWith('.webp', $ruta);
        Storage::disk('public')->assertMissing('categorias/original.png');

        $limpia = imagecreatefromstring(Storage::disk('public')->get($ruta));
        $alfa = fn (int $x, int $y): int => (imagecolorat($limpia, $x, $y) >> 24) & 0x7F;

        $this->assertSame(127, $alfa(5, 5), 'La esquina gris debe quedar transparente.');
        $this->assertSame(0, $alfa(100, 100), 'El dibujo guinda debe quedar opaco.');
    }
}
