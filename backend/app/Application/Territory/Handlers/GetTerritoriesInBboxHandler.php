<?php

declare(strict_types=1);

namespace App\Application\Territory\Handlers;

use App\Application\Territory\DTOs\TerritoryDTO;
use App\Application\Territory\Queries\GetTerritoriesInBboxQuery;
use App\Domain\Territory\Repositories\SpatialQueryRepositoryInterface;
use App\Domain\Territory\ValueObjects\HierarchyLevel;

/**
 * Handler pour la récupération spatiale de territoires par BBOX.
 */
final class GetTerritoriesInBboxHandler
{
    public function __construct(
        private readonly SpatialQueryRepositoryInterface $spatialRepository
    ) {}

    /**
     * @return TerritoryDTO[]
     */
    public function handle(GetTerritoriesInBboxQuery $query): array
    {
        $bboxVo = $query->bboxDto->toValueObject();
        $level = $query->bboxDto->hierarchyLevel !== null ? new HierarchyLevel($query->bboxDto->hierarchyLevel) : null;

        $territories = $this->spatialRepository->findWithinBoundingBox(
            boundingBox: $bboxVo,
            level: $level,
            limit: $query->bboxDto->limit
        );

        return array_map(
            fn ($territory) => TerritoryDTO::fromEntity($territory, $query->includeGeometry),
            $territories
        );
    }
}
