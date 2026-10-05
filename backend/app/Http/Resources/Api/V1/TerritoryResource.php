<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Application\Territory\DTOs\TerritoryDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource REST JSON standard pour un territoire.
 *
 * @property TerritoryDTO $resource
 */
class TerritoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource->uuid,
            'code' => $this->resource->code,
            'name' => $this->resource->name,
            'ansd_code' => $this->resource->ansdCode,
            'hierarchy' => [
                'level' => $this->resource->hierarchyLevel,
                'label' => $this->resource->hierarchyLevelLabel,
                'parent_code' => $this->resource->parentCode,
            ],
            'spatial' => [
                'centroid' => $this->resource->centroid,
                'bounding_box' => $this->resource->boundingBox,
                'area_km2' => $this->resource->areaKm2,
                'area_formatted' => $this->resource->areaFormatted,
            ],
            'demographics' => [
                'population' => $this->resource->population,
                'population_year' => $this->resource->populationYear,
                'capital' => $this->resource->capital,
            ],
            'status' => $this->resource->status,
            'quality_status' => $this->resource->qualityStatus,
            'metadata' => $this->resource->metadata,
            'geometry' => $this->when($this->resource->geometry !== null, $this->resource->geometry),
        ];
    }
}
