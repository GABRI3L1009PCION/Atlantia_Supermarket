<?php

namespace App\Enums;

/**
 * Estados operativos permitidos para pedidos.
 */
enum EstadoPedido: string
{
    case Pendiente = 'pendiente';
    case Confirmado = 'confirmado';
    case EnRevision = 'en_revision';
    case EnPreparacion = 'preparando';
    case ListoParaEntrega = 'listo_para_entrega';
    case EnRuta = 'en_ruta';
    case Entregado = 'entregado';
    case Cancelado = 'cancelado';
    case Rechazado = 'rechazado';

    /**
     * Indica si el pedido puede pasar al nuevo estado.
     */
    public function puedeTransicionarA(self $nuevo): bool
    {
        if ($this === $nuevo) {
            return false;
        }

        return in_array($nuevo, $this->transicionesPermitidas(), true);
    }

    /**
     * Devuelve el mapa de transiciones operativas permitidas.
     *
     * @return array<int, self>
     */
    private function transicionesPermitidas(): array
    {
        return match ($this) {
            self::Pendiente => [self::Confirmado, self::Cancelado, self::Rechazado],
            self::Confirmado => [self::EnRevision, self::EnPreparacion, self::Cancelado],
            self::EnRevision => [self::Confirmado, self::EnPreparacion, self::Cancelado, self::Rechazado],
            self::EnPreparacion => [self::ListoParaEntrega, self::Cancelado],
            self::ListoParaEntrega => [self::EnRuta, self::Cancelado],
            self::EnRuta => [self::Entregado, self::Cancelado],
            self::Entregado, self::Cancelado, self::Rechazado => [],
        };
    }
}
