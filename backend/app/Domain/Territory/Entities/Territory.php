<?php

declare(strict_types=1);

namespace App\Domain\Territory\Entities;

use App\Domain\Territory\Enums\QualityStatus;
use App\Domain\Territory\Enums\TerritoryStatus;
use App\Domain\Territory\ValueObjects\Area;
use App\Domain\Territory\ValueObjects\BoundingBox;
use App\Domain\Territory\ValueObjects\Coordinates;
use App\Domain\Territory\ValueObjects\HierarchyLevel;
use App\Domain\Territory\ValueObjects\TerritoryCode;
use DateTimeImmutable;

/**
 * Entité du Cœur Métier représentant une entité territoriale administrative ou géographique.
 */
class Territory
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $uuid,
        private TerritoryCode $code,
        private string $name,
        private ?string $normalizedName,
        private ?string $ansdCode,
        private HierarchyLevel $hierarchyLevel,
        private ?int $typeId,
        private ?string $typeName,
        private ?int $parentId,
        private ?TerritoryCode $parentCode,
        private ?Geometry $geometry,
        private ?Coordinates $centroid,
        private ?BoundingBox $boundingBox,
        private ?Area $area,
        private ?int $population,
        private ?int $populationYear,
        private ?string $capital,
        private array $metadata,
        private TerritoryStatus $status,
        private QualityStatus $qualityStatus,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $updatedAt
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getCode(): TerritoryCode
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNormalizedName(): ?string
    {
        return $this->normalizedName;
    }

    public function getAnsdCode(): ?string
    {
        return $this->ansdCode;
    }

    public function getHierarchyLevel(): HierarchyLevel
    {
        return $this->hierarchyLevel;
    }

    public function getTypeId(): ?int
    {
        return $this->typeId;
    }

    public function getTypeName(): ?string
    {
        return $this->typeName;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getParentCode(): ?TerritoryCode
    {
        return $this->parentCode;
    }

    public function getGeometry(): ?Geometry
    {
        return $this->geometry;
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

    public function getPopulation(): ?int
    {
        return $this->population;
    }

    public function getPopulationYear(): ?int
    {
        return $this->populationYear;
    }

    public function getCapital(): ?string
    {
        return $this->capital;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getStatus(): TerritoryStatus
    {
        return $this->status;
    }

    public function getQualityStatus(): QualityStatus
    {
        return $this->qualityStatus;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function rename(string $newName, ?string $newNormalizedName = null): void
    {
        $this->name = trim($newName);
        $this->normalizedName = $newNormalizedName ?? mb_strtoupper($this->name, 'UTF-8');
        $this->updatedAt = new DateTimeImmutable;
    }

    public function updatePopulation(int $population, int $year): void
    {
        $this->population = $population;
        $this->populationYear = $year;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function setStatus(TerritoryStatus $status): void
    {
        $this->status = $status;
        $this->updatedAt = new DateTimeImmutable;
    }
}
