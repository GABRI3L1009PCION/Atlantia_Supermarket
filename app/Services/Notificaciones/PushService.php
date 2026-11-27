<?php

namespace App\Services\Notificaciones;

use App\Models\PushSuscripcion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

/**
 * Notificaciones push gratuitas (Web Push con claves VAPID).
 * Funcionan en Android desde el navegador y en iPhone con la tienda instalada (iOS 16.4+).
 */
class PushService
{
    public static function habilitado(): bool
    {
        return filled(config('services.webpush.public_key')) && filled(config('services.webpush.private_key'));
    }

    public static function clavePublica(): ?string
    {
        return config('services.webpush.public_key');
    }

    /**
     * Envia un aviso a varios dispositivos. Devuelve cuantos lo recibieron.
     *
     * @param  Collection<int, PushSuscripcion>  $suscripciones
     */
    public function enviar(Collection $suscripciones, string $titulo, string $cuerpo, string $url, ?string $etiqueta = null): int
    {
        if (! self::habilitado() || $suscripciones->isEmpty()) {
            return 0;
        }

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => (string) config('services.webpush.subject'),
                    'publicKey' => (string) config('services.webpush.public_key'),
                    'privateKey' => (string) config('services.webpush.private_key'),
                ],
            ], ['TTL' => 3600 * 12]);
        } catch (Throwable $exception) {
            Log::warning('Web Push mal configurado.', ['error' => $exception->getMessage()]);

            return 0;
        }

        $carga = json_encode([
            'title' => $titulo,
            'body' => $cuerpo,
            'url' => $url,
            'tag' => $etiqueta,
            'icon' => asset('icon-192.png'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $porEndpoint = [];
        foreach ($suscripciones as $suscripcion) {
            $porEndpoint[$suscripcion->endpoint] = $suscripcion;
            $webPush->queueNotification(Subscription::create([
                'endpoint' => $suscripcion->endpoint,
                'publicKey' => $suscripcion->p256dh,
                'authToken' => $suscripcion->auth,
            ]), (string) $carga);
        }

        $entregadas = 0;
        foreach ($webPush->flush() as $reporte) {
            $suscripcion = $porEndpoint[$reporte->getEndpoint()] ?? null;

            if ($reporte->isSuccess()) {
                $entregadas++;
                $suscripcion?->forceFill(['ultimo_envio_at' => now()])->saveQuietly();
            } elseif ($reporte->isSubscriptionExpired()) {
                /* El cliente desinstalo o bloqueo las notificaciones: se borra el dispositivo. */
                $suscripcion?->delete();
            }
        }

        return $entregadas;
    }
}
