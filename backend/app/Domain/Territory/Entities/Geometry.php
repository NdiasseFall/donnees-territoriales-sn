<?php

declare(strict_types=1);

namespace App\Domain\Territory\Entities;

use App\Domain\Territory\ValueObjects\Area;
use App\Domain\Territory\ValueObjects\BoundingBox;
use App\Domain\Territory\ValueObjects\Coordinates;

/**
 * Entité représentant une géométrie spatiale indexée en base de données.
 */
class Geometry
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $uuid,
        private readonly string $geometryType,
        private readonly int $srid,
        private readonly ?string $geoJsonString,
        private readonly ?string $wkt,
        private readonly ?Coordinates $centroid,
        private readonly ?BoundingBox $boundingBox,
        private readonly ?Area $area,
        private readonly ?float $perimeterMeters,
        private readonly bool $isValid,
        private readonly ?string $validationError = null
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getGeometryType(): string
    {
        return $this->geometryType;
    }

    public function getSrid(): int
    {
        return $this->srid;
    }

    public function getGeoJsonString(): ?string
    {
        return $this->geoJsonString;
    }

    public function getGeoJsonArray(): ?array
    {
        if ($this->geoJsonString === null) {
            return null;
        }

        return json_decode($this->geoJsonString, true);
    }

    public function getWkt(): ?string
    {
        return $this->wkt;
    }

    public function getCentroid(): ?Coordinates
    {
        return $this->centroid;
    }

    public function getBoundingBox(): ?BoundingBox
    {
        return $this->boundingBox;
    }

    public function getArea(): ?Area
    {
        return $this->area;
    }

    public function getPerimeterMeters(): ?float
    {
        return $this->perimeterMeters;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getValidationError(): ?string
    {
        return $this->validationError;
    }
}
