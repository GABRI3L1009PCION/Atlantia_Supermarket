<?php

namespace App\Jobs;

use App\Models\Dte\DteFactura;
use App\Models\SentEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
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
    public function handle(): void
    {
        $dte = DteFactura::query()->with(['pedido.cliente', 'vendor'])->findOrFail($this->dteId);
        $cliente = $dte->pedido?->cliente;

        if (! $cliente) {
            return;
        }

        try {
            Mail::raw($this->body($dte), function ($message) use ($cliente, $dte): void {
                $message->to($cliente->email, $cliente->name)
                    ->subject('Factura FEL Atlantia ' . $dte->numero_dte);
            });

            $this->registrar($cliente->email, $dte, 'sent');
        } catch (Throwable $exception) {
            $this->registrar($cliente->email, $dte, 'failed', $exception->getMessage());

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
        return "Tu factura FEL {$dte->numero_dte} fue emitida por {$dte->vendor?->business_name}. "
            . "UUID SAT: {$dte->uuid_sat}. Total: Q {$dte->monto_total}.";
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
            'to' => $email,
            'subject' => 'Factura FEL Atlantia ' . $dte->numero_dte,
            'template' => 'emails.dte.factura',
            'status' => $status,
            'error' => $error,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);
    }
}
