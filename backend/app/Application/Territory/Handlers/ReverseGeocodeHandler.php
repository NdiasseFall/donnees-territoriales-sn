<?php

declare(strict_types=1);

namespace App\Application\Territory\Handlers;

use App\Application\Territory\DTOs\ReverseGeocodeResultDTO;
use App\Application\Territory\DTOs\TerritoryDTO;
use App\Application\Territory\Queries\ReverseGeocodeQuery;
use App\Domain\Territory\Repositories\SpatialQueryRepositoryInterface;
use App\Domain\Territory\Repositories\TerritoryRepositoryInterface;
use App\Domain\Territory\ValueObjects\Coordinates;
use App\Domain\Territory\ValueObjects\HierarchyLevel;
use App\Domain\Territory\ValueObjects\TerritoryCode;

/**
 * Handler pour le géocodage inverse de coordonnées WGS84 vers la hiérarchie territoriale sénégalaise.
 */
final class ReverseGeocodeHandler
{
    public function __construct(
        private readonly SpatialQueryRepositoryInterface $spatialRepository,
        private readonly TerritoryRepositoryInterface $territoryRepository
    ) {}

    public function handle(ReverseGeocodeQuery $query): ReverseGeocodeResultDTO
    {
        $coordinates = new Coordinates($query->longitude, $query->latitude);
        $targetLevel = $query->targetLevel !== null ? new HierarchyLevel($query->targetLevel) : null;

        $results = $this->spatialRepository->reverseGeocode($coordinates, $targetLevel);

        $levelsMap = [];
        foreach ($results as $item) {
            $code = new TerritoryCode($item['code']);
            $territory = $this->territoryRepository->findByCode($code);
            if ($territory !== null) {
                $levelsMap[$item['level']] = TerritoryDTO::fromEntity($territory, false);
            }
        }

        return new ReverseGeocodeResultDTO(
            longitude: $query->longitude,
            latitude: $query->latitude,
            country: $levelsMap[HierarchyLevel::COUNTRY] ?? null,
            region: $levelsMap[HierarchyLevel::REGION] ?? null,
            department: $levelsMap[HierarchyLevel::DEPARTMENT] ?? null,
            arrondissement: $levelsMap[HierarchyLevel::ARRONDISSEMENT] ?? null,
            commune: $levelsMap[HierarchyLevel::COMMUNE] ?? null,
            districtOrVillage: $levelsMap[HierarchyLevel::DISTRICT_VILLAGE] ?? null,
            hamlet: $levelsMap[HierarchyLevel::HAMLET] ?? null
        );
    }
}
