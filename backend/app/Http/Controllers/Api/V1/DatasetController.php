<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Dataset\DTOs\DatasetDTO;
use App\Domain\Dataset\Enums\AccessLevel;
use App\Domain\Dataset\Repositories\DatasetRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DatasetResource;
use App\Http\Responses\ApiError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DatasetController extends Controller
{
    public function __construct(
        private readonly DatasetRepositoryInterface $datasetRepository
    ) {}

    /**
     * Liste des jeux de données du catalogue avec leur version publiée la plus récente.
     */
    public function index(Request $request): JsonResponse
    {
        $category = $request->query('category');
        $accessLevelStr = $request->query('access_level');
        $accessLevel = $accessLevelStr ? AccessLevel::tryFrom(strtoupper($accessLevelStr)) : null;
        $limit = (int) $request->query('limit', 50);
        $offset = (int) $request->query('offset', 0);

        $datasets = $this->datasetRepository->findAll(
            category: $category,
            accessLevel: $accessLevel,
            limit: $limit,
            offset: $offset
        );

        $dtos = [];
        foreach ($datasets as $dataset) {
            $latestVersion = $this->datasetRepository->findLatestPublishedVersion($dataset->getId());
            $dtos[] = DatasetDTO::fromEntity($dataset, $latestVersion);
        }

        return response()->json([
            'success' => true,
            'count' => count($dtos),
            'data' => DatasetResource::collection($dtos),
        ]);
    }

    /**
     * Détails d'un jeu de données par slug.
     */
    public function show(string $slug): JsonResponse
    {
        $dataset = $this->datasetRepository->findBySlug($slug);

        if ($dataset === null) {
            return ApiError::make('DATASET_NOT_FOUND', "Jeu de données '{$slug}' introuvable.", 404);
        }

        $latestVersion = $this->datasetRepository->findLatestPublishedVersion($dataset->getId());
        $dto = DatasetDTO::fromEntity($dataset, $latestVersion);

        return response()->json([
            'success' => true,
            'data' => new DatasetResource($dto),
        ]);
    }
}
