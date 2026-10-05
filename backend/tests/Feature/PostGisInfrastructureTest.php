<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * US-021 — Vérifie que l'environnement de test est un vrai PostGIS.
 *
 * Ce test est le garde-fou principal : si la CI se lance sur une base sans
 * PostGIS, c'est ici que l'échec doit apparaître, explicitement, plutôt
 * que de contaminer tous les autres tests avec des erreurs SQL confuses.
 *
 * @group infrastructure
 */
class PostGisInfrastructureTest extends TestCase
{
    public function test_postgis_extension_is_available(): void
    {
        $this->assertPostGisAvailable();

        $version = $this->app['db']->connection()
            ->selectOne('SELECT PostGIS_Version() AS v');

        self::assertNotEmpty($version->v);
    }

    public function test_spatial_functions_are_executable(): void
    {
        $this->assertPostGisAvailable();

        // Les fonctions utilisées par l'ETL et l'API doivent toutes exister.
        $result = $this->app['db']->connection()->selectOne(
            'SELECT
                ST_IsValid(ST_GeomFromText(\'POLYGON((0 0, 1 0, 1 1, 0 1, 0 0))\', 4326)) AS is_valid,
                ST_SRID(ST_GeomFromText(\'POINT(1 1)\', 4326))                      AS srid,
                ST_GeometryType(ST_GeomFromText(\'POINT(1 1)\', 4326))              AS geom_type'
        );

        self::assertTrue((bool) $result->is_valid, 'Un polygone valide doit être accepté par ST_IsValid.');
        self::assertSame(4326, (int) $result->srid, 'Le SRID doit être préservé en EPSG:4326.');
        self::assertStringContainsString('POINT', (string) $result->geom_type);
    }

    /**
     * @dataProvider requiredSchemaProvider
     */
    public function test_required_schemas_exist(string $schema): void
    {
        $this->assertSchemaExists($schema);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function requiredSchemaProvider(): array
    {
        return [
            'raw' => ['raw'],
            'staging' => ['staging'],
            'core' => ['core'],
            'published' => ['published'],
            'audit' => ['audit'],
        ];
    }

    public function test_full_text_search_extensions_are_loaded(): void
    {
        // La recherche du §85 dépend de unaccent + pg_trgm + full-text français.
        $extensions = $this->app['db']->connection()->select(
            "SELECT extname FROM pg_extension WHERE extname IN ('unaccent', 'pg_trgm') ORDER BY extname"
        );

        $names = array_map(static fn ($row) => $row->extname, $extensions);

        self::assertContains('unaccent', $names, 'Extension unaccent requise par la recherche sans accent.');
        self::assertContains('pg_trgm', $names, 'Extension pg_trgm requise par la recherche approximative.');
    }
}
