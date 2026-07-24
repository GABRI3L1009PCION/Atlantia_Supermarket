<?php

namespace App\Http\Controllers;

use App\Models\Dte\DteFactura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sirve PDFs fiscales DTE solo despues de validar ownership o sesion invitada.
 */
class DtePdfController extends Controller
{
    /**
     * Descarga o muestra el PDF fiscal de un DTE autorizado.
     */
    public function __invoke(Request $request, DteFactura $dte): StreamedResponse
    {
        $dte->loadMissing('pedido.pedidoPadre');

        if ($request->user() !== null) {
            $this->authorize('downloadPdf', $dte);
        } else {
            abort_unless($this->canGuestView($request, $dte), 403);
        }

        $path = (string) $dte->pdf_path;
        abort_if($path === '', 404);

        $disk = $this->diskFor($path);
        abort_unless(Storage::disk($disk)->exists($path), 404);

        $filename = 'factura-atlantia-'.$dte->numero_dte.'.pdf';

        if ($request->boolean('download')) {
            return Storage::disk($disk)->download($path, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        return Storage::disk($disk)->response($path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Permite al comprador invitado ver PDFs de pedidos creados en su sesion.
     */
    private function canGuestView(Request $request, DteFactura $dte): bool
    {
        $pedido = $dte->pedido;

        if ($pedido === null) {
            return false;
        }

        $guestOrderUuids = $request->session()->get('guest_order_uuids', []);

        if (! is_array($guestOrderUuids)) {
            return false;
        }

        return in_array($pedido->uuid, $guestOrderUuids, true)
            || ($pedido->pedidoPadre !== null && in_array($pedido->pedidoPadre->uuid, $guestOrderUuids, true));
    }

    /**
     * Usa el disco privado nuevo y conserva compatibilidad de lectura con PDFs legados publicos.
     */
    private function diskFor(string $path): string
    {
        $privateDisk = (string) config('filesystems.private_disk', 'local');

        return Storage::disk($privateDisk)->exists($path) ? $privateDisk : 'public';
    }
}
