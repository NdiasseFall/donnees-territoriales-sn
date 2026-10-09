<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Application\Territory\DTOs\TerritoryMapDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource du payload cartographique d'un territoire (CDC §41) :
 * geometry, bbox, centroid, properties, children.
 *
 * @property TerritoryMapDTO $resource
 */
class TerritoryMapResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $territory = $this->resource->territory;

        return [
            'geometry' => $territory->geometry,
            'bbox' => $territory->boundingBox,
            'centroid' => $territory->centroid,
            'properties' => [
                'uuid' => $territory->uuid,
                'code' => $territory->code,
                'name' => $territory->name,
                'level' => $territory->hierarchyLevel,
                'level_label' => $territory->hierarchyLevelLabel,
                'parent_code' => $territory->parentCode,
                'area_km2' => $territory->areaKm2,
                'population' => $territory->population,
                'status' => $territory->status,
                'quality_status' => $territory->qualityStatus,
            ],
            'children' => TerritoryResource::collection($this->resource->children),
        ];
    }
}
