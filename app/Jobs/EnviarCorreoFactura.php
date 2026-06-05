<?php

namespace App\Jobs;

use App\Models\Dte\DteFactura;
use App\Models\SentEmail;
use App\Services\Fel\DteComprobantePdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Envia correo con datos fiscales de la factura emitida.
 */
class EnviarCorreoFactura implements ShouldQueue
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
     * Envia el correo fiscal y registra auditoria de email.
     *
     * @return void
     */
    public function handle(DteComprobantePdf $pdf): void
    {
        $dte = DteFactura::query()->with(['pedido.cliente', 'pedido.direccion', 'vendor.fiscalProfile', 'items.producto'])->findOrFail($this->dteId);
        $cliente = $dte->pedido?->cliente;
        $recipientEmail = $dte->pedido?->facturacion_email ?: $cliente?->email;
        $recipientName = $dte->pedido?->facturacion_nombre ?: $cliente?->name;

        if (! $recipientEmail) {
            return;
        }

        try {
            if ($dte->pdf_path === null || ! Storage::disk('public')->exists($dte->pdf_path)) {
                $pdf->store($dte);
                $dte->refresh();
            }

            Mail::raw($this->body($dte), function ($message) use ($recipientEmail, $recipientName, $dte): void {
                $message->to($recipientEmail, $recipientName)
                    ->subject('Factura Atlantia ' . $dte->numero_dte);

                if ($dte->pdf_path !== null) {
                    $message->attachFromStorageDisk(
                        'public',
                        $dte->pdf_path,
                        'factura-atlantia-' . $dte->numero_dte . '.pdf',
                        ['mime' => 'application/pdf']
                    );
                }
            });

            $this->registrar($recipientEmail, $dte, 'sent');
        } catch (Throwable $exception) {
            $this->registrar($recipientEmail, $dte, 'failed', $exception->getMessage());

            throw $exception;
        }
    }

    /**
     * Construye cuerpo de correo sin adjuntar datos sensibles.
     *
     * @param DteFactura $dte
     * @return string
     */
    private function body(DteFactura $dte): string
    {
        $mock = data_get($dte->certificador_respuesta, 'respuesta_original.mock', data_get($dte->certificador_respuesta, 'mock', false));
        $tipo = $mock ? 'factura electronica FEL emulada' : 'factura FEL';

        return "Hola, adjuntamos tu {$tipo} {$dte->numero_dte} emitido por {$dte->vendor?->business_name}. "
            . "Total: Q {$dte->monto_total}. "
            . ($mock ? 'Este documento es de prueba y no sustituye una certificacion SAT real.' : "UUID SAT: {$dte->uuid_sat}.");
    }

    /**
     * Registra resultado del envio.
     *
     * @param string $email
     * @param DteFactura $dte
     * @param string $status
     * @param string|null $error
     * @return void
     */
    private function registrar(string $email, DteFactura $dte, string $status, ?string $error = null): void
    {
        SentEmail::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $dte->pedido?->cliente_id,
            'to' => $email,
            'subject' => 'Factura Atlantia ' . $dte->numero_dte,
            'template' => 'emails.dte.factura',
            'status' => $status,
            'error' => $error,
            'metadata' => [
                'dte_id' => $dte->id,
                'numero_dte' => $dte->numero_dte,
                'pdf_path' => $dte->pdf_path,
            ],
            'sent_at' => $status === 'sent' ? now() : null,
        ]);
    }
}
