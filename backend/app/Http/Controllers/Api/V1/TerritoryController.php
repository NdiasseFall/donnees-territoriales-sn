<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Territory\DTOs\TerritoryDTO;
use App\Application\Territory\Handlers\GetTerritoryByCodeHandler;
use App\Application\Territory\Handlers\GetTerritoryHierarchyHandler;
use App\Application\Territory\Queries\GetTerritoryByCodeQuery;
use App\Application\Territory\Queries\GetTerritoryHierarchyQuery;
use App\Domain\Territory\Repositories\SpatialQueryRepositoryInterface;
use App\Domain\Territory\Repositories\TerritoryRepositoryInterface;
use App\Domain\Territory\ValueObjects\HierarchyLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GetTerritoriesRequest;
use App\Http\Resources\Api\V1\HierarchyNodeResource;
use App\Http\Resources\Api\V1\TerritoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TerritoryController extends Controller
{
    public function __construct(
        private readonly TerritoryRepositoryInterface $territoryRepository,
        private readonly SpatialQueryRepositoryInterface $spatialRepository,
        private readonly GetTerritoryByCodeHandler $getTerritoryByCodeHandler,
        private readonly GetTerritoryHierarchyHandler $getTerritoryHierarchyHandler
    ) {}

    /**
     * Liste paginée des territoires, avec option GeoJSON optimisé.
     */
    public function index(GetTerritoriesRequest $request): JsonResponse|Response
    {
        $level = $request->has('level') ? new HierarchyLevel((int) $request->input('level')) : null;
        $parentCode = $request->input('parent_code');
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

        $limit = (int) $request->input('limit', 50);
        $offset = (int) $request->input('offset', 0);

        if ($level !== null) {
            $entities = $this->territoryRepository->findByHierarchyLevel($level, $limit, $offset);
        } else {
            $entities = $this->territoryRepository->findByHierarchyLevel(new HierarchyLevel(HierarchyLevel::REGION), $limit, $offset);
        }

        $dtos = array_map(fn ($t) => TerritoryDTO::fromEntity($t, false), $entities);

        return response()->json([
            'success' => true,
            'count' => count($dtos),
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
                return response()->json(['error' => 'Territoire introuvable.'], 404);
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
            return response()->json(['error' => "Territoire avec le code '{$code}' introuvable."], 404);
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
            return response()->json(['error' => "Territoire avec le code '{$code}' introuvable."], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new HierarchyNodeResource($tree),
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
