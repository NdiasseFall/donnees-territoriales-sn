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
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Implémentation Eloquent/PostGIS du TerritoryRepositoryInterface.
 */
class EloquentTerritoryRepository implements TerritoryRepositoryInterface
{
    public function findById(int $id): ?Territory
    {
        $raw = DB::table('core.territories as t')
            ->leftJoin('core.territory_types as tt', 't.territory_type_id', '=', 'tt.id')
            ->leftJoin('core.territories as pt', 't.parent_id', '=', 'pt.id')
            ->leftJoin('core.geometries as g', 't.geometry_id', '=', 'g.id')
            ->where('t.id', $id)
            ->select([
                't.*',
                'tt.level as type_level',
                'tt.name as type_name',
                'pt.code as parent_code_value',
                'g.uuid as geom_uuid',
                'g.geometry_type',
                'g.srid',
                'g.area_sqkm as area_km2',
                'g.perimeter_km as perimeter_m',
                DB::raw("CASE WHEN g.quality_status IN ('VALID','VERIFIED','OFFICIAL') THEN 1 ELSE 0 END as geom_is_valid"),
                DB::raw("CASE WHEN g.quality_status IN ('INVALID','WARNING') THEN g.validation_details::text END as geom_validation_error"),
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
            ->leftJoin('core.territory_types as tt', 't.territory_type_id', '=', 'tt.id')
            ->leftJoin('core.territories as pt', 't.parent_id', '=', 'pt.id')
            ->leftJoin('core.geometries as g', 't.geometry_id', '=', 'g.id')
            ->where('t.uuid', $uuid)
            ->select([
                't.*',
                'tt.level as type_level',
                'tt.name as type_name',
                'pt.code as parent_code_value',
                'g.uuid as geom_uuid',
                'g.geometry_type',
                'g.srid',
                'g.area_sqkm as area_km2',
                'g.perimeter_km as perimeter_m',
                DB::raw("CASE WHEN g.quality_status IN ('VALID','VERIFIED','OFFICIAL') THEN 1 ELSE 0 END as geom_is_valid"),
                DB::raw("CASE WHEN g.quality_status IN ('INVALID','WARNING') THEN g.validation_details::text END as geom_validation_error"),
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
            ->leftJoin('core.territory_types as tt', 't.territory_type_id', '=', 'tt.id')
            ->leftJoin('core.territories as pt', 't.parent_id', '=', 'pt.id')
            ->leftJoin('core.geometries as g', 't.geometry_id', '=', 'g.id')
            ->where('t.code', $code->getValue())
            ->select([
                't.*',
                'tt.level as type_level',
                'tt.name as type_name',
                'pt.code as parent_code_value',
                'g.uuid as geom_uuid',
                'g.geometry_type',
                'g.srid',
                'g.area_sqkm as area_km2',
                'g.perimeter_km as perimeter_m',
                DB::raw("CASE WHEN g.quality_status IN ('VALID','VERIFIED','OFFICIAL') THEN 1 ELSE 0 END as geom_is_valid"),
                DB::raw("CASE WHEN g.quality_status IN ('INVALID','WARNING') THEN g.validation_details::text END as geom_validation_error"),
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
            ->join('core.territory_types as tt', 't.territory_type_id', '=', 'tt.id')
            ->leftJoin('core.territories as pt', 't.parent_id', '=', 'pt.id')
            ->leftJoin('core.geometries as g', 't.geometry_id', '=', 'g.id')
            ->where('tt.level', $level->getLevel())
            ->select([
                't.*',
                'tt.level as type_level',
                'tt.name as type_name',
                'pt.code as parent_code_value',
                'g.uuid as geom_uuid',
                'g.geometry_type',
                'g.srid',
                'g.area_sqkm as area_km2',
                'g.perimeter_km as perimeter_m',
                DB::raw("CASE WHEN g.quality_status IN ('VALID','VERIFIED','OFFICIAL') THEN 1 ELSE 0 END as geom_is_valid"),
                DB::raw("CASE WHEN g.quality_status IN ('INVALID','WARNING') THEN g.validation_details::text END as geom_validation_error"),
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
            ->join('core.territory_types as tt', 't.territory_type_id', '=', 'tt.id')
            ->join('core.territories as pt', 't.parent_id', '=', 'pt.id')
            ->leftJoin('core.geometries as g', 't.geometry_id', '=', 'g.id')
            ->where('pt.code', $parentCode->getValue())
            ->select([
                't.*',
                'tt.level as type_level',
                'tt.name as type_name',
                'pt.code as parent_code_value',
                'g.uuid as geom_uuid',
                'g.geometry_type',
                'g.srid',
                'g.area_sqkm as area_km2',
                'g.perimeter_km as perimeter_m',
                DB::raw("CASE WHEN g.quality_status IN ('VALID','VERIFIED','OFFICIAL') THEN 1 ELSE 0 END as geom_is_valid"),
                DB::raw("CASE WHEN g.quality_status IN ('INVALID','WARNING') THEN g.validation_details::text END as geom_validation_error"),
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
        // core.fn_get_territory_ancestors attend l'identifiant BIGINT du
        // territoire : le code est d'abord résolu (le passer tel quel provoquait
        // une erreur de cast PostgreSQL sur les appels hiérarchie/parents).
        $territory = $this->findByCode($childCode);
        if ($territory === null || $territory->getId() === null) {
            return [];
        }

        // La fonction renvoie une UNIQUE ligne JSONB (tableau d'ancêtres
        // {id, code, name, level, ...}) : elle doit être décodée, sinon
        // array_column retournait systématiquement [].
        $rows = DB::select('SELECT core.fn_get_territory_ancestors(?) AS tree', [$territory->getId()]);

        $tree = $rows[0]->tree ?? null;
        if (is_string($tree)) {
            $tree = json_decode($tree, true);
        }
        if (! is_array($tree) || $tree === []) {
            return [];
        }

        $ancestorCodes = array_column($tree, 'code');

        $rows = DB::table('core.territories as t')
            ->join('core.territory_types as tt', 't.territory_type_id', '=', 'tt.id')
            ->leftJoin('core.territories as pt', 't.parent_id', '=', 'pt.id')
            ->leftJoin('core.geometries as g', 't.geometry_id', '=', 'g.id')
            ->whereIn('t.code', $ancestorCodes)
            ->select([
                't.*',
                'tt.level as type_level',
                'tt.name as type_name',
                'pt.code as parent_code_value',
                'g.uuid as geom_uuid',
                'g.geometry_type',
                'g.srid',
                'g.area_sqkm as area_km2',
                'g.perimeter_km as perimeter_m',
                DB::raw("CASE WHEN g.quality_status IN ('VALID','VERIFIED','OFFICIAL') THEN 1 ELSE 0 END as geom_is_valid"),
                DB::raw("CASE WHEN g.quality_status IN ('INVALID','WARNING') THEN g.validation_details::text END as geom_validation_error"),
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
            ->join('core.territory_types as tt', 't.territory_type_id', '=', 'tt.id')
            ->leftJoin('core.territories as pt', 't.parent_id', '=', 'pt.id')
            ->leftJoin('core.geometries as g', 't.geometry_id', '=', 'g.id');

        if ($level !== null) {
            $builder->where('tt.level', $level->getLevel());
        }

        $cleanQuery = trim($query);
        // CDC §85 : « mbour / M'bour » homogène — normalisation symétrique :
        // l'apostrophe est supprimée des deux côtés (colonne + requête).
        // M'bour -> Mbour, Mbour -> Mbour : les deux requêtes matchent identiquement.
        $normalizedQuery = str_replace("'", '', $cleanQuery);
        $builder->where(function ($q) use ($normalizedQuery) {
            $q->whereRaw("REPLACE(t.code, '''', '') ILIKE ?", ["%{$normalizedQuery}%"])
                ->orWhereRaw("REPLACE(t.name, '''', '') ILIKE ?", ["%{$normalizedQuery}%"])
                ->orWhereRaw("to_tsvector('french', public.immutable_unaccent(REPLACE(t.name, '''', ''))) @@ plainto_tsquery('french', public.immutable_unaccent(?))", [$normalizedQuery]);
        });

        $rows = $builder->select([
            't.*',
            'tt.level as type_level',
            'tt.name as type_name',
            'pt.code as parent_code_value',
            'g.uuid as geom_uuid',
            'g.geometry_type',
            'g.srid',
            'g.area_sqkm as area_km2',
            'g.perimeter_km as perimeter_m',
            DB::raw("CASE WHEN g.quality_status IN ('VALID','VERIFIED','OFFICIAL') THEN 1 ELSE 0 END as geom_is_valid"),
            DB::raw("CASE WHEN g.quality_status IN ('INVALID','WARNING') THEN g.validation_details::text END as geom_validation_error"),
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
        // Colonnes alignées sur le schéma réel core.territories (CDC §14) :
        // slug et level sont NOT NULL, quality_status vit dans core.geometries.
        $data = [
            'uuid' => $territory->getUuid(),
            'code' => $territory->getCode()->getValue(),
            'name' => $territory->getName(),
            'slug' => Str::slug($territory->getName()),
            'level' => $territory->getHierarchyLevel()->getLevel(),
            'code_ansd' => $territory->getAnsdCode(),
            'territory_type_id' => $territory->getTypeId(),
            'parent_id' => $territory->getParentId(),
            'population_census' => $territory->getPopulation(),
            'status' => $territory->getStatus()->value,
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
            ->join('core.territory_types as tt', 't.territory_type_id', '=', 'tt.id')
            ->where('tt.level', $level->getLevel())
            ->count();
    }

    /**
     * Filtre combiné des critères d'index (US-012).
     *
     * Le bbox est transmis en requête préparée : aucun critère n'est concaténé
     * dans le SQL (US-022 — pas d'injection par filtre).
     *
     * @return Builder
     */
    private function applyCriteria(
        ?HierarchyLevel $level,
        ?string $type,
        ?string $parentCode,
        ?string $status,
        ?string $search,
        ?BoundingBox $bbox
    ) {
        $builder = DB::table('core.territories as t')
            ->join('core.territory_types as tt', 't.territory_type_id', '=', 'tt.id')
            ->leftJoin('core.territories as pt', 't.parent_id', '=', 'pt.id')
            ->leftJoin('core.geometries as g', 't.geometry_id', '=', 'g.id');

        if ($level !== null) {
            $builder->where('tt.level', $level->getLevel());
        }

        if ($type !== null && trim($type) !== '') {
            $typeValue = trim($type);
            $builder->where(function ($q) use ($typeValue) {
                $q->where('tt.code', 'ILIKE', $typeValue)
                    ->orWhere('tt.name', 'ILIKE', $typeValue);
            });
        }

        if ($parentCode !== null && trim($parentCode) !== '') {
            $builder->where('pt.code', trim($parentCode));
        }

        if ($status !== null && trim($status) !== '') {
            $builder->where('t.status', strtoupper(trim($status)));
        }

        if ($search !== null && trim($search) !== '') {
            // CDC §85 : normalisation symétrique des apostrophes (comme search()).
            $normalizedQuery = str_replace("'", '', trim($search));
            $builder->where(function ($q) use ($normalizedQuery) {
                $q->whereRaw("REPLACE(t.code, '''', '') ILIKE ?", ["%{$normalizedQuery}%"])
                    ->orWhereRaw("REPLACE(t.name, '''', '') ILIKE ?", ["%{$normalizedQuery}%"])
                    ->orWhereRaw("to_tsvector('french', public.immutable_unaccent(REPLACE(t.name, '''', ''))) @@ plainto_tsquery('french', public.immutable_unaccent(?))", [$normalizedQuery]);
            });
        }

        if ($bbox !== null) {
            // Requête préparée : ST_MakeEnvelope reçoit les bornes en paramètres
            // liés, jamais interpolées (ST_Intersects + index GiST utilisés).
            $envelope = 'ST_MakeEnvelope(?, ?, ?, ?, 4326)';
            $bounds = [$bbox->getMinLon(), $bbox->getMinLat(), $bbox->getMaxLon(), $bbox->getMaxLat()];
            $builder
                ->whereRaw("g.geometry && {$envelope}", $bounds)
                ->whereRaw("ST_Intersects(g.geometry, {$envelope})", $bounds);
        }

        return $builder;
    }

    /**
     * Sélection de colonnes commune aux hydratations (joins déjà posés).
     *
     * @param  Builder  $builder
     * @return Builder
     */
    private function applyCommonSelect($builder)
    {
        return $builder->select([
            't.*',
            'tt.level as type_level',
            'tt.name as type_name',
            'pt.code as parent_code_value',
            'g.uuid as geom_uuid',
            'g.geometry_type',
            'g.srid',
            'g.area_sqkm as area_km2',
            'g.perimeter_km as perimeter_m',
            DB::raw("CASE WHEN g.quality_status IN ('VALID','VERIFIED','OFFICIAL') THEN 1 ELSE 0 END as geom_is_valid"),
            DB::raw("CASE WHEN g.quality_status IN ('INVALID','WARNING') THEN g.validation_details::text END as geom_validation_error"),
            DB::raw('ST_AsGeoJSON(g.geometry) as geojson'),
            DB::raw('ST_X(g.centroid) as centroid_lon'),
            DB::raw('ST_Y(g.centroid) as centroid_lat'),
            DB::raw('ST_XMin(g.bbox) as bbox_min_lon'),
            DB::raw('ST_YMin(g.bbox) as bbox_min_lat'),
            DB::raw('ST_XMax(g.bbox) as bbox_max_lon'),
            DB::raw('ST_YMax(g.bbox) as bbox_max_lat'),
        ]);
    }

    public function findByCriteria(
        ?HierarchyLevel $level = null,
        ?string $type = null,
        ?string $parentCode = null,
        ?string $status = null,
        ?string $search = null,
        ?BoundingBox $bbox = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $rows = $this->applyCommonSelect(
            $this->applyCriteria($level, $type, $parentCode, $status, $search, $bbox)
        )
            ->orderBy('t.name', 'asc')
            ->limit(max(1, $limit))
            ->offset(max(0, $offset))
            ->get();

        return $rows->map(fn ($row) => $this->hydrateTerritory($row))->all();
    }

    public function countByCriteria(
        ?HierarchyLevel $level = null,
        ?string $type = null,
        ?string $parentCode = null,
        ?string $status = null,
        ?string $search = null,
        ?BoundingBox $bbox = null
    ): int {
        return (int) $this
            ->applyCriteria($level, $type, $parentCode, $status, $search, $bbox)
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
            ansdCode: $row->code_ansd ?? null,
            hierarchyLevel: new HierarchyLevel((int) ($row->type_level ?? 0)),
            typeId: isset($row->type_id) ? (int) $row->type_id : null,
            typeName: $row->type_name ?? null,
            parentId: isset($row->parent_id) ? (int) $row->parent_id : null,
            parentCode: $parentCodeVo,
            geometry: $geometry,
            centroid: $centroidVo,
            boundingBox: $bboxVo,
            area: $areaVo,
            population: isset($row->population_census) ? (int) $row->population_census : null,
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
