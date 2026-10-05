<?php

declare(strict_types=1);

namespace App\Domain\Territory\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object représentant une superficie territoriale calculée en kilomètres carrés (km²).
 */
final class Area
{
    public function __construct(private readonly float $squareKilometers)
    {
        if ($this->squareKilometers < 0.0) {
            throw new InvalidArgumentException('La superficie ne peut pas être négative.');
        }
    }

    public function toKm2(): float
    {
        return $this->squareKilometers;
    }

    public function toHectares(): float
    {
        return $this->squareKilometers * 100.0;
    }

    public function toSquareMeters(): float
    {
        return $this->squareKilometers * 1_000_000.0;
    }

    public function formatKm2(int $decimals = 2, string $decPoint = ',', string $thousandsSep = ' '): string
    {
        return number_format($this->squareKilometers, $decimals, $decPoint, $thousandsSep).' km²';
    }

    public function formatHectares(int $decimals = 1, string $decPoint = ',', string $thousandsSep = ' '): string
    {
        return number_format($this->toHectares(), $decimals, $decPoint, $thousandsSep).' ha';
    }
}
