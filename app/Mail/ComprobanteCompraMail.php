<?php

namespace App\Mail;

use App\Models\Pedido;
use App\Services\Pedidos\ComprobanteCompraPdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo al cliente con el comprobante de compra (PDF adjunto) de la parte de una tienda en su pedido.
 */
class ComprobanteCompraMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Pedido $parte) {}

    public function envelope(): Envelope
    {
        $padre = $this->parte->pedidoPadre ?? $this->parte;

        return new Envelope(
            subject: 'Tu comprobante de compra · Pedido #'.$padre->numero_pedido,
            replyTo: array_filter([config('atlantia.support.email')]),
        );
    }

    public function content(): Content
    {
        $padre = $this->parte->pedidoPadre ?? $this->parte;

        return new Content(view: 'emails.pedidos.comprobante', with: [
            'parte' => $this->parte,
            'padre' => $padre,
            'tienda' => $this->parte->vendor?->business_name ?? 'Atlantia Store',
            'cliente' => explode(' ', trim((string) ($this->parte->facturacion_nombre ?: $padre->facturacion_nombre ?: $this->parte->cliente?->name ?: 'cliente')))[0],
            'urlPedido' => $padre->exists ? route('cliente.pedidos.show', $padre) : route('home'),
        ]);
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $padre = $this->parte->pedidoPadre ?? $this->parte;

        return [
            Attachment::fromData(fn () => app(ComprobanteCompraPdf::class)->generar($this->parte), 'comprobante-'.$padre->numero_pedido.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
