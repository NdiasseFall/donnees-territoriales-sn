<?php

declare(strict_types=1);

namespace App\Application\Territory\Queries;

use App\Application\Territory\DTOs\BoundingBoxDTO;

/**
 * Query pour récupérer tous les territoires dans une BBOX.
 */
final class GetTerritoriesInBboxQuery
{
    public function __construct(
        public readonly BoundingBoxDTO $bboxDto,
        public readonly bool $includeGeometry = true
    ) {}
}
