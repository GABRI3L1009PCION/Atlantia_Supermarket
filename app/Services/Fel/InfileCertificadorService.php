<?php

namespace App\Services\Fel;

use App\Exceptions\DteCertificadorException;
use App\Models\Dte\DteAnulacion;
use App\Models\Dte\DteFactura;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Integracion con certificador FEL INFILE.
 */
class InfileCertificadorService implements CertificadorFelInterface
{
    /**
     * Certifica una factura electronica ante INFILE o el mock local.
     *
     * @param DteFactura $dte
     * @return array<string, mixed>
     *
     * @throws DteCertificadorException
     */
    public function certificar(DteFactura $dte): array
    {
        if ($this->usarMock()) {
            return $this->mockCertificacion($dte);
        }

        $response = Http::timeout(20)
            ->withHeaders($this->headers($dte))
            ->post($this->baseUrl() . '/v1/dte/certificar', [
                'xml_dte' => $dte->xml_dte,
                'numero_dte' => $dte->numero_dte,
                'tipo_dte' => $dte->tipo_dte,
            ]);

        if (! $response->successful()) {
            throw new DteCertificadorException('INFILE rechazo la certificacion del DTE.');
        }

        return $this->normalizarCertificacion($response->json());
    }

    /**
     * Solicita anulacion fiscal ante INFILE o el mock local.
     *
     * @param DteAnulacion $anulacion
     * @return array<string, mixed>
     *
     * @throws DteCertificadorException
     */
    public function anular(DteAnulacion $anulacion): array
    {
        $anulacion->loadMissing('dteFactura.vendor.fiscalProfile');

        if ($this->usarMock()) {
            return $this->mockAnulacion($anulacion);
        }

        $response = Http::timeout(20)
            ->withHeaders($this->headers($anulacion->dteFactura))
            ->post($this->baseUrl() . '/v1/dte/anular', [
                'uuid_sat' => $anulacion->dteFactura->uuid_sat,
                'motivo' => $anulacion->motivo,
                'fecha_anulacion' => $anulacion->fecha_anulacion?->toIso8601String(),
            ]);

        if (! $response->successful()) {
            throw new DteCertificadorException('INFILE rechazo la anulacion del DTE.');
        }

        return $this->normalizarAnulacion($response->json());
    }

    /**
     * Consulta estado del DTE en INFILE o el mock local.
     *
     * @param string $uuidSat
     * @return array<string, mixed>
     */
    public function consultar(string $uuidSat): array
    {
        if ($this->usarMock()) {
            return [
                'estado' => 'certificado',
                'uuid_sat' => $uuidSat,
                'respuesta_original' => ['mock' => true],
            ];
        }

        $response = Http::timeout(15)->get($this->baseUrl() . '/v1/dte/' . $uuidSat);

        return [
            'estado' => $response->successful() ? 'certificado' : 'desconocido',
            'uuid_sat' => $uuidSat,
            'respuesta_original' => $response->json() ?? [],
        ];
    }

    /**
     * Procesa webhook recibido desde el certificador.
     *
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $headers
     * @return DteFactura|null
     */
    public function procesarWebhook(array $payload, array $headers = []): ?DteFactura
    {
        if (($payload['uuid_sat'] ?? null) === null && ($payload['numero_dte'] ?? null) === null) {
            return null;
        }

        $dte = DteFactura::query()
            ->when($payload['uuid_sat'] ?? null, fn ($query, $uuidSat) => $query->where('uuid_sat', $uuidSat))
            ->when($payload['numero_dte'] ?? null, fn ($query, $numeroDte) => $query->orWhere('numero_dte', $numeroDte))
            ->first();

        if ($dte === null) {
            return null;
        }

        $estado = $payload['estado'] ?? $dte->estado;
        $dte->update([
            'estado' => in_array($estado, ['certificado', 'rechazado', 'anulado'], true) ? $estado : $dte->estado,
            'certificador_respuesta' => array_merge($dte->certificador_respuesta ?? [], [
                'webhook' => $payload,
                'headers' => $headers,
            ]),
        ]);

        return $dte->refresh();
    }

    /**
     * Devuelve URL base de INFILE.
     *
     * @return string
     */
    private function baseUrl(): string
    {
        return rtrim((string) config('services.infile.base_url', 'https://sandbox.infile.com.gt/api'), '/');
    }

    /**
     * Indica si se usa mock inteligente local.
     *
     * @return bool
     */
    private function usarMock(): bool
    {
        return (bool) config('services.infile.mock', app()->environment(['local', 'testing']));
    }

    /**
     * Headers de autenticacion por vendedor.
     *
     * @param DteFactura $dte
     * @return array<string, string>
     */
    private function headers(DteFactura $dte): array
    {
        $profile = $dte->vendor->fiscalProfile;

        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-FEL-Usuario' => (string) $profile?->fel_usuario,
            'X-FEL-Llave' => (string) $profile?->fel_llave_certificador,
        ];
    }

    /**
     * Respuesta mock compatible con el contrato normalizado.
     *
     * @param DteFactura $dte
     * @return array<string, mixed>
     */
    private function mockCertificacion(DteFactura $dte): array
    {
        $seed = $dte->numero_dte . '|' . $dte->vendor_id . '|' . $dte->monto_total;

        return [
            'estado' => 'certificado',
            'uuid_sat' => (string) Str::uuid(),
            'serie' => 'ATL-' . now()->format('Ym'),
            'numero' => abs(crc32($seed)),
            'fecha_certificacion' => now(),
            'pdf_path' => 'fel/dtes/' . $dte->uuid . '.pdf',
            'respuesta_original' => [
                'mock' => true,
                'certificador' => 'infile',
                'hash' => hash('sha256', $seed),
            ],
        ];
    }

    /**
     * Respuesta mock de anulacion compatible con el contrato normalizado.
     *
     * @param DteAnulacion $anulacion
     * @return array<string, mixed>
     */
    private function mockAnulacion(DteAnulacion $anulacion): array
    {
        return [
            'estado' => 'aceptada',
            'uuid_anulacion_sat' => (string) Str::uuid(),
            'respuesta_original' => [
                'mock' => true,
                'certificador' => 'infile',
                'dte_uuid_sat' => $anulacion->dteFactura->uuid_sat,
            ],
        ];
    }

    /**
     * Normaliza respuesta real de certificacion.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizarCertificacion(array $payload): array
    {
        return [
            'estado' => $payload['estado'] ?? 'certificado',
            'uuid_sat' => $payload['uuid'] ?? $payload['uuid_sat'] ?? null,
            'serie' => $payload['serie'] ?? null,
            'numero' => $payload['numero'] ?? null,
            'fecha_certificacion' => now(),
            'pdf_path' => $payload['pdf_path'] ?? null,
            'respuesta_original' => $payload,
        ];
    }

    /**
     * Normaliza respuesta real de anulacion.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizarAnulacion(array $payload): array
    {
        return [
            'estado' => $payload['estado'] ?? 'aceptada',
            'uuid_anulacion_sat' => $payload['uuid_anulacion'] ?? $payload['uuid_anulacion_sat'] ?? null,
            'respuesta_original' => $payload,
        ];
    }
}
