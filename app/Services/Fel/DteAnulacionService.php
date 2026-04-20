<?php

namespace App\Services\Fel;

use App\Exceptions\DteCertificadorException;
use App\Exceptions\TransaccionFallidaException;
use App\Models\Dte\DteAnulacion;
use App\Models\Dte\DteFactura;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Servicio de anulacion fiscal de DTE.
 */
class DteAnulacionService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(private readonly InfileCertificadorService $certificadorFel)
    {
    }

    /**
     * Solicita anulacion de un DTE certificado.
     *
     * @param DteFactura $dte
     * @param array<string, mixed> $data
     * @param User $user
     * @return DteAnulacion
     *
     * @throws DteCertificadorException
     * @throws TransaccionFallidaException
     */
    public function anular(DteFactura $dte, array $data, User $user): DteAnulacion
    {
        try {
            return DB::transaction(function () use ($dte, $data, $user): DteAnulacion {
                $dte->loadMissing('anulacion', 'vendor.fiscalProfile');

                if ($dte->estado !== 'certificado') {
                    throw new DteCertificadorException('Solo se pueden anular DTE certificados.');
                }

                if ($dte->anulacion !== null) {
                    throw new DteCertificadorException('El DTE ya tiene una anulacion registrada.');
                }

                $anulacion = DteAnulacion::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'dte_id' => $dte->id,
                    'motivo' => $data['motivo'],
                    'fecha_anulacion' => now(),
                    'usuario_id' => $user->id,
                    'estado' => 'solicitada',
                ]);

                $respuesta = $this->certificadorFel->anular($anulacion->fresh(['dteFactura.vendor.fiscalProfile']));
                $anulacion->update([
                    'uuid_anulacion_sat' => $respuesta['uuid_anulacion_sat'] ?? null,
                    'estado' => $respuesta['estado'] === 'aceptada' ? 'aceptada' : 'rechazada',
                    'certificador_respuesta' => $respuesta,
                ]);

                if ($anulacion->estado === 'aceptada') {
                    $dte->update(['estado' => 'anulado']);
                }

                return $anulacion->refresh();
            });
        } catch (DteCertificadorException|TransaccionFallidaException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new TransaccionFallidaException('No fue posible anular el DTE.', previous: $exception);
        }
    }
}
