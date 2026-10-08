<?php

declare(strict_types=1);

namespace App\Application\Territory\Handlers;

use App\Application\Territory\DTOs\TerritoryDTO;
use App\Application\Territory\DTOs\TerritoryMapDTO;
use App\Application\Territory\Queries\GetTerritoryMapQuery;
use App\Domain\Territory\Repositories\TerritoryRepositoryInterface;
use App\Domain\Territory\ValueObjects\TerritoryCode;

/**
 * Handler du payload cartographique complet d'un territoire (CDC §41) :
 * geometry, bbox, centroid, properties et children.
 */
final class GetTerritoryMapHandler
{
    public function __construct(
        private readonly TerritoryRepositoryInterface $territoryRepository
    ) {}

    public function handle(GetTerritoryMapQuery $query): ?TerritoryMapDTO
    {
        $code = new TerritoryCode($query->code);
        $territory = $this->territoryRepository->findByCode($code);

        if ($territory === null) {
            return null;
        }

        $children = [];
        if ($query->includeChildren) {
            $childEntities = $this->territoryRepository->findChildren($code);
            $children = array_map(
                fn ($t) => TerritoryDTO::fromEntity($t, false),
                $childEntities
            );
        }

        return new TerritoryMapDTO(
            territory: TerritoryDTO::fromEntity($territory, true),
            children: $children
        );
    }
}
