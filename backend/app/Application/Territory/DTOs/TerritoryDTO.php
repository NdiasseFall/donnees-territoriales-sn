<?php

declare(strict_types=1);

namespace App\Application\Territory\DTOs;

use App\Domain\Territory\Entities\Territory;

/**
 * Data Transfer Object pour la représentation d'un territoire en couche applicative.
 */
final class TerritoryDTO
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $ansdCode,
        public readonly int $hierarchyLevel,
        public readonly string $hierarchyLevelLabel,
        public readonly ?string $parentCode,
        public readonly ?array $centroid,
        public readonly ?array $boundingBox,
        public readonly ?float $areaKm2,
        public readonly ?string $areaFormatted,
        public readonly ?int $population,
        public readonly ?int $populationYear,
        public readonly ?string $capital,
        public readonly array $metadata,
        public readonly string $status,
        public readonly string $qualityStatus,
        public readonly ?array $geometry = null
    ) {}

    public static function fromEntity(Territory $territory, bool $includeGeometry = false): self
    {
        $geom = null;
        if ($includeGeometry && $territory->getGeometry() !== null) {
            $geom = $territory->getGeometry()->getGeoJsonArray();
        }

        return new self(
            uuid: $territory->getUuid(),
            code: $territory->getCode()->getValue(),
            name: $territory->getName(),
            ansdCode: $territory->getAnsdCode(),
            hierarchyLevel: $territory->getHierarchyLevel()->getLevel(),
            hierarchyLevelLabel: $territory->getHierarchyLevel()->getLabel(),
            parentCode: $territory->getParentCode()?->getValue(),
            centroid: $territory->getCentroid()?->toGeoJsonArray(),
            boundingBox: $territory->getBoundingBox()?->toArray(),
            areaKm2: $territory->getArea()?->toKm2(),
            areaFormatted: $territory->getArea()?->formatKm2(),
            population: $territory->getPopulation(),
            populationYear: $territory->getPopulationYear(),
            capital: $territory->getCapital(),
            metadata: $territory->getMetadata(),
            status: $territory->getStatus()->value,
            qualityStatus: $territory->getQualityStatus()->value,
            geometry: $geom
        );
    }
}
