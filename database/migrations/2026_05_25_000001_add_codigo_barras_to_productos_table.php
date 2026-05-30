<?php

use App\Models\Producto;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->string('codigo_barras', 32)->nullable()->unique()->after('sku');
        });

        Producto::withTrashed()
            ->whereNull('codigo_barras')
            ->select(['id'])
            ->chunkById(100, function ($productos): void {
                foreach ($productos as $producto) {
                    $producto->forceFill([
                        'codigo_barras' => $this->generateBarcode(),
                    ])->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->dropUnique(['codigo_barras']);
            $table->dropColumn('codigo_barras');
        });
    }

    private function generateBarcode(): string
    {
        do {
            $body = '740' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
            $barcode = $body . $this->ean13CheckDigit($body);
        } while (Producto::withTrashed()->where('codigo_barras', $barcode)->exists());

        return $barcode;
    }

    private function ean13CheckDigit(string $body): int
    {
        $sum = 0;

        foreach (str_split($body) as $index => $digit) {
            $sum += ((int) $digit) * ($index % 2 === 0 ? 1 : 3);
        }

        return (10 - ($sum % 10)) % 10;
    }
};
