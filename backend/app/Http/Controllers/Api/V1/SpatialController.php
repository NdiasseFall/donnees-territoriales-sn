<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Territory\DTOs\BoundingBoxDTO;
use App\Application\Territory\DTOs\TerritoryDTO;
use App\Application\Territory\Handlers\GetTerritoriesInBboxHandler;
use App\Application\Territory\Handlers\ReverseGeocodeHandler;
use App\Application\Territory\Queries\GetTerritoriesInBboxQuery;
use App\Application\Territory\Queries\ReverseGeocodeQuery;
use App\Domain\Territory\Repositories\SpatialQueryRepositoryInterface;
use App\Domain\Territory\ValueObjects\HierarchyLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BoundingBoxRequest;
use App\Http\Requests\Api\V1\ReverseGeocodeRequest;
use App\Http\Requests\Api\V1\SpatialIntersectionRequest;
use App\Http\Resources\Api\V1\GeoJsonFeatureCollectionResource;
use App\Http\Resources\Api\V1\TerritoryResource;
use Illuminate\Http\JsonResponse;

class SpatialController extends Controller
{
    public function __construct(
        private readonly ReverseGeocodeHandler $reverseGeocodeHandler,
        private readonly GetTerritoriesInBboxHandler $getTerritoriesInBboxHandler,
        private readonly SpatialQueryRepositoryInterface $spatialRepository
    ) {}

    /**
     * Géocodage inverse : retourne la hiérarchie territoriale au point géographique WGS84 donné.
     */
    public function reverseGeocode(ReverseGeocodeRequest $request): JsonResponse
    {
        $query = new ReverseGeocodeQuery(
            longitude: (float) $request->input('lon'),
            latitude: (float) $request->input('lat'),
            targetLevel: $request->has('level') ? (int) $request->input('level') : null
        );

        $result = $this->reverseGeocodeHandler->handle($query);

        return response()->json([
            'success' => true,
            'query' => [
                'longitude' => $result->longitude,
                'latitude' => $result->latitude,
            ],
            'hierarchy' => [
                'country' => $result->country ? new TerritoryResource($result->country) : null,
                'region' => $result->region ? new TerritoryResource($result->region) : null,
                'department' => $result->department ? new TerritoryResource($result->department) : null,
                'arrondissement' => $result->arrondissement ? new TerritoryResource($result->arrondissement) : null,
                'commune' => $result->commune ? new TerritoryResource($result->commune) : null,
                'district_or_village' => $result->districtOrVillage ? new TerritoryResource($result->districtOrVillage) : null,
                'hamlet' => $result->hamlet ? new TerritoryResource($result->hamlet) : null,
            ],
        ]);
    }

    /**
     * Recherche spatiale par emprise Bounding Box (BBOX).
     */
    public function bbox(BoundingBoxRequest $request): JsonResponse
    {
        $parts = array_map('floatval', explode(',', $request->input('bbox')));
        $level = $request->has('level') ? (int) $request->input('level') : null;
        $limit = (int) $request->input('limit', 500);
        $format = $request->input('format', 'json');

        $bboxDto = new BoundingBoxDTO(
            minLon: $parts[0],
            minLat: $parts[1],
            maxLon: $parts[2],
            maxLat: $parts[3],
            hierarchyLevel: $level,
            limit: $limit
        );

        $query = new GetTerritoriesInBboxQuery(
            bboxDto: $bboxDto,
            includeGeometry: $format === 'geojson'
        );

        $dtos = $this->getTerritoriesInBboxHandler->handle($query);

        if ($format === 'geojson') {
            return response()->json(new GeoJsonFeatureCollectionResource(collect($dtos)));
        }

        return response()->json([
            'success' => true,
            'bbox' => $parts,
            'count' => count($dtos),
            'data' => TerritoryResource::collection($dtos),
        ]);
    }

    /**
     * Recherche d'intersections géospatiales avec une géométrie utilisateur (Polygon, LineString, Point).
     */
    public function intersect(SpatialIntersectionRequest $request): JsonResponse
    {
        $geometryInput = $request->input('geometry');
        $level = $request->has('level') ? new HierarchyLevel((int) $request->input('level')) : null;
        $limit = (int) $request->input('limit', 100);
        $format = $request->input('format', 'json');

        $entities = $this->spatialRepository->findIntersecting(
            geojsonOrWkt: $geometryInput,
            level: $level,
            limit: $limit
        );

        $dtos = array_map(fn ($t) => TerritoryDTO::fromEntity($t, $format === 'geojson'), $entities);

        if ($format === 'geojson') {
            return response()->json(new GeoJsonFeatureCollectionResource(collect($dtos)));
        }

        return response()->json([
            'success' => true,
            'count' => count($dtos),
            'data' => TerritoryResource::collection($dtos),
        ]);
    }
}
