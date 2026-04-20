<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Limpia archivos temporales antiguos del almacenamiento configurado.
 */
class LimpiarArchivosTemporales implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $queue = 'maintenance';

    /**
     * Crea el job.
     *
     * @param string $disk
     * @param string $path
     * @param int $olderThanHours
     */
    public function __construct(
        private readonly string $disk = 'local',
        private readonly string $path = 'tmp',
        private readonly int $olderThanHours = 24
    ) {
    }

    /**
     * Elimina archivos temporales antiguos.
     *
     * @return void
     */
    public function handle(): void
    {
        $storage = Storage::disk($this->disk);
        $limit = now()->subHours($this->olderThanHours)->getTimestamp();

        foreach ($storage->allFiles($this->path) as $file) {
            if ($storage->lastModified($file) < $limit) {
                $storage->delete($file);
            }
        }
    }
}
