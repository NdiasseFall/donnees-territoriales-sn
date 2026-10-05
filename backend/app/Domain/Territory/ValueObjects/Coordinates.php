<?php

declare(strict_types=1);

namespace App\Domain\Territory\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object représentant des coordonnées géographiques WGS84 (EPSG:4326).
 */
final class Coordinates
{
    public function __construct(
        private readonly float $longitude,
        private readonly float $latitude
    ) {
        if ($this->longitude < -180.0 || $this->longitude > 180.0) {
            throw new InvalidArgumentException("La longitude ({$this->longitude}) doit être comprise entre -180.0 et 180.0.");
        }

        if ($this->latitude < -90.0 || $this->latitude > 90.0) {
            throw new InvalidArgumentException("La latitude ({$this->latitude}) doit être comprise entre -90.0 et 90.0.");
        }
    }

    public static function fromArray(array $coords): self
    {
        if (count($coords) < 2) {
            throw new InvalidArgumentException('Le tableau de coordonnées doit contenir [longitude, latitude].');
        }

        return new self((float) $coords[0], (float) $coords[1]);
    }

    public static function fromString(string $coordString): self
    {
        $parts = array_map('trim', explode(',', $coordString));
        if (count($parts) !== 2) {
            throw new InvalidArgumentException("Format attendu : 'longitude,latitude' ou 'lat,lng'");
        }

        return new self((float) $parts[0], (float) $parts[1]);
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function toWktPoint(): string
    {
        return sprintf('POINT(%.8f %.8f)', $this->longitude, $this->latitude);
    }

    public function toGeoJsonArray(): array
    {
        return [$this->longitude, $this->latitude];
    }
}
