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
        string $search = '',
        string $order = 'name',
        string $direction = 'asc',
        int $limit = 50,
        int $offset = 0
    ): array;

    public function countByCriteria(
        ?string $category = null,
        ?AccessLevel $accessLevel = null,
        string $search = ''
    ): int;

    public function findLatestPublishedVersion(int $datasetId): ?DatasetVersion;

    /**
     * Recherche du catalogue (nom, description, catégorie, producteur), insensible
     * à la casse et aux apostrophes, avec trigrammes et plein-texte français.
     *
     * @return Dataset[]
     */
    public function search(string $query, int $limit = 20): array;

    /**
     * @return DatasetVersion[]
     */
    public function findVersions(int $datasetId): array;

    public function save(Dataset $dataset): Dataset;

    public function saveVersion(DatasetVersion $version): DatasetVersion;
}
