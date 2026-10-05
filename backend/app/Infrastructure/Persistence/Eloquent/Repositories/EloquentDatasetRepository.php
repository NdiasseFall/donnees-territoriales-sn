<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Dataset\Entities\Dataset;
use App\Domain\Dataset\Entities\DatasetVersion;
use App\Domain\Dataset\Enums\AccessLevel;
use App\Domain\Dataset\Enums\DatasetVersionStatus;
use App\Domain\Dataset\Repositories\DatasetRepositoryInterface;
use App\Domain\Dataset\ValueObjects\Checksum;
use App\Domain\Dataset\ValueObjects\VersionNumber;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Implémentation Eloquent du DatasetRepositoryInterface pour le catalogue de données.
 */
class EloquentDatasetRepository implements DatasetRepositoryInterface
{
    public function findById(int $id): ?Dataset
    {
        $row = DB::table('core.datasets as d')
            ->leftJoin('core.sources as s', 'd.source_id', '=', 's.id')
            ->where('d.id', $id)
            ->select(['d.*', 's.name as source_name'])
            ->first();

        return $row ? $this->hydrateDataset($row) : null;
    }

    public function findBySlug(string $slug): ?Dataset
    {
        $row = DB::table('core.datasets as d')
            ->leftJoin('core.sources as s', 'd.source_id', '=', 's.id')
            ->where('d.slug', $slug)
            ->select(['d.*', 's.name as source_name'])
            ->first();

        return $row ? $this->hydrateDataset($row) : null;
    }

    public function findAll(
        ?string $category = null,
        ?AccessLevel $accessLevel = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $query = DB::table('core.datasets as d')
            ->leftJoin('core.sources as s', 'd.source_id', '=', 's.id');

        if ($category !== null) {
            $query->where('d.category', $category);
        }

        if ($accessLevel !== null) {
            $query->where('d.access_level', $accessLevel->value);
        }

        $rows = $query->select(['d.*', 's.name as source_name'])
            ->orderBy('d.name', 'asc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $rows->map(fn ($r) => $this->hydrateDataset($r))->all();
    }

    public function findLatestPublishedVersion(int $datasetId): ?DatasetVersion
    {
        $row = DB::table('core.dataset_versions')
            ->where('dataset_id', $datasetId)
            ->where('status', DatasetVersionStatus::PUBLISHED->value)
            ->orderByDesc('published_at')
            ->first();

        return $row ? $this->hydrateVersion($row) : null;
    }

    public function findVersions(int $datasetId): array
    {
        $rows = DB::table('core.dataset_versions')
            ->where('dataset_id', $datasetId)
            ->orderByDesc('created_at')
            ->get();

        return $rows->map(fn ($r) => $this->hydrateVersion($r))->all();
    }

    public function save(Dataset $dataset): Dataset
    {
        $data = [
            'uuid' => $dataset->getUuid(),
            'slug' => $dataset->getSlug(),
            'name' => $dataset->getName(),
            'description' => $dataset->getDescription(),
            'source_id' => $dataset->getSourceId(),
            'category' => $dataset->getCategory(),
            'access_level' => $dataset->getAccessLevel()->value,
            'license' => $dataset->getLicense(),
            'tags' => json_encode($dataset->getTags()),
            'metadata' => json_encode($dataset->getMetadata()),
            'is_official' => $dataset->isOfficial(),
            'updated_at' => now(),
        ];

        if ($dataset->getId() !== null) {
            DB::table('core.datasets')->where('id', $dataset->getId())->update($data);

            return $this->findById($dataset->getId()) ?? $dataset;
        }

        $data['created_at'] = now();
        $id = DB::table('core.datasets')->insertGetId($data);

        return $this->findById($id) ?? $dataset;
    }

    public function saveVersion(DatasetVersion $version): DatasetVersion
    {
        $data = [
            'uuid' => $version->getUuid(),
            'dataset_id' => $version->getDatasetId(),
            'version_number' => $version->getVersionNumber()->getValue(),
            'description' => $version->getDescription(),
            'status' => $version->getStatus()->value,
            'feature_count' => $version->getFeatureCount(),
            'checksum_sha256' => $version->getChecksum()?->getValue(),
            'file_path' => $version->getFilePath(),
            'file_size_bytes' => $version->getFileSizeBytes(),
            'validation_summary' => json_encode($version->getValidationSummary()),
            'published_at' => $version->getPublishedAt()?->format('Y-m-d H:i:s'),
        ];

        if ($version->getId() !== null) {
            DB::table('core.dataset_versions')->where('id', $version->getId())->update($data);

            return $version;
        }

        $data['created_at'] = now();
        $id = DB::table('core.dataset_versions')->insertGetId($data);

        $row = DB::table('core.dataset_versions')->where('id', $id)->first();

        return $this->hydrateVersion($row);
    }

    private function hydrateDataset(object $row): Dataset
    {
        $tags = isset($row->tags) ? (is_array($row->tags) ? $row->tags : json_decode((string) $row->tags, true) ?? []) : [];
        $metadata = isset($row->metadata) ? (is_array($row->metadata) ? $row->metadata : json_decode((string) $row->metadata, true) ?? []) : [];

        return new Dataset(
            id: (int) $row->id,
            uuid: (string) $row->uuid,
            slug: (string) $row->slug,
            name: (string) $row->name,
            description: $row->description ?? null,
            sourceId: isset($row->source_id) ? (int) $row->source_id : null,
            sourceName: $row->source_name ?? null,
            category: $row->category ?? null,
            accessLevel: AccessLevel::tryFrom($row->access_level ?? 'PUBLIC') ?? AccessLevel::PUBLIC,
            license: $row->license ?? null,
            tags: $tags,
            metadata: $metadata,
            isOfficial: (bool) ($row->is_official ?? false),
            createdAt: new DateTimeImmutable((string) $row->created_at),
            updatedAt: isset($row->updated_at) ? new DateTimeImmutable((string) $row->updated_at) : null
        );
    }

    private function hydrateVersion(object $row): DatasetVersion
    {
        $checksum = isset($row->checksum_sha256) && ! empty($row->checksum_sha256)
            ? new Checksum((string) $row->checksum_sha256)
            : null;

        $validationSummary = isset($row->validation_summary)
            ? (is_array($row->validation_summary) ? $row->validation_summary : json_decode((string) $row->validation_summary, true) ?? [])
            : [];

        return new DatasetVersion(
            id: (int) $row->id,
            uuid: (string) $row->uuid,
            datasetId: (int) $row->dataset_id,
            versionNumber: new VersionNumber((string) $row->version_number),
            description: $row->description ?? null,
            status: DatasetVersionStatus::tryFrom($row->status ?? 'draft') ?? DatasetVersionStatus::DRAFT,
            featureCount: isset($row->feature_count) ? (int) $row->feature_count : null,
            checksum: $checksum,
            filePath: $row->file_path ?? null,
            fileSizeBytes: isset($row->file_size_bytes) ? (int) $row->file_size_bytes : null,
            validationSummary: $validationSummary,
            publishedAt: isset($row->published_at) ? new DateTimeImmutable((string) $row->published_at) : null,
            createdAt: new DateTimeImmutable((string) $row->created_at)
        );
    }
}
