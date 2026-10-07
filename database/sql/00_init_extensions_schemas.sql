-- ============================================================================
-- 00_INIT_EXTENSIONS_SCHEMAS.SQL
-- Plateforme Nationale de Données Territoriales du Sénégal
-- Initialisation des extensions PostGIS et création des schémas d'isolation
-- ============================================================================

-- 1. Extensions requises
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "unaccent";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
CREATE EXTENSION IF NOT EXISTS "postgis";
CREATE EXTENSION IF NOT EXISTS "postgis_topology";

-- 1.1 Wrapper IMMUTABLE autour de unaccent() (STABLE) : indispensable pour
--     toute expression d'index (PG refuse les fonctions STABLE dans un index)
--     et garantit l'indexation de la recherche plein-texte sans accents (§85).
--     Requêtes et index doivent passer par cette fonction pour être alignés.
CREATE OR REPLACE FUNCTION public.immutable_unaccent(p_text text)
RETURNS text
LANGUAGE sql
IMMUTABLE
PARALLEL SAFE
RETURNS NULL ON NULL INPUT
AS $$ SELECT public.unaccent(p_text) $$;

-- 2. Création des 5 schémas d'architecture géospatiale
CREATE SCHEMA IF NOT EXISTS raw;
CREATE SCHEMA IF NOT EXISTS staging;
CREATE SCHEMA IF NOT EXISTS core;
CREATE SCHEMA IF NOT EXISTS published;
CREATE SCHEMA IF NOT EXISTS audit;

-- Commentaires de documentation sur les schémas
COMMENT ON SCHEMA raw IS 'Zone de réception des données brutes importées (Shapefiles, GeoJSON, CSV, GeoPackage) pour traçabilité intégrale.';
COMMENT ON SCHEMA staging IS 'Zone de transformation, normalisation des codes/noms, reprojection SRID 4326 et validation topologique.';
COMMENT ON SCHEMA core IS 'Référentiel géospatial pivot officiel interne du Sénégal.';
COMMENT ON SCHEMA published IS 'Données officielles validées, simplifiées et indexées pour exposition publique et API/Web GIS.';
COMMENT ON SCHEMA audit IS 'Journalisation immuable de l’ensemble des opérations, modifications et validations.';
