<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Territory\Repositories\SpatialQueryRepositoryInterface;
use App\Domain\Territory\Repositories\TerritoryRepositoryInterface;
use App\Domain\Territory\ValueObjects\BoundingBox;
use App\Domain\Territory\ValueObjects\Coordinates;
use App\Domain\Territory\ValueObjects\HierarchyLevel;
use App\Domain\Territory\ValueObjects\TerritoryCode;
use Illuminate\Support\Facades\DB;

/**
 * Implémentation PostGIS haute performance pour les opérations spatiales et les exports GeoJSON.
 */
class PostgisSpatialRepository implements SpatialQueryRepositoryInterface
{
    public function __construct(
        private readonly TerritoryRepositoryInterface $territoryRepository
    ) {}

    public function reverseGeocode(Coordinates $coordinates, ?HierarchyLevel $targetLevel = null): array
    {
        $sql = 'SELECT level, type_code as type, code, name FROM published.fn_reverse_geocode(?, ?)';
        $bindings = [$coordinates->getLongitude(), $coordinates->getLatitude()];

        $rows = DB::select($sql, $bindings);

        $results = [];
        foreach ($rows as $row) {
            $levelInt = (int) $row->level;
            if ($targetLevel !== null && $levelInt !== $targetLevel->getLevel()) {
                continue;
            }

            $results[] = [
                'level' => $levelInt,
                'type' => (string) $row->type,
                'code' => (string) $row->code,
                'name' => (string) $row->name,
            ];
        }

        return $results;
    }

    public function findWithinBoundingBox(BoundingBox $boundingBox, ?HierarchyLevel $level = null, int $limit = 500): array
    {
        $bboxWkt = sprintf(
            'SRID=4326;POLYGON((%f %f, %f %f, %f %f, %f %f, %f %f))',
            $boundingBox->getMinLon(), $boundingBox->getMinLat(),
            $boundingBox->getMaxLon(), $boundingBox->getMinLat(),
            $boundingBox->getMaxLon(), $boundingBox->getMaxLat(),
            $boundingBox->getMinLon(), $boundingBox->getMaxLat(),
            $boundingBox->getMinLon(), $boundingBox->getMinLat()
        );

        $query = DB::table('core.territories as t')
            ->join('core.geometries as g', 't.geometry_id', '=', 'g.id')
            ->join('core.territory_types as tt', 't.type_id', '=', 'tt.id')
            ->whereRaw('g.geometry && ST_GeomFromEWKT(?)', [$bboxWkt])
            ->whereRaw('ST_Intersects(g.geometry, ST_GeomFromEWKT(?))', [$bboxWkt]);

        if ($level !== null) {
            $query->where('tt.level', $level->getLevel());
        }

        $codes = $query->limit($limit)->pluck('t.code');

        $territories = [];
        foreach ($codes as $code) {
            $territory = $this->territoryRepository->findByCode(new TerritoryCode($code));
            if ($territory !== null) {
                $territories[] = $territory;
            }
        }

        return $territories;
    }

    public function findIntersecting(string $geojsonOrWkt, ?HierarchyLevel $level = null, int $limit = 100): array
    {
        $isGeojson = str_starts_with(trim($geojsonOrWkt), '{');

        if ($isGeojson) {
            $geomExpr = 'ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)';
        } else {
            $geomExpr = 'ST_SetSRID(ST_GeomFromText(?), 4326)';
        }

        $query = DB::table('core.territories as t')
            ->join('core.geometries as g', 't.geometry_id', '=', 'g.id')
            ->join('core.territory_types as tt', 't.type_id', '=', 'tt.id')
            ->whereRaw("g.geometry && {$geomExpr}", [$geojsonOrWkt])
            ->whereRaw("ST_Intersects(g.geometry, {$geomExpr})", [$geojsonOrWkt]);

        if ($level !== null) {
            $query->where('tt.level', $level->getLevel());
        }

        $codes = $query->limit($limit)->pluck('t.code');

        $territories = [];
        foreach ($codes as $code) {
            $territory = $this->territoryRepository->findByCode(new TerritoryCode($code));
            if ($territory !== null) {
                $territories[] = $territory;
            }
        }

        return $territories;
    }

    public function getPublishedGeoJsonCollection(
        ?HierarchyLevel $level = null,
        ?string $parentCode = null,
        bool $simplified = false,
        float $simplifyTolerance = 0.001
    ): string {
        $result = DB::selectOne(
            'SELECT published.fn_get_territories_geojson_collection(?, ?, ?, ?) as collection',
            [
                $level?->getLevel(),
                $parentCode,
                $simplified,
                $simplifyTolerance,
            ]
        );

        return $result->collection ?? '{"type":"FeatureCollection","features":[]}';
    }

    public function getPublishedGeoJsonFeature(
        string $code,
        bool $simplified = false,
        float $simplifyTolerance = 0.001
    ): ?string {
        $result = DB::selectOne(
            'SELECT published.fn_get_territory_geojson(?, ?, ?) as feature',
            [
                $code,
                $simplified,
                $simplifyTolerance,
            ]
        );

        return $result?->feature ?? null;
    }
}
