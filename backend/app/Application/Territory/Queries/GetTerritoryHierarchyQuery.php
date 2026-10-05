<?php

declare(strict_types=1);

namespace App\Application\Territory\Queries;

/**
 * Query pour récupérer l'arbre hiérarchique d'un territoire (ancêtres et descendants).
 */
final class GetTerritoryHierarchyQuery
{
    public function __construct(
        public readonly string $code,
        public readonly bool $includeChildren = true,
        public readonly bool $includeAncestors = true
    ) {}
}
