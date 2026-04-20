<?php

namespace App\Jobs;

use App\Models\Dte\DteFactura;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

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
    public function handle(): void
    {
        $dte = DteFactura::query()->with(['vendor', 'pedido', 'items.producto'])->findOrFail($this->dteId);

        if (! app()->bound('dompdf.wrapper')) {
            throw new RuntimeException('El generador PDF DomPDF no esta configurado.');
        }

        $html = view('pdf.dte.factura', ['dte' => $dte])->render();
        $pdf = app('dompdf.wrapper')->loadHTML($html);
        $path = 'dte/pdf/' . $dte->uuid . '.pdf';

        Storage::disk(config('filesystems.default'))->put($path, $pdf->output());
        $dte->update(['pdf_path' => $path]);
    }
}
