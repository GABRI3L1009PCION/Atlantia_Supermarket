<?php

namespace App\Jobs;

use App\Models\PushSuscripcion;
use App\Services\Notificaciones\PushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Envia una notificacion push a los dispositivos de un usuario.
 */
class EnviarPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(
        public readonly int $userId,
        public readonly string $titulo,
        public readonly string $cuerpo,
        public readonly string $url,
        public readonly ?string $etiqueta = null,
    ) {}

    public function handle(PushService $push): void
    {
        $push->enviar(
            PushSuscripcion::query()->where('user_id', $this->userId)->get(),
            $this->titulo,
            $this->cuerpo,
            $this->url,
            $this->etiqueta
        );
    }
}
