<?php

declare(strict_types=1);

namespace App\Domain\Dataset\Repositories;

use App\Domain\Dataset\Entities\Dataset;
use App\Domain\Dataset\Entities\DatasetVersion;
use App\Domain\Dataset\Enums\AccessLevel;

/**
 * Interface du Repository pour la gestion des catalogues et versions de jeux de données.
 */
interface DatasetRepositoryInterface
{
    public function findById(int $id): ?Dataset;

    public function findBySlug(string $slug): ?Dataset;

    /**
     * @return Dataset[]
     */
    public function findAll(
        ?string $category = null,
        ?AccessLevel $accessLevel = null,
        int $limit = 50,
        int $offset = 0
    ): array;

    public function findLatestPublishedVersion(int $datasetId): ?DatasetVersion;

    /**
     * @return DatasetVersion[]
     */
    public function findVersions(int $datasetId): array;

    public function save(Dataset $dataset): Dataset;

    public function saveVersion(DatasetVersion $version): DatasetVersion;
}
