<?php

declare(strict_types=1);

namespace App\Domain\Auth\Entities;

use App\Domain\Auth\Enums\KeyTier;
use DateTimeImmutable;

/**
 * Entité représentant une clé API Sanctum avec tier de rate limiting et permissions.
 */
class ApiKey
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $uuid,
        private readonly string $hashedToken,
        private readonly string $name,
        private readonly KeyTier $tier,
        private array $permissions,
        private readonly ?int $userId,
        private readonly ?string $description,
        private readonly ?DateTimeImmutable $expiresAt,
        private readonly bool $isActive,
        private readonly ?DateTimeImmutable $lastUsedAt,
        private readonly ?string $createdByIp,
        private readonly DateTimeImmutable $createdAt,
        private readonly ?DateTimeImmutable $updatedAt
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTier(): KeyTier
    {
        return $this->tier;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return new DateTimeImmutable > $this->expiresAt;
    }

    public function getLastUsedAt(): ?DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function getCreatedByIp(): ?string
    {
        return $this->createdByIp;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
