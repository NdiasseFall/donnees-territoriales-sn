-- ============================================================================
-- INIT_ALL.SQL
-- Plateforme Nationale de Données Territoriales du Sénégal
-- Script maître d'initialisation complète de la base de données PostGIS
-- ============================================================================

\echo '--> [1/6] Initialisation des extensions et des 5 schémas PostGIS...'
\i 00_init_extensions_schemas.sql

\echo '--> [2/6] Création du schéma audit...'
\i 01_create_audit_schema.sql

\echo '--> [3/6] Création du schéma core (référentiel pivot national)...'
\i 02_create_core_schema.sql

\echo '--> [4/6] Création du schéma raw (ingestion brute des fichiers)...'
\i 03_create_raw_schema.sql

\echo '--> [5/6] Création du schéma staging (validation et contrôle qualité)...'
\i 04_create_staging_schema.sql

\echo '--> [6/6] Création du schéma published et fonctions spatiales avancées...'
\i 05_create_published_schema.sql
\i 06_spatial_helper_functions.sql

\echo '--> Initialisation PostGIS terminée avec succès !'
