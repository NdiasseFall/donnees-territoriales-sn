<?php

declare(strict_types=1);

namespace App\Application\Territory\Queries;

/**
 * Query pour le payload cartographique complet d'un territoire (CDC §41).
 */
final class GetTerritoryMapQuery
{
    public function __construct(
        public readonly string $code,
        public readonly bool $includeChildren = true
    ) {}
}
