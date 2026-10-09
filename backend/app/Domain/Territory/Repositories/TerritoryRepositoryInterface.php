<?php

declare(strict_types=1);

namespace App\Domain\Territory\Repositories;

use App\Domain\Territory\Entities\Territory;
use App\Domain\Territory\ValueObjects\BoundingBox;
use App\Domain\Territory\ValueObjects\HierarchyLevel;
use App\Domain\Territory\ValueObjects\TerritoryCode;

/**
 * Interface du Repository Territoire définissant les opérations d'accès aux entités administratives.
 */
interface TerritoryRepositoryInterface
{
    public function findById(int $id): ?Territory;

    public function findByUuid(string $uuid): ?Territory;

    public function findByCode(TerritoryCode $code): ?Territory;

    /**
     * @return Territory[]
     */
    public function findByHierarchyLevel(HierarchyLevel $level, int $limit = 100, int $offset = 0): array;

    /**
     * @return Territory[]
     */
    public function findChildren(TerritoryCode $parentCode): array;

    /**
     * @return Territory[]
     */
    public function findAncestors(TerritoryCode $childCode): array;

    /**
     * @return Territory[]
     */
    public function search(string $query, ?HierarchyLevel $level = null, int $limit = 20): array;

    /**
     * Recherche paginée multicritères (US-012) — tous les filtres combinables.
     *
     * Le filtre spatial bbox est appliqué en requête préparée (ST_Intersects +
     * ST_MakeEnvelope avec liaisons de paramètres), jamais par concaténation SQL.
     *
     * @return Territory[]
     */
    public function findByCriteria(
        ?HierarchyLevel $level = null,
        ?string $type = null,
        ?string $parentCode = null,
        ?string $status = null,
        ?string $search = null,
        ?BoundingBox $bbox = null,
        int $limit = 50,
        int $offset = 0
    ): array;

    /**
     * Compteur aligné sur findByCriteria (pagination — US-012).
     */
    public function countByCriteria(
        ?HierarchyLevel $level = null,
        ?string $type = null,
        ?string $parentCode = null,
        ?string $status = null,
        ?string $search = null,
        ?BoundingBox $bbox = null
    ): int;

    public function save(Territory $territory): Territory;

    public function countByLevel(HierarchyLevel $level): int;
}
