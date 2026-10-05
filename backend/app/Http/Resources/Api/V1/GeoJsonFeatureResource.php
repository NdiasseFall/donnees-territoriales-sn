<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Application\Territory\DTOs\TerritoryDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource GeoJSON Feature conforme à la spécification RFC 7946.
 *
 * @property TerritoryDTO $resource
 */
class GeoJsonFeatureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'Feature',
            'id' => $this->resource->code,
            'geometry' => $this->resource->geometry ?? [
                'type' => 'Point',
                'coordinates' => $this->resource->centroid ?? [0, 0],
            ],
            'properties' => [
                'uuid' => $this->resource->uuid,
                'code' => $this->resource->code,
                'name' => $this->resource->name,
                'ansd_code' => $this->resource->ansdCode,
                'level' => $this->resource->hierarchyLevel,
                'level_label' => $this->resource->hierarchyLevelLabel,
                'parent_code' => $this->resource->parentCode,
                'area_km2' => $this->resource->areaKm2,
                'population' => $this->resource->population,
                'population_year' => $this->resource->populationYear,
                'capital' => $this->resource->capital,
                'status' => $this->resource->status,
                'quality_status' => $this->resource->qualityStatus,
            ],
            'bbox' => $this->resource->boundingBox,
        ];
    }
}
