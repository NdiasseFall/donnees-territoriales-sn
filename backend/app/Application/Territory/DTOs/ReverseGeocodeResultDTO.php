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
        public readonly ?TerritoryDTO $hamlet = null,
        public readonly ?string $confidence = null,
        public readonly ?float $boundaryDistanceM = null
    ) {}

    /**
     * Vrai si aucun territoire n'a été résolu (point hors emprise ou en mer).
     * Déclenche un 404 côté API (US-014, CDC §44) — jamais de commune arbitraire.
     */
    public function isEmpty(): bool
    {
        return $this->country === null
            && $this->region === null
            && $this->department === null
            && $this->arrondissement === null
            && $this->commune === null
            && $this->districtOrVillage === null
            && $this->hamlet === null;
    }
}
