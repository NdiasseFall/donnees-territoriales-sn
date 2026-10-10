<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Dataset\DTOs\DatasetDTO;
use App\Domain\Dataset\Enums\AccessLevel;
use App\Domain\Dataset\Repositories\DatasetRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DatasetCatalogRequest;
use App\Http\Resources\Api\V1\DatasetResource;
use App\Http\Responses\ApiError;
use Illuminate\Http\JsonResponse;

class DatasetController extends Controller
{
    public function __construct(
        private readonly DatasetRepositoryInterface $datasetRepository
    ) {}

    /**
     * Liste paginée des jeux de données du catalogue, avec recherches, filtres et tri.
     */
    public function index(DatasetCatalogRequest $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', $request->input('limit', 50));
        $page = $request->has('page') ? max(1, (int) $request->input('page')) : null;
        $offset = $page !== null ? ($page - 1) * $perPage : (int) $request->input('offset', 0);

        // CDC : l'enum AccessLevel est en MAJUSCULES ; la normalisation rend
        // le filtre insensible à la casse (?access_level=public == PUBLIC).
        $accessLevel = $request->input('access_level') !== null
            ? AccessLevel::tryFrom(strtoupper((string) $request->input('access_level')))
            : null;

        // Alias de rétrocompatibilité : « search » est synonyme de « q ».
        $search = (string) ($request->input('q') ?? $request->input('search', ''));

        $datasets = $this->datasetRepository->findAll(
            category: $request->input('category'),
            accessLevel: $accessLevel,
            search: $search,
            order: $request->input('order', 'name'),
            direction: $request->input('direction', 'asc'),
            limit: $perPage,
            offset: $offset
        );

        $total = $this->datasetRepository->countByCriteria(
            category: $request->input('category'),
            accessLevel: $accessLevel,
            search: $search
        );

        $dtos = [];
        foreach ($datasets as $dataset) {
            $latestVersion = $this->datasetRepository->findLatestPublishedVersion($dataset->getId());
            $dtos[] = DatasetDTO::fromEntity($dataset, $latestVersion);
        }

        $shown = count($dtos);

        return response()->json([
            'success' => true,
            'count' => $shown,
            'total' => $total,
            'current_page' => $page ?? 1,
            'per_page' => $perPage,
            'last_page' => $total > 0 ? (int) ceil($total / (float) $perPage) : 0,
            // CDC §60 : sur une page vide (hors plage), from/to valent 0.
            'from' => $shown > 0 ? $offset + 1 : 0,
            'to' => $shown > 0 ? $offset + $shown : 0,
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
