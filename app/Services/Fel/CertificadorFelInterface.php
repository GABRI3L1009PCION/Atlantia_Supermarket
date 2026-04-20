<?php

namespace App\Services\Fel;

use App\Models\Dte\DteAnulacion;
use App\Models\Dte\DteFactura;

/**
 * Contrato comun para certificadores FEL.
 */
interface CertificadorFelInterface
{
    /**
     * Certifica una factura electronica ante el certificador FEL.
     *
     * @param DteFactura $dte
     * @return array<string, mixed>
     */
    public function certificar(DteFactura $dte): array;

    /**
     * Solicita anulacion fiscal de una factura certificada.
     *
     * @param DteAnulacion $anulacion
     * @return array<string, mixed>
     */
    public function anular(DteAnulacion $anulacion): array;

    /**
     * Consulta el estado de un DTE en el certificador.
     *
     * @param string $uuidSat
     * @return array<string, mixed>
     */
    public function consultar(string $uuidSat): array;
}
