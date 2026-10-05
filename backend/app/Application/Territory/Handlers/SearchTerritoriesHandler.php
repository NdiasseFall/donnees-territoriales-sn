<?php

declare(strict_types=1);

namespace App\Application\Territory\Handlers;

use App\Application\Territory\DTOs\TerritoryDTO;
use App\Application\Territory\Queries\SearchTerritoriesQuery;
use App\Domain\Territory\Repositories\TerritoryRepositoryInterface;
use App\Domain\Territory\ValueObjects\HierarchyLevel;

/**
 * Handler pour la recherche plein texte et filtrée des territoires.
 */
final class SearchTerritoriesHandler
{
    public function __construct(
        private readonly TerritoryRepositoryInterface $territoryRepository
    ) {}

    /**
     * @return TerritoryDTO[]
     */
    public function handle(SearchTerritoriesQuery $query): array
    {
        $criteria = $query->criteria;
        $level = $criteria->hierarchyLevel !== null ? new HierarchyLevel($criteria->hierarchyLevel) : null;

        $territories = $this->territoryRepository->search(
            query: $criteria->query,
            level: $level,
            limit: $criteria->limit
        );

        return array_map(
            fn ($territory) => TerritoryDTO::fromEntity($territory, false),
            $territories
        );
    }
}
