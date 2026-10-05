<?php

declare(strict_types=1);

namespace App\Domain\Dataset\Entities;

use App\Domain\Dataset\Enums\AccessLevel;
use DateTimeImmutable;

/**
 * Entité représentant un jeu de données thématique ou administratif.
 */
class Dataset
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $uuid,
        private readonly string $slug,
        private string $name,
        private ?string $description,
        private ?int $sourceId,
        private ?string $sourceName,
        private ?string $category,
        private AccessLevel $accessLevel,
        private ?string $license,
        private array $tags,
        private array $metadata,
        private bool $isOfficial,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $updatedAt
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getSourceId(): ?int
    {
        return $this->sourceId;
    }

    public function getSourceName(): ?string
    {
        return $this->sourceName;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getAccessLevel(): AccessLevel
    {
        return $this->accessLevel;
    }

    public function getLicense(): ?string
    {
        return $this->license;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isOfficial(): bool
    {
        return $this->isOfficial;
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
