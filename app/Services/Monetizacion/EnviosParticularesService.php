<?php

namespace App\Services\Monetizacion;

use App\Models\ExternalDeliveryOrder;
use App\Services\Repartidores\ExternalDeliveryOrderService;
use App\Support\CoberturaColonias;

/**
 * "Atlantia Envios": mensajeria para personas que quieren mandar algo (paquete, documentos, encargo).
 * Usa la misma red de repartidores que los pedidos de la tienda.
 */
class EnviosParticularesService
{
    public function __construct(private readonly ExternalDeliveryOrderService $entregas) {}

    /**
     * Precio del envio segun municipios y tamano.
     */
    public static function cotizar(string $municipioOrigen, string $municipioDestino, string $tamano): float
    {
        $tarifas = (array) config('monetizacion.envios');
        $base = $municipioOrigen === $municipioDestino
            ? (float) ($tarifas['mismo_municipio'][$municipioDestino] ?? $tarifas['mismo_municipio']['default'] ?? 25)
            : (float) ($tarifas['entre_municipios'] ?? 35);

        return round($base + (float) ($tarifas['tamanos'][$tamano]['recargo'] ?? 0), 2);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function solicitar(array $data): ExternalDeliveryOrder
    {
        $tarifa = self::cotizar($data['origen_municipio'], $data['destino_municipio'], $data['tamano']);
        $pagaDestinatario = $data['quien_paga'] === 'destinatario';
        $tamano = (string) (config("monetizacion.envios.tamanos.{$data['tamano']}.nombre") ?? $data['tamano']);

        $notasRecogida = trim(implode(' · ', array_filter([
            "Paquete: {$data['descripcion']} ({$tamano})",
            $pagaDestinatario ? null : 'Cobrar Q '.number_format($tarifa, 2).' del envío al remitente al recoger.',
            $data['notas_recogida'] ?? null,
        ])));

        return $this->entregas->create([
            'source_channel' => 'atlantia_envios',
            'external_reference' => 'ENV-'.now()->format('ymdHis').random_int(10, 99),
            'store_name' => $data['remitente_nombre'],
            'store_contact_name' => $data['remitente_nombre'],
            'store_phone' => $data['remitente_telefono'],
            'pickup_address' => $data['origen_direccion'].', '.$data['origen_municipio'],
            'pickup_latitude' => $data['origen_lat'] ?? null,
            'pickup_longitude' => $data['origen_lng'] ?? null,
            'pickup_notes' => $notasRecogida,
            'customer_name' => $data['destinatario_nombre'],
            'customer_phone' => $data['destinatario_telefono'],
            'delivery_address' => $data['destino_direccion'].', '.$data['destino_municipio'],
            'delivery_latitude' => $data['destino_lat'] ?? null,
            'delivery_longitude' => $data['destino_lng'] ?? null,
            'delivery_notes' => $data['notas_entrega'] ?? null,
            'payment_method' => 'cash',
            /* Si paga quien recibe, el repartidor cobra el envio al entregar. */
            'amount_to_collect' => $pagaDestinatario ? $tarifa : 0,
            'delivery_fee' => $tarifa,
            'courier_earning' => round($tarifa * 0.7, 2),
            'metadata' => [
                'servicio' => 'atlantia_envios',
                'tamano' => $data['tamano'],
                'quien_paga' => $data['quien_paga'],
                'cobrar_al_recoger' => $pagaDestinatario ? 0 : $tarifa,
            ],
        ]);
    }

    /**
     * Municipios con servicio (los mismos de la tienda).
     *
     * @return array<int, string>
     */
    public static function municipios(): array
    {
        return array_keys(CoberturaColonias::catalogo()) ?: ['Puerto Barrios', 'Santo Tomas'];
    }
}
