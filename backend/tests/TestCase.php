<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Classe de base de tous les tests du backend.
 *
 * US-021 : les tests géospatiaux s'exécutent sur une instance PostGIS réelle.
 * Aucune base SQLite ni mock spatial n'est utilisé — les fonctions PostGIS
 * (ST_IsValid, ST_Intersects, ST_SRID...) n'existent pas ailleurs.
 */
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Vérifie que la connexion cible expose bien PostGIS.
     *
     * À appeler dans le setUp() de tout test touchant la géométrie, afin
     * d'échouer avec un message explicite plutôt qu'avec une erreur SQL
     * obscure si la CI est mal configurée.
     */
    protected function assertPostGisAvailable(): void
    {
        $version = $this->app['db']->connection()->selectOne('SELECT PostGIS_Version() AS v');

        self::assertNotNull(
            $version->v ?? null,
            "PostGIS est requis pour ce test. Vérifiez DB_CONNECTION=pgsql et l'image postgis/postgis."
        );
    }

    /**
     * Vérifie qu'un schéma existe et est accessible.
     */
    protected function assertSchemaExists(string $schema): void
    {
        $exists = $this->app['db']->connection()->selectOne(
            'SELECT EXISTS (SELECT 1 FROM information_schema.schemata WHERE schema_name = ?) AS ok',
            [$schema]
        );

        self::assertTrue(
            (bool) ($exists->ok ?? false),
            sprintf('Schéma "%s" absent. Exécutez database/sql/init_all.sql.', $schema)
        );
    }
}
