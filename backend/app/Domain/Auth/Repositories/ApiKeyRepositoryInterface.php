<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories;

use App\Domain\Auth\Entities\ApiKey;
use App\Domain\Auth\ValueObjects\PlainTextApiToken;

/**
 * Interface du repository pour la gestion des clés API.
 */
interface ApiKeyRepositoryInterface
{
    public function findByToken(string $token): ?ApiKey;

    public function findByUuid(string $uuid): ?ApiKey;

    /**
     * @return ApiKey[]
     */
    public function findByUser(int $userId): array;

    public function create(
        string $name,
        array $permissions,
        int $userId,
        ?string $description = null,
        ?\DateTimeImmutable $expiresAt = null,
        ?string $createdByIp = null
    ): array; // [ApiKey, PlainTextApiToken]

    public function revoke(string $uuid): bool;

    public function revokeAllByUser(int $userId): int;

    public function regenerate(string $uuid): ?array; // [ApiKey, PlainTextApiToken]
}
