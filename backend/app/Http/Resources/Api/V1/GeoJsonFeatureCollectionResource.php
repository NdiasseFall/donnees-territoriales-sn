<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Ressource GeoJSON FeatureCollection conforme RFC 7946.
 */
class GeoJsonFeatureCollectionResource extends ResourceCollection
{
    public $collects = GeoJsonFeatureResource::class;

    public function toArray(Request $request): array
    {
        return [
            'type' => 'FeatureCollection',
            'crs' => [
                'type' => 'name',
                'properties' => [
                    'name' => 'urn:ogc:def:crs:OGC:1.3:CRS84',
                ],
            ],
            'features' => $this->collection,
            'total_features' => $this->collection->count(),
        ];
    }
}
