<?php

declare(strict_types=1);

namespace App\Application\Territory\DTOs;

/**
 * DTO pour la structure hiérarchique arborescente (Parent -> Enfants).
 */
final class HierarchyNodeDTO
{
    /**
     * @param  HierarchyNodeDTO[]  $children
     */
    public function __construct(
        public readonly TerritoryDTO $territory,
        public readonly array $ancestors = [],
        public readonly array $children = []
    ) {}
}
