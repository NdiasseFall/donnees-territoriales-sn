<?php

declare(strict_types=1);

namespace App\Application\Territory\Handlers;

use App\Application\Territory\DTOs\HierarchyNodeDTO;
use App\Application\Territory\DTOs\TerritoryDTO;
use App\Application\Territory\Queries\GetTerritoryHierarchyQuery;
use App\Domain\Territory\Repositories\TerritoryRepositoryInterface;
use App\Domain\Territory\ValueObjects\TerritoryCode;

/**
 * Handler pour la résolution complète de la hiérarchie d'un territoire (parents et enfants).
 */
final class GetTerritoryHierarchyHandler
{
    public function __construct(
        private readonly TerritoryRepositoryInterface $territoryRepository
    ) {}

    public function handle(GetTerritoryHierarchyQuery $query): ?HierarchyNodeDTO
    {
        $code = new TerritoryCode($query->code);
        $territory = $this->territoryRepository->findByCode($code);

        if ($territory === null) {
            return null;
        }

        $ancestors = [];
        if ($query->includeAncestors) {
            $ancestorEntities = $this->territoryRepository->findAncestors($code);
            $ancestors = array_map(
                fn ($t) => TerritoryDTO::fromEntity($t, false),
                $ancestorEntities
            );
        }

        $children = [];
        if ($query->includeChildren) {
            $childEntities = $this->territoryRepository->findChildren($code);
            $children = array_map(
                fn ($t) => TerritoryDTO::fromEntity($t, false),
                $childEntities
            );
        }

        return new HierarchyNodeDTO(
            territory: TerritoryDTO::fromEntity($territory, false),
            ancestors: $ancestors,
            children: $children
        );
    }
}
