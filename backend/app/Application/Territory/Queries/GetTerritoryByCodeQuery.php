<?php

declare(strict_types=1);

namespace App\Application\Territory\Queries;

/**
 * Query pour récupérer un territoire par son code unique.
 */
final class GetTerritoryByCodeQuery
{
    public function __construct(
        public readonly string $code,
        public readonly bool $includeGeometry = false,
        public readonly bool $simplifiedGeometry = false,
        public readonly float $simplifyTolerance = 0.001
    ) {}
}
