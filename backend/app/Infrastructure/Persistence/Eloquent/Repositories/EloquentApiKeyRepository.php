<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Auth\Entities\ApiKey;
use App\Domain\Auth\Enums\KeyTier;
use App\Domain\Auth\Repositories\ApiKeyRepositoryInterface;
use App\Domain\Auth\ValueObjects\PlainTextApiToken;
use App\Infrastructure\Persistence\Eloquent\Models\Auth\ApiKeyModel;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * Implémentation Eloquent du ApiKeyRepositoryInterface.
 */
class EloquentApiKeyRepository implements ApiKeyRepositoryInterface
{
    public function findByToken(string $token): ?ApiKey
    {
        $hash = hash('sha256', $token);
        $row = ApiKeyModel::where('token_hash', $hash)->first();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByUuid(string $uuid): ?ApiKey
    {
        $row = ApiKeyModel::where('uuid', $uuid)->first();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByUser(int $userId): array
    {
        $rows = ApiKeyModel::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        return $rows->map(fn ($r) => $this->hydrate($r))->all();
    }

    public function create(
        string $name,
        array $permissions,
        int $userId,
        ?string $description = null,
        ?DateTimeImmutable $expiresAt = null,
        ?string $createdByIp = null
    ): array {
        $plainToken = new PlainTextApiToken;

        $row = ApiKeyModel::create([
            'uuid' => (string) Uuid::uuid4(),
            'user_id' => $userId,
            'name' => trim($name),
            'token_hash' => $plainToken->getHashed(),
            'token_prefix' => $plainToken->getPrefix(),
            'tier' => KeyTier::PUBLIC->value,
            'permissions' => $permissions,
            'description' => $description,
            'expires_at' => $expiresAt,
            'is_active' => true,
            'created_by_ip' => $createdByIp,
        ]);

        return [$this->hydrate($row), $plainToken];
    }

    public function revoke(string $uuid): bool
    {
        $row = ApiKeyModel::where('uuid', $uuid)->first();

        if ($row === null) {
            return false;
        }

        return (bool) $row->delete();
    }

    public function revokeAllByUser(int $userId): int
    {
        return ApiKeyModel::where('user_id', $userId)->delete();
    }

    public function regenerate(string $uuid): ?array
    {
        $row = ApiKeyModel::where('uuid', $uuid)->first();

        if ($row === null) {
            return null;
        }

        $plainToken = new PlainTextApiToken;

        $row->update([
            'token_hash' => $plainToken->getHashed(),
            'token_prefix' => $plainToken->getPrefix(),
        ]);

        $refreshed = ApiKeyModel::where('uuid', $uuid)->first();

        return [$this->hydrate($refreshed), $plainToken];
    }

    private function hydrate(ApiKeyModel $model): ApiKey
    {
        return new ApiKey(
            id: $model->id,
            uuid: $model->uuid,
            hashedToken: $model->token_hash,
            name: $model->name,
            tier: KeyTier::tryFrom($model->tier) ?? KeyTier::PUBLIC,
            permissions: (array) ($model->permissions ?? []),
            userId: $model->user_id,
            description: $model->description ?? null,
            expiresAt: $model->expires_at ? new DateTimeImmutable($model->expires_at->format('Y-m-d H:i:s')) : null,
            isActive: (bool) $model->is_active,
            lastUsedAt: $model->last_used_at ? new DateTimeImmutable($model->last_used_at->format('Y-m-d H:i:s')) : null,
            createdByIp: $model->created_by_ip ?? null,
            createdAt: new DateTimeImmutable($model->created_at->format('Y-m-d H:i:s')),
            updatedAt: $model->updated_at ? new DateTimeImmutable($model->updated_at->format('Y-m-d H:i:s')) : null
        );
    }
}
