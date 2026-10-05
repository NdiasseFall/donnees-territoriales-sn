<?php

declare(strict_types=1);

namespace App\Domain\Territory\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object représentant une boîte englobante spatiale (BBOX EPSG:4326).
 */
final class BoundingBox
{
    public function __construct(
        private readonly float $minLon,
        private readonly float $minLat,
        private readonly float $maxLon,
        private readonly float $maxLat
    ) {
        if ($this->minLon >= $this->maxLon) {
            throw new InvalidArgumentException("minLon ({$this->minLon}) doit être strictement inférieur à maxLon ({$this->maxLon}).");
        }

        if ($this->minLat >= $this->maxLat) {
            throw new InvalidArgumentException("minLat ({$this->minLat}) doit être strictement inférieur à maxLat ({$this->maxLat}).");
        }

        if ($this->minLon < -180.0 || $this->maxLon > 180.0 || $this->minLat < -90.0 || $this->maxLat > 90.0) {
            throw new InvalidArgumentException('Coordonnées BBOX hors des limites terrestres WGS84.');
        }
    }

    public static function fromString(string $bboxString): self
    {
        $parts = array_map('floatval', explode(',', $bboxString));

        if (count($parts) !== 4) {
            throw new InvalidArgumentException('Format BBOX attendu : min_lon,min_lat,max_lon,max_lat');
        }

        return new self($parts[0], $parts[1], $parts[2], $parts[3]);
    }

    public function getMinLon(): float
    {
        return $this->minLon;
    }

    public function getMinLat(): float
    {
        return $this->minLat;
    }

    public function getMaxLon(): float
    {
        return $this->maxLon;
    }

    public function getMaxLat(): float
    {
        return $this->maxLat;
    }

    public function toArray(): array
    {
        return [$this->minLon, $this->minLat, $this->maxLon, $this->maxLat];
    }
}
