<?php

declare(strict_types=1);

namespace App\Application\Territory\DTOs;

use App\Domain\Territory\ValueObjects\BoundingBox;

/**
 * DTO pour le filtre spatial BBOX.
 */
final class BoundingBoxDTO
{
    public function __construct(
        public readonly float $minLon,
        public readonly float $minLat,
        public readonly float $maxLon,
        public readonly float $maxLat,
        public readonly ?int $hierarchyLevel = null,
        public readonly int $limit = 500
    ) {}

    public function toValueObject(): BoundingBox
    {
        return new BoundingBox(
            $this->minLon,
            $this->minLat,
            $this->maxLon,
            $this->maxLat
        );
    }
}
