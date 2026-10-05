<?php

declare(strict_types=1);

namespace App\Application\Territory\Queries;

/**
 * Query pour effectuer un géocodage inverse aux coordonnées données.
 */
final class ReverseGeocodeQuery
{
    public function __construct(
        public readonly float $longitude,
        public readonly float $latitude,
        public readonly ?int $targetLevel = null
    ) {}
}
