<?php

namespace App\Jobs;

use App\Models\Dte\DteFactura;
use App\Services\Fel\DteComprobantePdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Genera PDF fiscal de un DTE certificado.
 */
class GenerarPdfDte implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;


    public int $tries = 3;

    /**
     * Crea el job.
     *
     * @param int $dteId
     */
    public function __construct(private readonly int $dteId)
    {
    }

    /**
     * Genera y almacena el PDF.
     *
     * @return void
     */
    public function handle(DteComprobantePdf $pdf): void
    {
        $dte = DteFactura::query()->with(['vendor.fiscalProfile', 'pedido.cliente', 'items.producto'])->findOrFail($this->dteId);

        $pdf->store($dte);
    }
}
