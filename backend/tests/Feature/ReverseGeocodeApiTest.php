<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * US-014 — Reverse geocoding & indicateur de confiance (CDC §44).
 *
 * Critères BDD verrouillés :
 *  1. Point dans l'emprise → hiérarchie complète country → … → locality.
 *  2. Point hors emprise / dans l'océan → 404 explicite (jamais de commune
 *     arbitraire), enveloppe d'erreur §50.
 *  3. Point proche d'une frontière → tolérance gérée + indicateur de confiance
 *     (high / medium / low) dérivé de la distance à la limite du territoire
 *     le plus fin.
 *
 * Fixtures PostGIS réelles (EPSG:4326) en transaction : aucun mock spatial
 * (US-021). Le préfixe « SN-RGT » isole les fixtures du référentiel réel.
 */
class ReverseGeocodeApiTest extends TestCase
{
    use DatabaseTransactions;

    private const COUNTRY_CODE = 'SN-RGT';

    private const COMMUNE_CODE = 'SN-RGT-CM';

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertPostGisAvailable();
        $this->seedFixtureHierarchy();
    }

    private function seedFixtureHierarchy(): void
    {
        DB::delete("DELETE FROM published.territories WHERE code LIKE 'SN-RGT%'");
        DB::delete("DELETE FROM core.territories WHERE code LIKE 'SN-RGT%'");

        $countryType = (int) DB::selectOne("SELECT id FROM core.territory_types WHERE code = 'COUNTRY'")->id;
        $communeType = (int) DB::selectOne("SELECT id FROM core.territory_types WHERE code = 'COMMUNE'")->id;

        // Pays large : -18..-11 / 12..13.5 (empreinte réaliste du Sénégal).
        $countryId = $this->insertTerritory(
            self::COUNTRY_CODE, 'Sénégal Test', 0, $countryType, null,
            -18.0, 12.0, -11.0, 13.5
        );

        // Commune carrée -16.5..-16.0 / 14.5..15.0 (centre ≈ -16.25, 14.75).
        $this->insertTerritory(
            self::COMMUNE_CODE, 'Commune Test', 4, $communeType, $countryId,
            -16.5, 14.5, -16.0, 15.0
        );

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
            WHERE t.code LIKE 'SN-RGT%'
        ");
    }

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
            $minLon, $minLat, $maxLon, $maxLat
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
            [$parentId, $typeId, (int) $geometry->id, $code, $name, strtolower(str_replace('_', '-', $code)), $level]
        );

        return (int) $territory->id;
    }

    public function test_point_inside_footprint_returns_full_hierarchy_with_confidence(): void
    {
        // Centre de la commune (-16.25, 14.75) : bien à l'intérieur.
        $response = $this->getJson('/api/v1/spatial/reverse-geocode?lng=-16.25&lat=14.75');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'query' => ['longitude', 'latitude'],
            'confidence',
            'boundary_distance_m',
            'hierarchy' => ['country', 'region', 'department', 'arrondissement', 'commune', 'district_or_village', 'hamlet'],
        ]);

        // Critère 1 : la commune est résolue.
        self::assertSame(self::COMMUNE_CODE, $response->json('hierarchy.commune.code'));
        // Critère 3 : un point central porte un indicateur de confiance exploitable.
        self::assertContains($response->json('confidence'), ['high', 'medium', 'low']);
        self::assertGreaterThan(0.0, (float) $response->json('boundary_distance_m'));
    }

    public function test_point_outside_footprint_returns_404_not_arbitrary_commune(): void
    {
        // Pleine océane Atlantique : hors de toute géométrie de fixture.
        $response = $this->getJson('/api/v1/spatial/reverse-geocode?lng=-25.0&lat=10.0');

        // Critère 2 : 404 explicite — jamais un 200 avec une commune arbitraire.
        $response->assertStatus(404);
        $response->assertJsonStructure(['error' => ['code', 'message', 'request_id']]);
        self::assertSame('REVERSE_GEOCODE_OUTSIDE_TERRITORY', $response->json('error.code'));
    }

    public function test_point_near_boundary_has_lower_confidence_than_central_point(): void
    {
        // Point quasi collé à la frontière ouest de la commune (-16.5, 14.75) :
        // ~quelques mètres du bord → confiance « low ».
        $near = $this->getJson('/api/v1/spatial/reverse-geocode?lng=-16.4995&lat=14.75');
        $near->assertOk();
        self::assertSame('low', $near->json('confidence'), 'Un point collé à la frontière doit avoir une confiance faible.');
        self::assertLessThan(100.0, (float) $near->json('boundary_distance_m'));

        // Point central → confiance plus élevée que celle du point frontalier.
        $central = $this->getJson('/api/v1/spatial/reverse-geocode?lng=-16.25&lat=14.75');
        $central->assertOk();
        $confidenceRank = ['low' => 0, 'medium' => 1, 'high' => 2];
        self::assertGreaterThan(
            $confidenceRank[$near->json('confidence')],
            $confidenceRank[$central->json('confidence')],
            'Un point central doit être plus fiable qu\'un point proche d\'une frontière.'
        );
    }
}
