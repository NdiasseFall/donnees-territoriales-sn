<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Territory\Entities\Geometry;
use App\Domain\Territory\Entities\Territory;
use App\Domain\Territory\Enums\QualityStatus;
use App\Domain\Territory\Enums\TerritoryStatus;
use App\Domain\Territory\Repositories\TerritoryRepositoryInterface;
use App\Domain\Territory\ValueObjects\Area;
use App\Domain\Territory\ValueObjects\BoundingBox;
use App\Domain\Territory\ValueObjects\Coordinates;
use App\Domain\Territory\ValueObjects\HierarchyLevel;
use App\Domain\Territory\ValueObjects\TerritoryCode;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Implémentation Eloquent/PostGIS du TerritoryRepositoryInterface.
 */
class EloquentTerritoryRepository implements TerritoryRepositoryInterface
{
    public function findById(int $id): ?Territory
    {
        $raw = DB::table('core.territories as t')
            ->leftJoin('core.territory_types as tt', 't.type_id', '=', 'tt.id')
            ->leftJoin('core.territories as pt', 't.parent_id', '=', 'pt.id')
            ->leftJoin('core.geometries as g', 't.geometry_id', '=', 'g.id')
            ->where('t.id', $id)
            ->select([
                't.*',
                'tt.level as type_level',
                'tt.name_fr as type_name',
                'pt.code as parent_code_value',
                'g.uuid as geom_uuid',
                'g.geometry_type',
                'g.srid',
                'g.area_km2',
                'g.perimeter_m',
                'g.is_valid as geom_is_valid',
                'g.validation_error as geom_validation_error',
                DB::raw('ST_AsGeoJSON(g.geometry) as geojson'),
                DB::raw('ST_X(g.centroid) as centroid_lon'),
                DB::raw('ST_Y(g.centroid) as centroid_lat'),
                DB::raw('ST_XMin(g.bbox) as bbox_min_lon'),
                DB::raw('ST_YMin(g.bbox) as bbox_min_lat'),
                DB::raw('ST_XMax(g.bbox) as bbox_max_lon'),
                DB::raw('ST_YMax(g.bbox) as bbox_max_lat'),
            ])
            ->first();

        return $raw ? $this->hydrateTerritory($raw) : null;
    }

    public function findByUuid(string $uuid): ?Territory
    {
        $raw = DB::table('core.territories as t')
            ->leftJoin('core.territory_types as tt', 't.type_id', '=', 'tt.id')
            ->leftJoin('core.territories as pt', 't.parent_id', '=', 'pt.id')
            ->leftJoin('core.geometries as g', 't.geometry_id', '=', 'g.id')
            ->where('t.uuid', $uuid)
            ->select([
                't.*',
                'tt.level as type_level',
                'tt.name_fr as type_name',
                'pt.code as parent_code_value',
                'g.uuid as geom_uuid',
                'g.geometry_type',
                'g.srid',
                'g.area_km2',
                'g.perimeter_m',
                'g.is_valid as geom_is_valid',
                'g.validation_error as geom_validation_error',
                DB::raw('ST_AsGeoJSON(g.geometry) as geojson'),
                DB::raw('ST_X(g.centroid) as centroid_lon'),
                DB::raw('ST_Y(g.centroid) as centroid_lat'),
                DB::raw('ST_XMin(g.bbox) as bbox_min_lon'),
                DB::raw('ST_YMin(g.bbox) as bbox_min_lat'),
                DB::raw('ST_XMax(g.bbox) as bbox_max_lon'),
                DB::raw('ST_YMax(g.bbox) as bbox_max_lat'),
            ])
            ->first();

        return $raw ? $this->hydrateTerritory($raw) : null;
    }

