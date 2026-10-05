<?php

declare(strict_types=1);

namespace App\Application\Territory\Queries;

use App\Application\Territory\DTOs\SearchTerritoryCriteriaDTO;

/**
 * Query pour rechercher des territoires par critères textuels ou filtres de niveau.
 */
final class SearchTerritoriesQuery
{
    public function __construct(
        public readonly SearchTerritoryCriteriaDTO $criteria
    ) {}
}
