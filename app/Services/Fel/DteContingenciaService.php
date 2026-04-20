<?php

namespace App\Services\Fel;

use App\Models\Dte\DteFactura;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Servicio para gestionar DTE pendientes por contingencia.
 */
class DteContingenciaService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(private readonly InfileCertificadorService $certificadorFel)
    {
    }

    /**
     * Lista DTE pendientes o rechazados para reproceso operativo.
     *
     * @return LengthAwarePaginator
     */
    public function pendientes(): LengthAwarePaginator
    {
        return DteFactura::query()
            ->with(['vendor', 'pedido'])
            ->whereIn('estado', ['borrador', 'rechazado'])
            ->latest()
            ->paginate(50);
    }

    /**
     * Reintenta certificacion de un DTE en contingencia.
     *
     * @param DteFactura $dte
     * @return DteFactura
     */
    public function reintentar(DteFactura $dte): DteFactura
    {
        $respuesta = $this->certificadorFel->certificar($dte->fresh(['vendor.fiscalProfile']));

        $dte->update([
            'uuid_sat' => $respuesta['uuid_sat'] ?? $dte->uuid_sat,
            'serie' => $respuesta['serie'] ?? $dte->serie,
            'numero' => $respuesta['numero'] ?? $dte->numero,
            'pdf_path' => $respuesta['pdf_path'] ?? $dte->pdf_path,
            'estado' => $respuesta['estado'] === 'certificado' ? 'certificado' : 'rechazado',
            'fecha_certificacion' => $respuesta['fecha_certificacion'] ?? $dte->fecha_certificacion,
            'certificador_respuesta' => $respuesta,
        ]);

        return $dte->refresh();
    }
}
