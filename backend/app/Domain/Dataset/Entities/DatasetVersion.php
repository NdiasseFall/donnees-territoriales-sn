<?php

declare(strict_types=1);

namespace App\Domain\Dataset\Entities;

use App\Domain\Dataset\Enums\DatasetVersionStatus;
use App\Domain\Dataset\ValueObjects\Checksum;
use App\Domain\Dataset\ValueObjects\VersionNumber;
use DateTimeImmutable;

/**
 * Entité représentant une version spécifique et immuable d'un jeu de données.
 */
class DatasetVersion
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $uuid,
        private readonly int $datasetId,
        private VersionNumber $versionNumber,
        private ?string $description,
        private DatasetVersionStatus $status,
        private ?int $featureCount,
        private ?Checksum $checksum,
        private ?string $filePath,
        private ?int $fileSizeBytes,
        private array $validationSummary,
        private ?DateTimeImmutable $publishedAt,
        private DateTimeImmutable $createdAt
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getDatasetId(): int
    {
        return $this->datasetId;
    }

    public function getVersionNumber(): VersionNumber
    {
        return $this->versionNumber;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): DatasetVersionStatus
    {
        return $this->status;
    }

    public function getFeatureCount(): ?int
    {
        return $this->featureCount;
    }

    public function getChecksum(): ?Checksum
    {
        return $this->checksum;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function getFileSizeBytes(): ?int
    {
        return $this->fileSizeBytes;
    }

    public function getValidationSummary(): array
    {
        return $this->validationSummary;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isPublished(): bool
    {
        return $this->status === DatasetVersionStatus::PUBLISHED;
    }
}
