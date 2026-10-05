<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Territory\DTOs\SearchTerritoryCriteriaDTO;
use App\Application\Territory\Handlers\SearchTerritoriesHandler;
use App\Application\Territory\Queries\SearchTerritoriesQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SearchTerritoryRequest;
use App\Http\Resources\Api\V1\TerritoryResource;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __construct(
        private readonly SearchTerritoriesHandler $searchTerritoriesHandler
    ) {}

    /**
     * Recherche textuelle plein texte et phonétique/trigramme.
     */
    public function search(SearchTerritoryRequest $request): JsonResponse
    {
        $criteria = new SearchTerritoryCriteriaDTO(
            query: (string) $request->input('q'),
            hierarchyLevel: $request->has('level') ? (int) $request->input('level') : null,
            parentCode: $request->input('parent_code'),
            limit: (int) $request->input('limit', 20)
        );

        $query = new SearchTerritoriesQuery($criteria);
        $results = $this->searchTerritoriesHandler->handle($query);

        return response()->json([
            'success' => true,
            'query' => $criteria->query,
            'count' => count($results),
            'data' => TerritoryResource::collection($results),
        ]);
    }
}
