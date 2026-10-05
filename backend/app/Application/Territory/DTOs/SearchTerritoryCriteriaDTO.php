<?php

declare(strict_types=1);

namespace App\Application\Territory\DTOs;

/**
 * DTO pour les critères de recherche multicritère et plein texte.
 */
final class SearchTerritoryCriteriaDTO
{
    public function __construct(
        public readonly string $query,
        public readonly ?int $hierarchyLevel = null,
        public readonly ?string $parentCode = null,
        public readonly int $limit = 20,
        public readonly int $offset = 0
    ) {}
}
