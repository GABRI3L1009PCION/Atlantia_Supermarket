<?php

namespace App\ValueObjects;

/**
 * Value object para importes monetarios sin errores de punto flotante.
 */
final readonly class Dinero
{
    /**
     * Crea una instancia basada en centavos.
     */
    private function __construct(private int $centavos) {}

    /**
     * Crea dinero desde una cantidad numerica.
     */
    public static function from(int|float|string|null $monto): self
    {
        if ($monto === null || $monto === '') {
            return new self(0);
        }

        return new self((int) round(((float) $monto) * 100));
    }

    /**
     * Devuelve cero monetario.
     */
    public static function zero(): self
    {
        return new self(0);
    }

    /**
     * Suma dos importes.
     */
    public function add(self $otro): self
    {
        return new self($this->centavos + $otro->centavos);
    }

    /**
     * Resta dos importes.
     */
    public function subtract(self $otro): self
    {
        return new self($this->centavos - $otro->centavos);
    }

    /**
     * Multiplica un importe por una cantidad entera.
     */
    public function multiply(int $factor): self
    {
        return new self($this->centavos * $factor);
    }

    /**
     * Calcula un porcentaje entero del importe.
     */
    public function percentage(int $porcentaje): self
    {
        return new self((int) round($this->centavos * ($porcentaje / 100)));
    }

    /**
     * Devuelve el valor decimal listo para persistir.
     */
    public function toDecimal(): string
    {
        return number_format($this->centavos / 100, 2, '.', '');
    }

    /**
     * Devuelve centavos.
     */
    public function toCents(): int
    {
        return $this->centavos;
    }

    /**
     * Devuelve representacion flotante solo para interoperabilidad externa.
     */
    public function toFloat(): float
    {
        return $this->centavos / 100;
    }
}
