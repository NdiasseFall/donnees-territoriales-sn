<?php

declare(strict_types=1);

namespace App\Application\Territory\Handlers;

use App\Application\Territory\DTOs\TerritoryDTO;
use App\Application\Territory\Queries\GetTerritoryByCodeQuery;
use App\Domain\Territory\Repositories\TerritoryRepositoryInterface;
use App\Domain\Territory\ValueObjects\TerritoryCode;

/**
 * Handler pour la récupération d'un territoire par code.
 */
final class GetTerritoryByCodeHandler
{
    public function __construct(
        private readonly TerritoryRepositoryInterface $territoryRepository
    ) {}

    public function handle(GetTerritoryByCodeQuery $query): ?TerritoryDTO
    {
        $code = new TerritoryCode($query->code);
        $territory = $this->territoryRepository->findByCode($code);

        if ($territory === null) {
            return null;
        }

        return TerritoryDTO::fromEntity($territory, $query->includeGeometry);
    }
}
