<?php

namespace App\Services\Notificaciones;

use App\Contracts\NotificacionContract;
use App\Models\Pedido;
use App\Models\SentEmail;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Servicio de notificaciones relacionadas con pedidos.
 */
class NotificadorPedidoService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(private readonly NotificacionContract $notificationService)
    {
    }

    /**
     * Notifica confirmacion de pedido al cliente y vendedor.
     *
     * @param Pedido $pedido
     * @return void
     */
    public function pedidoConfirmado(Pedido $pedido): void
    {
        $pedido->loadMissing(['cliente', 'vendor.user']);

        $this->notificarUsuario($pedido->cliente, 'pedido.confirmado', [
            'titulo' => 'Pedido confirmado',
            'mensaje' => "Tu pedido {$pedido->numero_pedido} fue confirmado.",
            'pedido_uuid' => $pedido->uuid,
            'estado' => $pedido->estadoValor(),
        ]);

        if ($pedido->vendor?->user !== null) {
            $this->notificarUsuario($pedido->vendor->user, 'pedido.recibido', [
                'titulo' => 'Nuevo pedido recibido',
                'mensaje' => "Recibiste el pedido {$pedido->numero_pedido}.",
                'pedido_uuid' => $pedido->uuid,
                'vendor_id' => $pedido->vendor_id,
            ]);
        }
    }

    /**
     * Notifica cambio de estado de un pedido.
     *
     * @param Pedido $pedido
     * @param string $estadoAnterior
     * @param string $estadoNuevo
     * @return void
     */
    public function estadoActualizado(Pedido $pedido, string $estadoAnterior, string $estadoNuevo): void
    {
        $pedido->loadMissing(['cliente', 'vendor.user']);

        $payload = [
            'titulo' => 'Estado de pedido actualizado',
            'mensaje' => "El pedido {$pedido->numero_pedido} cambio de {$estadoAnterior} a {$estadoNuevo}.",
            'pedido_uuid' => $pedido->uuid,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
        ];

        $this->notificarUsuario($pedido->cliente, 'pedido.estado_actualizado', $payload);

        if ($pedido->vendor?->user !== null) {
            $this->notificarUsuario($pedido->vendor->user, 'pedido.estado_actualizado', $payload);
        }
    }

    /**
     * Notifica asignacion de entrega al repartidor.
     *
     * @param Pedido $pedido
     * @param User $repartidor
     * @return void
     */
    public function rutaAsignada(Pedido $pedido, User $repartidor): void
    {
        $this->notificarUsuario($repartidor, 'pedido.ruta_asignada', [
            'titulo' => 'Ruta asignada',
            'mensaje' => "Se te asigno la entrega del pedido {$pedido->numero_pedido}.",
            'pedido_uuid' => $pedido->uuid,
            'numero_pedido' => $pedido->numero_pedido,
        ]);
    }

    /**
     * Notifica al repartidor que el pedido ya puede recogerse.
     *
     * @param Pedido $pedido
     * @return void
     */
    public function pedidoListoParaRecoger(Pedido $pedido): void
    {
        $pedido->loadMissing('deliveryRoute.repartidor');

        if ($pedido->deliveryRoute?->repartidor === null) {
            return;
        }

        $this->notificarUsuario($pedido->deliveryRoute->repartidor, 'pedido.listo_para_recoger', [
            'titulo' => 'Pedido listo para recoger',
            'mensaje' => "El pedido {$pedido->numero_pedido} ya esta listo para que pases a recogerlo.",
            'pedido_uuid' => $pedido->uuid,
            'numero_pedido' => $pedido->numero_pedido,
        ]);
    }

    /**
     * Crea notificacion interna y auditoria de email encolado.
     *
     * @param User $user
     * @param string $type
     * @param array<string, mixed> $data
     * @return void
     */
    private function notificarUsuario(User $user, string $type, array $data): void
    {
        $this->notificationService->enviar($user, $type, $data);
        $this->registrarEmail($user, $data['titulo'], $type, $data);
    }

    /**
     * Registra email pendiente para auditoria y jobs futuros.
     *
     * @param User $user
     * @param string $subject
     * @param string $template
     * @param array<string, mixed> $metadata
     * @return SentEmail
     */
    private function registrarEmail(User $user, string $subject, string $template, array $metadata): SentEmail
    {
        return SentEmail::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'to' => $user->email,
            'subject' => $subject,
            'template' => $template,
            'status' => 'queued',
            'metadata' => $metadata,
        ]);
    }
}
