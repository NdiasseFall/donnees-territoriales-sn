<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * US-012 — Endpoints territoires (CDC §35-41, §50, RFC 7946).
 *
 * Fixtures PostGIS réelles (EPSG:4326) insérées puis annulées par transaction :
 * aucun mock spatial (US-021). Le préfixe « SN-TST » isole les fixtures du
 * référentiel réel, dont les codes canoniques relèvent de la décision D-3.
 */
class TerritoryHierarchyApiTest extends TestCase
{
    use DatabaseTransactions;

    private const COUNTRY_CODE = 'SN-TST';

    private const REGION_CODE = 'SN-TST-RG';

    private const DEPARTMENT_CODE = 'SN-TST-RG-DP';

    private const COMMUNE_CODE = 'SN-TST-RG-DP-CM';

    private const UNKNOWN_CODE = 'SN-TST-999-ZZ';

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertPostGisAvailable();
        $this->seedFixtureHierarchy();
    }

    /**
     * Hiérarchie complète Pays → Région → Département → Commune (CDC §39),
     * avec géométries polygonales emboîtées et miroir dans published.* (P5).
     */
    private function seedFixtureHierarchy(): void
    {
        DB::delete("DELETE FROM published.territories WHERE code LIKE 'SN-TST%'");
        DB::delete("DELETE FROM core.territories WHERE code LIKE 'SN-TST%'");

        $typeIds = [];
        foreach (['COUNTRY', 'REGION', 'DEPARTMENT', 'COMMUNE'] as $typeCode) {
            $row = DB::selectOne('SELECT id FROM core.territory_types WHERE code = ?', [$typeCode]);
            self::assertNotNull(
                $row,
                "Type territorial {$typeCode} absent — appliquez database/sql/02_create_core_schema.sql."
            );
            $typeIds[$typeCode] = (int) $row->id;
        }

        $countryId = $this->insertTerritory(
            self::COUNTRY_CODE, 'Sénégal Fixture', 0, $typeIds['COUNTRY'], null,
            -18.0, 12.0, -11.0, 13.5
        );
        $regionId = $this->insertTerritory(
            self::REGION_CODE, 'Région Fixture', 1, $typeIds['REGION'], $countryId,
            -17.0, 14.0, -16.0, 15.0
        );
        $departmentId = $this->insertTerritory(
            self::DEPARTMENT_CODE, 'Département Fixture', 2, $typeIds['DEPARTMENT'], $regionId,
            -16.9, 14.4, -16.3, 14.8
        );
        $this->insertTerritory(
            self::COMMUNE_CODE, 'Commune Fixture Mbour', 4, $typeIds['COMMUNE'], $departmentId,
            -16.8, 14.5, -16.6, 14.7
        );

        // La plateforme publique ne lit que published.* (principe P5) :
        // le miroir alimente /geometry et /geojson sans casser le parcours prod.
        DB::statement("
            INSERT INTO published.territories
                (id, code, name, slug, type_code, type_name, level, parent_code,
                 geometry, simplified_geometry, centroid, bbox)
            SELECT t.id, t.code, t.name, lower(t.code), tt.code, tt.name, tt.level, p.code,
                   g.geometry, g.simplified_geometry, g.centroid, g.bbox
            FROM core.territories t
            JOIN core.territory_types tt ON tt.id = t.territory_type_id
            JOIN core.geometries g ON g.id = t.geometry_id
            LEFT JOIN core.territories p ON p.id = t.parent_id
            WHERE t.code LIKE 'SN-TST%'
        ");
    }

    /**
     * Insertion d'une géométrie polygonale (le trigger core recalcule centroid,
     * bbox et qualité) puis du territoire correspondant. Retourne son id.
     */
    private function insertTerritory(
        string $code,
        string $name,
        int $level,
        int $typeId,
        ?int $parentId,
        float $minLon,
        float $minLat,
        float $maxLon,
        float $maxLat
    ): int {
        $wkt = sprintf(
            'POLYGON((%1$f %2$f, %3$f %2$f, %3$f %4$f, %1$f %4$f, %1$f %2$f))',
            $minLon,
            $minLat,
            $maxLon,
            $maxLat
        );

        $geometry = DB::selectOne(
            "INSERT INTO core.geometries (geometry_type, srid, geometry)
             VALUES ('Polygon', 4326, ST_SetSRID(ST_GeomFromText(?), 4326))
             RETURNING id",
            [$wkt]
        );

        $territory = DB::selectOne(
            "INSERT INTO core.territories
                (parent_id, territory_type_id, geometry_id, code, name, slug, level, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'ACTIVE')
             RETURNING id",
            [
                $parentId,
                $typeId,
                (int) $geometry->id,
                $code,
                $name,
                strtolower(str_replace('_', '-', $code)),
                $level,
            ]
        );

        return (int) $territory->id;
    }

    public function test_code_route_returns_territory_with_identity_fields(): void
    {
        $response = $this->getJson('/api/v1/territories/code/'.self::COMMUNE_CODE);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'uuid',
                'code',
                'name',
                'hierarchy' => ['level', 'label', 'parent_code'],
                'status',
            ],
        ]);
        self::assertSame(self::COMMUNE_CODE, $response->json('data.code'));
        self::assertSame(4, (int) $response->json('data.hierarchy.level'));
    }

    public function test_code_route_returns_structured_error_for_unknown_code(): void
    {
        $this->getJson('/api/v1/territories/code/'.self::UNKNOWN_CODE)
            ->assertStatus(404)
            ->assertJsonStructure(['error' => ['code', 'message']]);
    }

    public function test_children_returns_direct_children_only(): void
    {
        $response = $this->getJson('/api/v1/territories/'.self::REGION_CODE.'/children');

        $response->assertOk();
        $codes = array_column($response->json('data'), 'code');

        self::assertContains(self::DEPARTMENT_CODE, $codes);
        self::assertNotContains(self::COUNTRY_CODE, $codes, 'Le parent ne doit pas figurer parmi les enfants.');
        self::assertNotContains(self::COMMUNE_CODE, $codes, 'Seuls les enfants directs sont retournés.');
    }

    public function test_children_returns_structured_error_for_unknown_code(): void
    {
        $this->getJson('/api/v1/territories/'.self::UNKNOWN_CODE.'/children')
            ->assertStatus(404)
            ->assertJsonStructure(['error' => ['code', 'message']]);
    }

    public function test_parents_returns_full_chain_ordered_from_country(): void
    {
        $response = $this->getJson('/api/v1/territories/'.self::COMMUNE_CODE.'/parents');

        $response->assertOk();
        $codes = array_column($response->json('data'), 'code');

        self::assertSame(
            [self::COUNTRY_CODE, self::REGION_CODE, self::DEPARTMENT_CODE],
            $codes,
            'CDC §39 : la chaîne va du pays vers le parent direct, sans le territoire lui-même.'
        );
    }

    public function test_parents_returns_structured_error_for_unknown_code(): void
    {
        $this->getJson('/api/v1/territories/'.self::UNKNOWN_CODE.'/parents')
            ->assertStatus(404)
            ->assertJsonStructure(['error' => ['code', 'message']]);
    }

    public function test_geometry_endpoint_serves_rfc7946_feature(): void
    {
        $response = $this->get('/api/v1/territories/'.self::COMMUNE_CODE.'/geometry');

        $response->assertOk();

        // Directive projet : GeoJSON servi en application/geo+json (CDC §34).
        self::assertStringContainsString(
            'application/geo+json',
            (string) $response->headers->get('Content-Type')
        );

        $feature = json_decode((string) $response->getContent(), true);

        self::assertIsArray($feature);
        self::assertSame('Feature', $feature['type'] ?? null, 'RFC 7946 : type = Feature.');
        self::assertArrayHasKey('geometry', $feature);
        self::assertArrayHasKey('properties', $feature);
        self::assertArrayNotHasKey('crs', $feature, 'RFC 7946 interdit la clé "crs".');
        self::assertNotEmpty($feature['geometry']['type'] ?? null);
        self::assertNotEmpty($feature['geometry']['coordinates'] ?? null);
    }

    public function test_geometry_endpoint_returns_structured_error_for_unknown_code(): void
    {
        $this->get('/api/v1/territories/'.self::UNKNOWN_CODE.'/geometry')
            ->assertStatus(404);
    }

    public function test_map_endpoint_returns_section_41_payload(): void
    {
        $response = $this->getJson('/api/v1/territories/'.self::DEPARTMENT_CODE.'/map');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'geometry',
                'bbox',
                'centroid',
                'properties' => ['code', 'name', 'level', 'status'],
                'children',
            ],
        ]);

        $children = array_column($response->json('data.children'), 'code');
        self::assertContains(self::COMMUNE_CODE, $children);
    }

    public function test_map_endpoint_returns_structured_error_for_unknown_code(): void
    {
        $this->getJson('/api/v1/territories/'.self::UNKNOWN_CODE.'/map')
            ->assertStatus(404)
            ->assertJsonStructure(['error' => ['code', 'message']]);
    }

    public function test_index_filters_by_type(): void
    {
        $response = $this->getJson('/api/v1/territories?type=COMMUNE');

        $response->assertOk();

        foreach ($response->json('data') as $item) {
            self::assertSame(
                4,
                (int) $item['hierarchy']['level'],
                'Le filtre type=COMMUNE ne doit retourner que des communes (niveau 4).'
            );
        }
    }

    public function test_index_rejects_unknown_status_filter(): void
    {
        $this->getJson('/api/v1/territories?status=BOGUS')->assertStatus(422);
    }

    public function test_index_search_filter_returns_matching_names(): void
    {
        $response = $this->getJson('/api/v1/territories?search=Fixture');

        $response->assertOk();

        $codes = array_column($response->json('data'), 'code');
        self::assertContains(self::COMMUNE_CODE, $codes);
    }

    public function test_index_bbox_filter_uses_prepared_spatial_query(): void
    {
        $response = $this->getJson('/api/v1/territories?bbox=-16.9,14.4,-16.3,14.8');

        $response->assertOk();

        $codes = array_column($response->json('data'), 'code');
        self::assertContains(self::COMMUNE_CODE, $codes);
        self::assertNotContains(
            self::COUNTRY_CODE,
            $codes,
            'Le pays est hors emprise : ST_Intersects doit l\'exclure.'
        );
    }

    public function test_index_rejects_malformed_bbox(): void
    {
        // Un bbox non numérique ne doit jamais atteindre PostGIS (422).
        $this->getJson('/api/v1/territories?bbox=abc,def,ghi,jkl')->assertStatus(422);
    }

    public function test_index_rejects_inverted_bbox_without_server_error(): void
    {
        // Format valide mais bornes inversées : erreur structurée, jamais un 500.
        $this->getJson('/api/v1/territories?bbox=-16,14,-17,15')
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['code', 'message']]);
    }

    public function test_index_supports_page_and_per_page_pagination(): void
    {
        $response = $this->getJson('/api/v1/territories?level=4&page=1&per_page=2');

        $response->assertOk();
        self::assertLessThanOrEqual(2, count($response->json('data')));
        self::assertSame(1, (int) $response->json('meta.page'));
        self::assertSame(2, (int) $response->json('meta.limit'));
        self::assertIsInt($response->json('meta.total'));
    }

    public function test_hierarchy_endpoint_returns_ancestors_and_children(): void
    {
        $response = $this->getJson('/api/v1/territories/'.self::COMMUNE_CODE.'/hierarchy');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => ['territory', 'ancestors', 'children'],
        ]);
    }
}
