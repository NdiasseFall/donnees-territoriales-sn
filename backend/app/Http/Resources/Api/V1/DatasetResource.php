<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Application\Dataset\DTOs\DatasetDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource REST JSON pour un jeu de données du catalogue.
 *
 * @property DatasetDTO $resource
 */
class DatasetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource->uuid,
            'slug' => $this->resource->slug,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'source' => $this->resource->sourceName,
            'category' => $this->resource->category,
            'access_level' => $this->resource->accessLevel,
            'license' => $this->resource->license,
            'tags' => $this->resource->tags,
            'metadata' => $this->resource->metadata,
            'is_official' => $this->resource->isOfficial,
            'latest_version' => [
                'version' => $this->resource->latestVersion,
                'feature_count' => $this->resource->latestFeatureCount,
                'checksum_sha256' => $this->resource->latestChecksum,
                'published_at' => $this->resource->latestPublishedAt,
            ],
        ];
    }
}
