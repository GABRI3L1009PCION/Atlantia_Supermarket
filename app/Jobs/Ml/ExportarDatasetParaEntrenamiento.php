<?php

namespace App\Jobs\Ml;

use App\Services\Ml\ExportadorDatasetService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Exporta dataset de entrenamiento para el microservicio ML.
 */
class ExportarDatasetParaEntrenamiento implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;


    /**
     * Crea el job.
     *
     * @param string $tipo
     * @param string $disk
     */
    public function __construct(private readonly string $tipo = 'ventas', private readonly string $disk = 'local')
    {
    }

    /**
     * Exporta datos a JSONL.
     *
     * @param ExportadorDatasetService $exportadorDatasetService
     * @return void
     */
    public function handle(ExportadorDatasetService $exportadorDatasetService): void
    {
        $dataset = $this->tipo === 'catalogo'
            ? $exportadorDatasetService->catalogo()
            : $exportadorDatasetService->ventas();

        $content = $dataset
            ->map(fn (mixed $row) => json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->implode(PHP_EOL);

        $path = 'ml/datasets/' . $this->tipo . '-' . now()->format('YmdHis') . '.jsonl';

        Storage::disk($this->disk)->put($path, $content);
    }
}