    public function findByCode(TerritoryCode $code): ?Territory
    {
        $raw = DB::table('core.territories as t')
            ->leftJoin('core.territory_types as tt', 't.type_id', '=', 'tt.id')
            ->leftJoin('core.territories as pt', 't.parent_id', '=', 'pt.id')
            ->leftJoin('core.geometries as g', 't.geometry_id', '=', 'g.id')
            ->where('t.code', $code->getValue())
            ->select([
                't.*',
                'tt.level as type_level',
                'tt.name_fr as type_name',
                'pt.code as parent_code_value',
                'g.uuid as geom_uuid',
                'g.geometry_type',
                'g.srid',
                'g.area_km2',
                'g.perimeter_m',
                'g.is_valid as geom_is_valid',
                'g.validation_error as geom_validation_error',
                DB::raw('ST_AsGeoJSON(g.geometry) as geojson'),
                DB::raw('ST_X(g.centroid) as centroid_lon'),
                DB::raw('ST_Y(g.centroid) as centroid_lat'),
                DB::raw('ST_XMin(g.bbox) as bbox_min_lon'),
                DB::raw('ST_YMin(g.bbox) as bbox_min_lat'),
                DB::raw('ST_XMax(g.bbox) as bbox_max_lon'),
                DB::raw('ST_YMax(g.bbox) as bbox_max_lat'),
            ])
            ->first();

        return $raw ? $this->hydrateTerritory($raw) : null;
    }

