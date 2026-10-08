<?php

declare(strict_types=1);

namespace App\Application\Territory\DTOs;

/**
 * Data Transfer Object du payload cartographique d'un territoire (CDC §41).
 *
 * Une réponse /map expose : geometry, bbox, centroid, properties, children.
 */
final class TerritoryMapDTO
{
    /**
     * @param  array<int, TerritoryDTO>  $children  Enfants directs (couche « enfants » de la carte).
     */
    public function __construct(
        public readonly TerritoryDTO $territory,
        public readonly array $children = []
    ) {}
}
