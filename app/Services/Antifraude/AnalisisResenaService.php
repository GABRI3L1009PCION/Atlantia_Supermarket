<?php

namespace App\Services\Antifraude;

use App\Models\Ml\ReviewFlag;
use App\Models\Resena;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de analisis y moderacion antifraude de resenas.
 */
class AnalisisResenaService
{
    /**
     * Lista resenas pendientes o marcadas por ML.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function pending(array $filters = []): LengthAwarePaginator
    {
        return Resena::query()
            ->with(['producto', 'cliente', 'reviewFlags'])
            ->where(function ($query): void {
                $query->where('aprobada', false)->orWhere('flagged_ml', true);
            })
            ->when($filters['flagged_ml'] ?? null, fn ($query, $flagged) => $query->where('flagged_ml', (bool) $flagged))
            ->latest()
            ->paginate(50);
    }

    /**
     * Analiza una resena y registra flag si corresponde.
     *
     * @param Resena $resena
     * @return ReviewFlag|null
     */
    public function analizar(Resena $resena): ?ReviewFlag
    {
        $resultado = $this->calcularSospecha($resena);

        if ($resultado['score_sospecha'] < 0.7) {
            return null;
        }

        $resena->update(['flagged_ml' => true]);

        return ReviewFlag::query()->create([
            'resena_id' => $resena->id,
            'razon_ml' => $resultado['razon_ml'],
            'score_sospecha' => $resultado['score_sospecha'],
            'revisada' => false,
            'accion_tomada' => 'ninguna',
            'modelo_version_id' => null,
        ]);
    }

    /**
     * Modera una resena marcada o pendiente.
     *
     * @param Resena $resena
     * @param array<string, mixed> $data
     * @param User $user
     * @return Resena
     */
    public function moderate(Resena $resena, array $data, User $user): Resena
    {
        return DB::transaction(function () use ($resena, $data, $user): Resena {
            $accion = $data['accion'] ?? 'aprobar';
            $aprobada = $accion === 'aprobar';

            $resena->update([
                'aprobada' => $aprobada,
                'flagged_ml' => $accion === 'mantener_flag' || ($accion === 'ocultar'),
                'moderada_por' => $user->id,
                'moderada_at' => now(),
            ]);

            ReviewFlag::query()
                ->where('resena_id', $resena->id)
                ->where('revisada', false)
                ->update([
                    'revisada' => true,
                    'accion_tomada' => $this->accionTomada($accion),
                    'revisada_por' => $user->id,
                    'revisada_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($accion === 'eliminar') {
                $resena->delete();
            }

            return $resena->refresh();
        });
    }

    /**
     * Calcula sospecha NLP por reglas deterministicas.
     *
     * @param Resena $resena
     * @return array<string, mixed>
     */
    private function calcularSospecha(Resena $resena): array
    {
        $contenido = mb_strtolower(trim(($resena->titulo ?? '') . ' ' . ($resena->contenido ?? '')));
        $score = 0.0;
        $razon = 'patron_textual';

        if ($contenido === '' && $resena->calificacion === 5) {
            $score += 0.35;
            $razon = 'calificacion_sin_contenido';
        }

        if (preg_match('/(gratis|promo externa|whatsapp|telegram|link|http)/i', $contenido) === 1) {
            $score += 0.45;
            $razon = 'spam_promocional';
        }

        if ($this->textoRepetitivo($contenido)) {
            $score += 0.3;
            $razon = 'texto_repetitivo';
        }

        if (mb_strlen($contenido) < 12 && in_array((int) $resena->calificacion, [1, 5], true)) {
            $score += 0.2;
            $razon = 'resena_extrema_muy_corta';
        }

        return [
            'razon_ml' => $razon,
            'score_sospecha' => min(1.0, round($score, 6)),
            'longitud_texto' => mb_strlen($contenido),
            'calificacion' => $resena->calificacion,
        ];
    }

    /**
     * Detecta texto con demasiada repeticion de palabras.
     *
     * @param string $contenido
     * @return bool
     */
    private function textoRepetitivo(string $contenido): bool
    {
        $palabras = array_filter(preg_split('/\s+/', $contenido) ?: []);

        if (count($palabras) < 6) {
            return false;
        }

        $frecuencias = array_count_values($palabras);
        $maxima = max($frecuencias);

        return ($maxima / count($palabras)) >= 0.45;
    }

    /**
     * Mapea accion del formulario a enum de review_flags.
     *
     * @param string $accion
     * @return string
     */
    private function accionTomada(string $accion): string
    {
        return match ($accion) {
            'aprobar' => 'aprobada',
            'ocultar', 'mantener_flag' => 'ocultada',
            'eliminar' => 'eliminada',
            default => 'ninguna',
        };
    }
}
