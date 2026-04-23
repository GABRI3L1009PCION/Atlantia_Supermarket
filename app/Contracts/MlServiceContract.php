<?php

namespace App\Contracts;

/**
 * Contrato tipado de acceso a capacidades ML.
 */
interface MlServiceContract
{
    /**
     * Detecta fraude de pedidos.
     *
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    public function detectarFraude(array $datos): array;

    /**
     * Genera prediccion de demanda.
     *
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    public function predecirDemanda(array $datos): array;

    /**
     * Genera recomendaciones de productos.
     *
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    public function recomendar(array $datos): array;
}
