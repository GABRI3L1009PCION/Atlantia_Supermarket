<?php

namespace App\Services\Ml;

use App\Models\Ml\ReviewFlag;
use App\Models\Resena;
use App\Services\Antifraude\AnalisisResenaService;

/**
 * Servicio ML para deteccion de resenas falsas o spam.
 */
class DetectorResenaFalsaService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(
        private readonly MlServiceClient $mlClient,
        private readonly AnalisisResenaService $analisisResenaService
    ) {
    }

    /**
     * Evalua una resena con ML y fallback local.
     *
     * @param Resena $resena
     * @return ReviewFlag|null
     */
    public function evaluar(Resena $resena): ?ReviewFlag
    {
        try {
            $resultado = $this->mlClient->post('/nlp/review-flag', [
                'resena_id' => $resena->id,
                'calificacion' => $resena->calificacion,
                'titulo' => $resena->titulo,
                'contenido' => $resena->contenido,
            ]);

            if ((float) ($resultado['score_sospecha'] ?? 0) < 0.7) {
                return null;
            }

            $resena->update(['flagged_ml' => true]);

            return ReviewFlag::query()->create([
                'resena_id' => $resena->id,
                'razon_ml' => $resultado['razon_ml'] ?? 'ml_review_flag',
                'score_sospecha' => $resultado['score_sospecha'],
                'modelo_version_id' => $resultado['modelo_version_id'] ?? null,
            ]);
        } catch (\Throwable) {
            return $this->analisisResenaService->analizar($resena);
        }
    }
}
