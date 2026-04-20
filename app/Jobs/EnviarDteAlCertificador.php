<?php

namespace App\Jobs;

use App\Events\DteEmitido;
use App\Events\DteRechazado;
use App\Models\Dte\DteFactura;
use App\Services\Fel\InfileCertificadorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Envia un DTE al certificador FEL configurado.
 */
class EnviarDteAlCertificador implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Cola donde se procesa FEL.
     */
    public string $queue = 'fel';

    /**
     * Numero maximo de intentos.
     */
    public int $tries = 3;

    /**
     * Segundos de espera entre reintentos.
     *
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 900];

    /**
     * Crea el job.
     *
     * @param int $dteId
     */
    public function __construct(private readonly int $dteId)
    {
    }

    /**
     * Ejecuta el envio al certificador.
     *
     * @param InfileCertificadorService $certificador
     * @return void
     */
    public function handle(InfileCertificadorService $certificador): void
    {
        $dte = DteFactura::query()->findOrFail($this->dteId);

        try {
            $respuesta = $certificador->certificar($dte);
            $estado = $respuesta['estado'] ?? 'certificado';

            $dte->update([
                'estado' => $estado === 'certificado' ? 'certificado' : 'rechazado',
                'uuid_sat' => $respuesta['uuid_sat'] ?? $dte->uuid_sat,
                'fecha_certificacion' => $estado === 'certificado' ? now() : $dte->fecha_certificacion,
                'certificador_respuesta' => $respuesta,
            ]);

            $dte->estado === 'certificado'
                ? DteEmitido::dispatch($dte->refresh())
                : DteRechazado::dispatch($dte->refresh(), $respuesta['mensaje'] ?? null);
        } catch (Throwable $exception) {
            $dte->update([
                'estado' => 'rechazado',
                'certificador_respuesta' => ['error' => $exception->getMessage()],
            ]);

            DteRechazado::dispatch($dte->refresh(), $exception->getMessage());

            throw $exception;
        }
    }
}
