<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Territory\DTOs\TerritoryDTO;
use App\Application\Territory\Handlers\GetTerritoryByCodeHandler;
use App\Application\Territory\Handlers\GetTerritoryHierarchyHandler;
use App\Application\Territory\Handlers\GetTerritoryMapHandler;
use App\Application\Territory\Queries\GetTerritoryByCodeQuery;
use App\Application\Territory\Queries\GetTerritoryHierarchyQuery;
use App\Application\Territory\Queries\GetTerritoryMapQuery;
use App\Domain\Territory\Repositories\SpatialQueryRepositoryInterface;
use App\Domain\Territory\Repositories\TerritoryRepositoryInterface;
use App\Domain\Territory\ValueObjects\BoundingBox;
use App\Domain\Territory\ValueObjects\HierarchyLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GetTerritoriesRequest;
use App\Http\Resources\Api\V1\HierarchyNodeResource;
use App\Http\Resources\Api\V1\TerritoryMapResource;
use App\Http\Resources\Api\V1\TerritoryResource;
use App\Http\Responses\ApiError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class TerritoryController extends Controller
{
    public function __construct(
        private readonly TerritoryRepositoryInterface $territoryRepository,
        private readonly SpatialQueryRepositoryInterface $spatialRepository,
        private readonly GetTerritoryByCodeHandler $getTerritoryByCodeHandler,
        private readonly GetTerritoryHierarchyHandler $getTerritoryHierarchyHandler,
        private readonly GetTerritoryMapHandler $getTerritoryMapHandler
    ) {}

    /**
     * Liste paginée des territoires, avec filtres (US-012) et GeoJSON optimisé.
     */
    public function index(GetTerritoriesRequest $request): JsonResponse|Response
    {
        $level = $request->has('level') ? new HierarchyLevel((int) $request->input('level')) : null;
        $parentCode = $request->input('parent_code') ?? $request->input('parent');
        $format = $request->input('format', 'json');
        $simplified = $request->boolean('simplified', false);
        $tolerance = (float) $request->input('tolerance', 0.001);

        if ($format === 'geojson') {
            $geojson = $this->spatialRepository->getPublishedGeoJsonCollection(
                level: $level,
                parentCode: $parentCode,
                simplified: $simplified,
                simplifyTolerance: $tolerance
            );

            return response($geojson, 200, ['Content-Type' => 'application/geo+json']);
        }

        $type = $request->input('type');
        $status = $request->input('status');
        $search = $request->input('search');

        // BBOX : bornes invalides (inversées, hors WGS84) → 422, jamais un 500.
        $bbox = null;
        $rawBbox = $request->input('bbox');
        if ($rawBbox !== null) {
            try {
                $bbox = BoundingBox::fromString((string) $rawBbox);
            } catch (InvalidArgumentException $e) {
                return ApiError::make('INVALID_BBOX', $e->getMessage(), 422);
            }
        }

        $perPage = (int) $request->input('per_page', $request->input('limit', 50));
        $page = $request->has('page') ? max(1, (int) $request->input('page')) : null;
        $offset = $page !== null ? ($page - 1) * $perPage : (int) $request->input('offset', 0);

        $hasCriteria = $type !== null || $status !== null || $search !== null
            || $bbox !== null || $parentCode !== null;

        if ($hasCriteria) {
            $entities = $this->territoryRepository->findByCriteria(
                $level, $type, $parentCode, $status, $search, $bbox, $perPage, $offset
            );
            $total = $this->territoryRepository->countByCriteria(
                $level, $type, $parentCode, $status, $search, $bbox
            );
        } else {
            $effectiveLevel = $level ?? new HierarchyLevel(HierarchyLevel::REGION);
            $entities = $this->territoryRepository->findByHierarchyLevel($effectiveLevel, $perPage, $offset);
            $total = $this->territoryRepository->countByLevel($effectiveLevel);
        }

        $dtos = array_map(fn ($t) => TerritoryDTO::fromEntity($t, false), $entities);

        return response()->json([
            'success' => true,
            'count' => count($dtos),
            'meta' => [
                'total' => $total,
                'limit' => $perPage,
                'offset' => $offset,
                'page' => $page,
            ],
            'data' => TerritoryResource::collection($dtos),
        ]);
    }

    /**
     * Détail d'un territoire par code (ex: SN-DK ou SN).
     */
    public function show(string $code, Request $request): JsonResponse|Response
    {
        $format = $request->query('format', 'json');
        $simplified = $request->boolean('simplified', false);
        $tolerance = (float) $request->query('tolerance', 0.001);

        if ($format === 'geojson') {
            $geojson = $this->spatialRepository->getPublishedGeoJsonFeature($code, $simplified, $tolerance);
            if ($geojson === null) {
                return ApiError::make('TERRITORY_NOT_FOUND', 'Territoire introuvable.', 404);
            }

            return response($geojson, 200, ['Content-Type' => 'application/geo+json']);
        }

        $query = new GetTerritoryByCodeQuery(
            code: $code,
            includeGeometry: true,
            simplifiedGeometry: $simplified,
            simplifyTolerance: $tolerance
        );

        $dto = $this->getTerritoryByCodeHandler->handle($query);

        if ($dto === null) {
            return ApiError::make('TERRITORY_NOT_FOUND', "Territoire avec le code '{$code}' introuvable.", 404);
        }

        return response()->json([
            'success' => true,
            'data' => new TerritoryResource($dto),
        ]);
    }

    /**
     * Arbre hiérarchique complet d'un territoire (parents et enfants directs).
     */
    public function hierarchy(string $code): JsonResponse
    {
        $query = new GetTerritoryHierarchyQuery($code);
        $tree = $this->getTerritoryHierarchyHandler->handle($query);

        if ($tree === null) {
            return ApiError::make('TERRITORY_NOT_FOUND', "Territoire avec le code '{$code}' introuvable.", 404);
        }

        return response()->json([
            'success' => true,
            'data' => new HierarchyNodeResource($tree),
        ]);
    }

    /**
     * Enfants directs d'un territoire (US-012 / CDC §39).
     */
    public function children(string $code): JsonResponse
    {
        $query = new GetTerritoryHierarchyQuery(
            code: $code,
            includeChildren: true,
            includeAncestors: false
        );
        $tree = $this->getTerritoryHierarchyHandler->handle($query);

        if ($tree === null) {
            return ApiError::make('TERRITORY_NOT_FOUND', "Territoire avec le code '{$code}' introuvable.", 404);
        }

        return response()->json([
            'success' => true,
            'count' => count($tree->children),
            'data' => TerritoryResource::collection($tree->children),
        ]);
    }

    /**
     * Chaîne complète des parents, du pays vers le parent direct
     * (US-012 / CDC §39 : Sénégal → Thiès → Mbour → Commune de Mbour).
     */
    public function parents(string $code): JsonResponse
    {
        $query = new GetTerritoryHierarchyQuery(
            code: $code,
            includeChildren: false,
            includeAncestors: true
        );
        $tree = $this->getTerritoryHierarchyHandler->handle($query);

        if ($tree === null) {
            return ApiError::make('TERRITORY_NOT_FOUND', "Territoire avec le code '{$code}' introuvable.", 404);
        }

        // core.fn_get_territory_ancestors inclut le territoire lui-même :
        // la chaîne « parents » l'exclut (un territoire n'est pas son propre parent).
        $selfCode = strtoupper(trim($code));
        $ancestors = array_values(array_filter(
            $tree->ancestors,
            fn (TerritoryDTO $ancestor) => strtoupper($ancestor->code) !== $selfCode
        ));

        return response()->json([
            'success' => true,
            'count' => count($ancestors),
            'data' => TerritoryResource::collection($ancestors),
        ]);
    }

    /**
     * Géométrie seule d'un territoire, en GeoJSON Feature RFC 7946 (US-012).
     */
    public function geometry(string $code, Request $request): JsonResponse|Response
    {
        $simplified = $request->boolean('simplified', false);
        $tolerance = (float) $request->query('tolerance', 0.001);

        $geojson = $this->spatialRepository->getPublishedGeoJsonFeature($code, $simplified, $tolerance);

        if ($geojson === null) {
            return ApiError::make('TERRITORY_NOT_FOUND', "Territoire avec le code '{$code}' introuvable.", 404);
        }

        return response($geojson, 200, ['Content-Type' => 'application/geo+json']);
    }

    /**
     * Payload cartographique complet : geometry, bbox, centroid, properties,
     * children (US-012 / CDC §41).
     */
    public function map(string $code): JsonResponse
    {
        $dto = $this->getTerritoryMapHandler->handle(new GetTerritoryMapQuery($code));

        if ($dto === null) {
            return ApiError::make('TERRITORY_NOT_FOUND', "Territoire avec le code '{$code}' introuvable.", 404);
        }

        return response()->json([
            'success' => true,
            'data' => new TerritoryMapResource($dto),
        ]);
    }

    /**
     * Liste des régions du Sénégal (Niveau 1).
     */
    public function regions(Request $request): JsonResponse|Response
    {
        $request->merge(['level' => HierarchyLevel::REGION]);

        return $this->index(app(GetTerritoriesRequest::class));
    }

    /**
     * Liste des départements du Sénégal (Niveau 2).
     */
    public function departments(Request $request): JsonResponse|Response
    {
        $request->merge(['level' => HierarchyLevel::DEPARTMENT]);

        return $this->index(app(GetTerritoriesRequest::class));
    }

    /**
     * Liste des communes du Sénégal (Niveau 4).
     */
    public function communes(Request $request): JsonResponse|Response
    {
        $request->merge(['level' => HierarchyLevel::COMMUNE]);

        return $this->index(app(GetTerritoriesRequest::class));
    }
}
