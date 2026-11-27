<?php

namespace App\Services\Notificaciones;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Envia mensajes de WhatsApp al cliente.
 *
 * Drivers:
 *  - "log": solo escribe el mensaje en el log (desarrollo y pruebas).
 *  - "meta": WhatsApp Cloud API de Meta con plantillas aprobadas.
 *  - "null": desactivado.
 */
class WhatsAppService
{
    /**
     * Indica si hay un canal de WhatsApp activo.
     */
    public function habilitado(): bool
    {
        return in_array($this->driver(), ['log', 'meta'], true);
    }

    /**
     * Convierte un telefono de Guatemala al formato internacional 502XXXXXXXX.
     */
    public static function normalizarTelefono(?string $telefono): ?string
    {
        $digitos = preg_replace('/\D+/', '', (string) $telefono) ?? '';

        if (strlen($digitos) === 11 && str_starts_with($digitos, '502')) {
            $digitos = substr($digitos, 3);
        }

        return preg_match('/^[2-7]\d{7}$/', $digitos) === 1 ? '502'.$digitos : null;
    }

    /**
     * Envia un mensaje. Devuelve true si el proveedor lo acepto.
     *
     * @param  array<int, string>  $parametros  Valores en orden para la plantilla.
     */
    public function enviar(string $telefono, string $plantilla, array $parametros, string $texto): bool
    {
        $numero = self::normalizarTelefono($telefono);

        if ($numero === null || ! $this->habilitado()) {
            return false;
        }

        if ($this->driver() === 'log') {
            Log::info('WhatsApp (simulado)', [
                'para' => $numero,
                'plantilla' => $plantilla,
                'texto' => $texto,
            ]);

            return true;
        }

        return $this->enviarMeta($numero, $plantilla, $parametros);
    }

    /**
     * Envia una plantilla por la WhatsApp Cloud API.
     *
     * @param  array<int, string>  $parametros
     */
    private function enviarMeta(string $numero, string $plantilla, array $parametros): bool
    {
        $token = (string) config('services.whatsapp.token');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id');
        $nombrePlantilla = (string) (config("services.whatsapp.plantillas.{$plantilla}") ?: $plantilla);

        if ($token === '' || $phoneNumberId === '') {
            Log::warning('WhatsApp sin credenciales: no se envio el mensaje.', ['plantilla' => $plantilla]);

            return false;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(15)
                ->post(sprintf(
                    'https://graph.facebook.com/%s/%s/messages',
                    config('services.whatsapp.api_version', 'v21.0'),
                    $phoneNumberId
                ), [
                    'messaging_product' => 'whatsapp',
                    'to' => $numero,
                    'type' => 'template',
                    'template' => [
                        'name' => $nombrePlantilla,
                        'language' => ['code' => config('services.whatsapp.idioma', 'es')],
                        'components' => $parametros === [] ? [] : [[
                            'type' => 'body',
                            'parameters' => array_map(
                                static fn (string $valor): array => ['type' => 'text', 'text' => $valor],
                                array_values($parametros)
                            ),
                        ]],
                    ],
                ]);
        } catch (Throwable $exception) {
            Log::warning('WhatsApp no disponible.', ['error' => $exception->getMessage()]);

            return false;
        }

        if ($response->failed()) {
            Log::warning('WhatsApp rechazo el mensaje.', ['status' => $response->status(), 'plantilla' => $nombrePlantilla]);

            return false;
        }

        return true;
    }

    private function driver(): string
    {
        return (string) config('services.whatsapp.driver', 'log');
    }
}
