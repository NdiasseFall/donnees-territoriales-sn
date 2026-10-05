<?php

declare(strict_types=1);

namespace App\Domain\Territory\Repositories;

use App\Domain\Territory\Entities\Territory;
use App\Domain\Territory\ValueObjects\BoundingBox;
use App\Domain\Territory\ValueObjects\Coordinates;
use App\Domain\Territory\ValueObjects\HierarchyLevel;

/**
 * Interface pour les opérations géospatiales complexes et haute performance PostGIS.
 */
interface SpatialQueryRepositoryInterface
{
    /**
     * Géocodage inverse : retourne le territoire englobant un point donné.
     *
     * @return array<int, array{level: int, type: string, code: string, name: string}>
     */
    public function reverseGeocode(Coordinates $coordinates, ?HierarchyLevel $targetLevel = null): array;

    /**
     * Recherche spatiale dans une boîte englobante (BBOX).
     *
     * @return Territory[]
     */
    public function findWithinBoundingBox(BoundingBox $boundingBox, ?HierarchyLevel $level = null, int $limit = 500): array;

    /**
     * Recherche des territoires intersectant une géométrie GeoJSON ou WKT donnée.
     *
     * @return Territory[]
     */
    public function findIntersecting(string $geojsonOrWkt, ?HierarchyLevel $level = null, int $limit = 100): array;

    /**
     * Export GeoJSON FeatureCollection optimisé directement depuis PostgreSQL.
     */
    public function getPublishedGeoJsonCollection(
        ?HierarchyLevel $level = null,
        ?string $parentCode = null,
        bool $simplified = false,
        float $simplifyTolerance = 0.001
    ): string;

    /**
     * Export GeoJSON Feature unique optimisé.
     */
    public function getPublishedGeoJsonFeature(
        string $code,
        bool $simplified = false,
        float $simplifyTolerance = 0.001
    ): ?string;
}
