<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Application\Territory\DTOs\HierarchyNodeDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource pour l'arbre hiérarchique territorial.
 *
 * @property HierarchyNodeDTO $resource
 */
class HierarchyNodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'territory' => new TerritoryResource($this->resource->territory),
            'ancestors' => TerritoryResource::collection($this->resource->ancestors),
            'children' => TerritoryResource::collection($this->resource->children),
        ];
    }
}
