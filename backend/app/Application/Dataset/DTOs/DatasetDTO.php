<?php

declare(strict_types=1);

namespace App\Application\Dataset\DTOs;

use App\Domain\Dataset\Entities\Dataset;
use App\Domain\Dataset\Entities\DatasetVersion;

/**
 * DTO pour les jeux de données et leurs métadonnées associées.
 */
final class DatasetDTO
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $slug,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $sourceName,
        public readonly ?string $category,
        public readonly string $accessLevel,
        public readonly ?string $license,
        public readonly array $tags,
        public readonly array $metadata,
        public readonly bool $isOfficial,
        public readonly ?string $latestVersion = null,
        public readonly ?int $latestFeatureCount = null,
        public readonly ?string $latestChecksum = null,
        public readonly ?string $latestPublishedAt = null
    ) {}

    public static function fromEntity(Dataset $dataset, ?DatasetVersion $latestVersion = null): self
    {
        return new self(
            uuid: $dataset->getUuid(),
            slug: $dataset->getSlug(),
            name: $dataset->getName(),
            description: $dataset->getDescription(),
            sourceName: $dataset->getSourceName(),
            category: $dataset->getCategory(),
            accessLevel: $dataset->getAccessLevel()->value,
            license: $dataset->getLicense(),
            tags: $dataset->getTags(),
            metadata: $dataset->getMetadata(),
            isOfficial: $dataset->isOfficial(),
            latestVersion: $latestVersion?->getVersionNumber()->getValue(),
            latestFeatureCount: $latestVersion?->getFeatureCount(),
            latestChecksum: $latestVersion?->getChecksum()?->getValue(),
            latestPublishedAt: $latestVersion?->getPublishedAt()?->format(DATE_ATOM)
        );
    }
}
