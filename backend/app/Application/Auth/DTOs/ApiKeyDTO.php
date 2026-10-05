<?php

declare(strict_types=1);

namespace App\Application\Auth\DTOs;

use App\Domain\Auth\Entities\ApiKey;

/**
 * DTO pour les clés API.
 */
final class ApiKeyDTO
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $name,
        public readonly string $tier,
        public readonly array $permissions,
        public readonly ?string $description,
        public readonly ?string $tokenPrefix,
        public readonly ?string $plainTextToken,
        public readonly ?string $expiresAt,
        public readonly bool $isActive,
        public readonly ?string $lastUsedAt,
        public readonly string $createdAt,
    ) {}

    public static function fromEntity(ApiKey $apiKey, ?string $plainTextToken = null): self
    {
        return new self(
            uuid: $apiKey->getUuid(),
            name: $apiKey->getName(),
            tier: $apiKey->getTier()->value,
            permissions: $apiKey->getPermissions(),
            description: $apiKey->getDescription(),
            tokenPrefix: $plainTextToken ? 'tnp_'.substr($plainTextToken, 0, 12) : null,
            plainTextToken: $plainTextToken,
            expiresAt: $apiKey->getExpiresAt()?->format(DATE_ATOM),
            isActive: $apiKey->isActive(),
            lastUsedAt: $apiKey->getLastUsedAt()?->format(DATE_ATOM),
            createdAt: $apiKey->getCreatedAt()->format(DATE_ATOM),
        );
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'tier' => $this->tier,
            'permissions' => $this->permissions,
            'description' => $this->description,
            'token_prefix' => $this->tokenPrefix,
            'plain_text_token' => $this->plainTextToken,
            'expires_at' => $this->expiresAt,
            'is_active' => $this->isActive,
            'last_used_at' => $this->lastUsedAt,
            'created_at' => $this->createdAt,
        ];
    }
}
