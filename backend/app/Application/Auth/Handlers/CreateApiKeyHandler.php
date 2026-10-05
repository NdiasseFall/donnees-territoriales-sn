<?php

declare(strict_types=1);

namespace App\Application\Auth\Handlers;

use App\Domain\Auth\Repositories\ApiKeyRepositoryInterface;

/**
 * Handler pour la création d'une clé API.
 */
final class CreateApiKeyHandler
{
    public function __construct(
        private readonly ApiKeyRepositoryInterface $apiKeyRepository
    ) {}

    public function handle(
        string $name,
        array $permissions,
        int $userId,
        ?string $description = null,
        ?\DateTimeImmutable $expiresAt = null,
        ?string $createdByIp = null
    ): array {
        [$apiKey, $plainToken] = $this->apiKeyRepository->create(
            name: $name,
            permissions: $permissions,
            userId: $userId,
            description: $description,
            expiresAt: $expiresAt,
            createdByIp: $createdByIp
        );

        return [$apiKey, $plainToken];
    }
}
