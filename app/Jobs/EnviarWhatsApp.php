<?php

namespace App\Jobs;

use App\Services\Notificaciones\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Envia un mensaje de WhatsApp fuera de la peticion web.
 */
class EnviarWhatsApp implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @param  array<int, string>  $parametros
     */
    public function __construct(
        public readonly string $telefono,
        public readonly string $plantilla,
        public readonly array $parametros,
        public readonly string $texto,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(WhatsAppService $whatsApp): void
    {
        $whatsApp->enviar($this->telefono, $this->plantilla, $this->parametros, $this->texto);
    }
}
