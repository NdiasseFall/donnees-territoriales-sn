<?php

declare(strict_types=1);

namespace App\Application\Territory\DTOs;

/**
 * DTO pour le résultat de géocodage inverse.
 */
final class ReverseGeocodeResultDTO
{
    public function __construct(
        public readonly float $longitude,
        public readonly float $latitude,
        public readonly ?TerritoryDTO $country = null,
        public readonly ?TerritoryDTO $region = null,
        public readonly ?TerritoryDTO $department = null,
        public readonly ?TerritoryDTO $arrondissement = null,
        public readonly ?TerritoryDTO $commune = null,
        public readonly ?TerritoryDTO $districtOrVillage = null,
        public readonly ?TerritoryDTO $hamlet = null
    ) {}
}
