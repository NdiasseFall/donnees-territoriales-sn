<?php

declare(strict_types=1);

namespace App\Application\Auth\Handlers;

use App\Domain\Auth\Repositories\ApiKeyRepositoryInterface;

/**
 * Handler pour la révocation d'une clé API.
 */
final class RevokeApiKeyHandler
{
    public function __construct(
        private readonly ApiKeyRepositoryInterface $apiKeyRepository
    ) {}

    public function handle(string $uuid): bool
    {
        return $this->apiKeyRepository->revoke($uuid);
    }

    public function handleRevokeAll(int $userId): int
    {
        return $this->apiKeyRepository->revokeAllByUser($userId);
    }
}