    public function findByHierarchyLevel(HierarchyLevel $level, int $limit = 100, int $offset = 0): array
    {
        $rows = DB::table('core.territories as t')
            ->join('core.territory_types as tt', 't.type_id', '=', 'tt.id')
            ->leftJoin('core.territories as pt', 't.parent_id', '=', 'pt.id')
            ->leftJoin('core.geometries as g', 't.geometry_id', '=', 'g.id')
            ->where('tt.level', $level->getLevel())
            ->select([
                't.*',
                'tt.level as type_level',
                'tt.name_fr as type_name',
                'pt.code as parent_code_value',
                'g.uuid as geom_uuid',
                'g.geometry_type',
                'g.srid',
                'g.area_km2',
                'g.perimeter_m',
                'g.is_valid as geom_is_valid',
                'g.validation_error as geom_validation_error',
                DB::raw('ST_AsGeoJSON(g.geometry) as geojson'),
                DB::raw('ST_X(g.centroid) as centroid_lon'),
                DB::raw('ST_Y(g.centroid) as centroid_lat'),
                DB::raw('ST_XMin(g.bbox) as bbox_min_lon'),
                DB::raw('ST_YMin(g.bbox) as bbox_min_lat'),
                DB::raw('ST_XMax(g.bbox) as bbox_max_lon'),
                DB::raw('ST_YMax(g.bbox) as bbox_max_lat'),
            ])
            ->orderBy('t.name', 'asc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $rows->map(fn ($row) => $this->hydrateTerritory($row))->all();
    }

    public function findChildren(TerritoryCode $parentCode): array
    {
        $rows = DB::table('core.territories as t')
            ->join('core.territory_types as tt', 't.type_id', '=', 'tt.id')
            ->join('core.territories as pt', 't.parent_id', '=', 'pt.id')
            ->leftJoin('core.geometries as g', 't.geometry_id', '=', 'g.id')
            ->where('pt.code', $parentCode->getValue())
            ->select([
                't.*',
                'tt.level as type_level',
                'tt.name_fr as type_name',
                'pt.code as parent_code_value',
                'g.uuid as geom_uuid',
                'g.geometry_type',
                'g.srid',
                'g.area_km2',
                'g.perimeter_m',
                'g.is_valid as geom_is_valid',
                'g.validation_error as geom_validation_error',
                DB::raw('ST_AsGeoJSON(g.geometry) as geojson'),
                DB::raw('ST_X(g.centroid) as centroid_lon'),
                DB::raw('ST_Y(g.centroid) as centroid_lat'),
                DB::raw('ST_XMin(g.bbox) as bbox_min_lon'),
                DB::raw('ST_YMin(g.bbox) as bbox_min_lat'),
                DB::raw('ST_XMax(g.bbox) as bbox_max_lon'),
                DB::raw('ST_YMax(g.bbox) as bbox_max_lat'),
            ])
            ->orderBy('t.name', 'asc')
            ->get();

        return $rows->map(fn ($row) => $this->hydrateTerritory($row))->all();
    }

    public function findAncestors(TerritoryCode $childCode): array
    {
        $results = DB::select('SELECT * FROM core.fn_get_territory_ancestors(?)', [$childCode->getValue()]);

        if (empty($results)) {
            return [];
        }

        $ancestorCodes = array_column($results, 'code');

        $rows = DB::table('core.territories as t')
            ->join('core.territory_types as tt', 't.type_id', '=', 'tt.id')
            ->leftJoin('core.territories as pt', 't.parent_id', '=', 'pt.id')
            ->leftJoin('core.geometries as g', 't.geometry_id', '=', 'g.id')
            ->whereIn('t.code', $ancestorCodes)
            ->select([
                't.*',
                'tt.level as type_level',
                'tt.name_fr as type_name',
                'pt.code as parent_code_value',
                'g.uuid as geom_uuid',
                'g.geometry_type',
                'g.srid',
                'g.area_km2',
                'g.perimeter_m',
                'g.is_valid as geom_is_valid',
                'g.validation_error as geom_validation_error',
                DB::raw('ST_AsGeoJSON(g.geometry) as geojson'),
                DB::raw('ST_X(g.centroid) as centroid_lon'),
                DB::raw('ST_Y(g.centroid) as centroid_lat'),
                DB::raw('ST_XMin(g.bbox) as bbox_min_lon'),
                DB::raw('ST_YMin(g.bbox) as bbox_min_lat'),
                DB::raw('ST_XMax(g.bbox) as bbox_max_lon'),
                DB::raw('ST_YMax(g.bbox) as bbox_max_lat'),
            ])
            ->orderBy('tt.level', 'asc')
            ->get();

        return $rows->map(fn ($row) => $this->hydrateTerritory($row))->all();
    }

    public function search(string $query, ?HierarchyLevel $level = null, int $limit = 20): array
    {
        $builder = DB::table('core.territories as t')
            ->join('core.territory_types as tt', 't.type_id', '=', 'tt.id')
            ->leftJoin('core.territories as pt', 't.parent_id', '=', 'pt.id')
            ->leftJoin('core.geometries as g', 't.geometry_id', '=', 'g.id');

        if ($level !== null) {
            $builder->where('tt.level', $level->getLevel());
        }

        $cleanQuery = trim($query);
        $builder->where(function ($q) use ($cleanQuery) {
            $q->whereRaw('t.code ILIKE ?', ["%{$cleanQuery}%"])
                ->orWhereRaw('t.name ILIKE ?', ["%{$cleanQuery}%"])
                ->orWhereRaw('t.normalized_name ILIKE ?', ['%'.strtoupper($cleanQuery).'%'])
                ->orWhereRaw("to_tsvector('french', unaccent(t.name)) @@ plainto_tsquery('french', unaccent(?))", [$cleanQuery]);
        });

        $rows = $builder->select([
            't.*',
            'tt.level as type_level',
            'tt.name_fr as type_name',
            'pt.code as parent_code_value',
            'g.uuid as geom_uuid',
            'g.geometry_type',
            'g.srid',
            'g.area_km2',
            'g.perimeter_m',
            'g.is_valid as geom_is_valid',
            'g.validation_error as geom_validation_error',
            DB::raw('ST_AsGeoJSON(g.geometry) as geojson'),
            DB::raw('ST_X(g.centroid) as centroid_lon'),
            DB::raw('ST_Y(g.centroid) as centroid_lat'),
            DB::raw('ST_XMin(g.bbox) as bbox_min_lon'),
            DB::raw('ST_YMin(g.bbox) as bbox_min_lat'),
            DB::raw('ST_XMax(g.bbox) as bbox_max_lon'),
            DB::raw('ST_YMax(g.bbox) as bbox_max_lat'),
        ])
            ->limit($limit)
            ->get();

        return $rows->map(fn ($row) => $this->hydrateTerritory($row))->all();
    }

    public function save(Territory $territory): Territory
    {
        $data = [
            'uuid' => $territory->getUuid(),
            'code' => $territory->getCode()->getValue(),
            'name' => $territory->getName(),
            'normalized_name' => $territory->getNormalizedName(),
            'ansd_code' => $territory->getAnsdCode(),
            'type_id' => $territory->getTypeId(),
            'parent_id' => $territory->getParentId(),
            'population' => $territory->getPopulation(),
            'population_year' => $territory->getPopulationYear(),
            'capital' => $territory->getCapital(),
            'status' => $territory->getStatus()->value,
            'quality_status' => $territory->getQualityStatus()->value,
            'metadata' => json_encode($territory->getMetadata()),
            'updated_at' => now(),
        ];

        if ($territory->getId() !== null) {
            DB::table('core.territories')->where('id', $territory->getId())->update($data);

            return $this->findById($territory->getId()) ?? $territory;
        }

        $data['created_at'] = now();
        $id = DB::table('core.territories')->insertGetId($data);

        return $this->findById($id) ?? $territory;
    }

    public function countByLevel(HierarchyLevel $level): int
    {
        return (int) DB::table('core.territories as t')
            ->join('core.territory_types as tt', 't.type_id', '=', 'tt.id')
            ->where('tt.level', $level->getLevel())
            ->count();
    }

    private function hydrateTerritory(object $row): Territory
    {
        $geometry = null;
        if (isset($row->geom_uuid) && $row->geom_uuid !== null) {
            $centroid = null;
            if (isset($row->centroid_lon) && $row->centroid_lon !== null) {
                $centroid = new Coordinates((float) $row->centroid_lon, (float) $row->centroid_lat);
            }

            $bbox = null;
            if (isset($row->bbox_min_lon) && $row->bbox_min_lon !== null) {
                $bbox = new BoundingBox(
                    (float) $row->bbox_min_lon,
                    (float) $row->bbox_min_lat,
                    (float) $row->bbox_max_lon,
                    (float) $row->bbox_max_lat
                );
            }

            $geometry = new Geometry(
                id: (int) $row->geometry_id,
                uuid: (string) $row->geom_uuid,
                geometryType: (string) $row->geometry_type,
                srid: (int) $row->srid,
                geoJsonString: $row->geojson ?? null,
                wkt: null,
                centroid: $centroid,
                boundingBox: $bbox,
                area: isset($row->area_km2) ? new Area((float) $row->area_km2) : null,
                perimeterMeters: isset($row->perimeter_m) ? (float) $row->perimeter_m : null,
                isValid: (bool) $row->geom_is_valid,
                validationError: $row->geom_validation_error ?? null
            );
        }

        $centroidVo = null;
        if (isset($row->centroid_lon) && $row->centroid_lon !== null) {
            $centroidVo = new Coordinates((float) $row->centroid_lon, (float) $row->centroid_lat);
        }

        $bboxVo = null;
        if (isset($row->bbox_min_lon) && $row->bbox_min_lon !== null) {
            $bboxVo = new BoundingBox(
                (float) $row->bbox_min_lon,
                (float) $row->bbox_min_lat,
                (float) $row->bbox_max_lon,
                (float) $row->bbox_max_lat
            );
        }

        $areaVo = isset($row->area_km2) ? new Area((float) $row->area_km2) : null;
        $parentCodeVo = isset($row->parent_code_value) ? new TerritoryCode($row->parent_code_value) : null;
        $metadata = isset($row->metadata) ? (is_array($row->metadata) ? $row->metadata : json_decode((string) $row->metadata, true) ?? []) : [];

        return new Territory(
            id: (int) $row->id,
            uuid: (string) $row->uuid,
            code: new TerritoryCode((string) $row->code),
            name: (string) $row->name,
            normalizedName: $row->normalized_name ?? null,
            ansdCode: $row->ansd_code ?? null,
            hierarchyLevel: new HierarchyLevel((int) ($row->type_level ?? 0)),
            typeId: isset($row->type_id) ? (int) $row->type_id : null,
            typeName: $row->type_name ?? null,
            parentId: isset($row->parent_id) ? (int) $row->parent_id : null,
            parentCode: $parentCodeVo,
            geometry: $geometry,
            centroid: $centroidVo,
            boundingBox: $bboxVo,
            area: $areaVo,
            population: isset($row->population) ? (int) $row->population : null,
            populationYear: isset($row->population_year) ? (int) $row->population_year : null,
            capital: $row->capital ?? null,
            metadata: $metadata,
            status: TerritoryStatus::tryFrom($row->status ?? 'ACTIVE') ?? TerritoryStatus::ACTIVE,
            qualityStatus: QualityStatus::tryFrom($row->quality_status ?? 'VALID') ?? QualityStatus::VALID,
            createdAt: new DateTimeImmutable((string) $row->created_at),
            updatedAt: isset($row->updated_at) ? new DateTimeImmutable((string) $row->updated_at) : null
        );
    }
}
