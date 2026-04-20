<?php

namespace App\Services\Ml;

/**
 * Servicio de webhooks entrantes del microservicio ML.
 */
class MlWebhookService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(private readonly MonitorDriftService $monitorDriftService)
    {
    }

    /**
     * Procesa webhook ML.
     *
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $headers
     * @return void
     */
    public function handle(array $payload, array $headers = []): void
    {
        $this->monitorDriftService->handleWebhook($payload + ['headers' => $headers]);
    }
}
