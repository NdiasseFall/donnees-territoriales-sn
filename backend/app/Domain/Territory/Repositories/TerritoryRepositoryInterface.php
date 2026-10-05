<?php

declare(strict_types=1);

namespace App\Domain\Territory\Repositories;

use App\Domain\Territory\Entities\Territory;
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

    public function save(Territory $territory): Territory;

    public function countByLevel(HierarchyLevel $level): int;
}
