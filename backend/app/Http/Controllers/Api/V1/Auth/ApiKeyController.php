<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Application\Auth\DTOs\ApiKeyDTO;
use App\Application\Auth\Handlers\CreateApiKeyHandler;
use App\Domain\Auth\Enums\KeyTier;
use App\Domain\Auth\Repositories\ApiKeyRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function __construct(
        private readonly CreateApiKeyHandler $createKeyHandler
    ) {}

    /**
     * Lister les clés API du user authentifié.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();

        if ($userId === null) {
            return response()->json(['error' => 'Non authentifié.'], 401);
        }

        $repo = app(ApiKeyRepositoryInterface::class);
        $keys = $repo->findByUser($userId);

        return response()->json([
            'success' => true,
            'count' => count($keys),
            'data' => array_map(
                fn ($k) => ApiKeyDTO::fromEntity($k)->toArray(),
                $keys
            ),
        ]);
    }

    /**
     * Créer une nouvelle clé API.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string'],
            'tier' => ['nullable', 'string', 'in:public,developer,admin'],
            'description' => ['nullable', 'string', 'max:500'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $userId = auth()->id();
        if ($userId === null) {
            return response()->json(['error' => 'Non authentifié.'], 401);
        }

        $tier = KeyTier::tryFrom($validated['tier'] ?? 'public') ?? KeyTier::PUBLIC;

        [$apiKey, $plainToken] = $this->createKeyHandler->handle(
            name: $validated['name'],
            permissions: $validated['permissions'],
            userId: $userId,
            description: $validated['description'] ?? null,
            expiresAt: $validated['expires_at'] ? new \DateTimeImmutable($validated['expires_at']) : null,
            createdByIp: $request->ip()
        );

        return response()->json([
            'success' => true,
            'data' => ApiKeyDTO::fromEntity($apiKey, $plainToken)->toArray(),
        ], 201);
    }

    /**
     * Révoquer une clé API.
     */
    public function destroy(string $uuid): JsonResponse
    {
        $repo = app(ApiKeyRepositoryInterface::class);
        $result = $repo->revoke($uuid);

        if ($result === false) {
            return response()->json(['error' => 'Clé API introuvable.'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Clé API révoquée.',
        ]);
    }

    /**
     * Régénérer le token d'une clé API (invalide l'ancien).
     */
    public function regenerate(string $uuid): JsonResponse
    {
        $repo = app(ApiKeyRepositoryInterface::class);
        $result = $repo->regenerate($uuid);

        if ($result === null) {
            return response()->json(['error' => 'Clé API introuvable.'], 404);
        }

        [$apiKey, $plainToken] = $result;

        return response()->json([
            'success' => true,
            'data' => ApiKeyDTO::fromEntity($apiKey, $plainToken)->toArray(),
        ], 200);
    }
}
