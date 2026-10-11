-- ============================================================================
-- 08_ALIGN_DATASET_CATALOG_DDL.SQL
-- Phase 4 (US-015/US-018) — aligne le DDL catalogue sur le Domain existant.
--
-- Contexte : le contrôleur catalogue + EloquentDatasetRepository + DatasetModel
-- utilisent des colonnes absentes du DDL d'origine (02_create_core_schema.sql) :
--   core.datasets : category, license, tags, metadata, is_official
--   core.dataset_versions : les hydratations/save utilisent version_number,
--     feature_count, checksum_sha256, file_path, file_size_bytes,
--     validation_summary, file_size_bytes, published_at...
--
-- Idempotent et rejouable (IF NOT EXISTS / vérification information_schema),
-- exécuté APRÈS le 02 dans init_all.sql, docker-compose (initdb) et la CI.
-- ============================================================================

-- 1. core.datasets : colonnes catalogue manquantes.
ALTER TABLE core.datasets
    ADD COLUMN IF NOT EXISTS category VARCHAR(100),
    ADD COLUMN IF NOT EXISTS license VARCHAR(255),
    ADD COLUMN IF NOT EXISTS tags JSONB NOT NULL DEFAULT '[]'::jsonb,
    ADD COLUMN IF NOT EXISTS metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
    ADD COLUMN IF NOT EXISTS is_official BOOLEAN NOT NULL DEFAULT FALSE;

CREATE INDEX IF NOT EXISTS idx_datasets_category ON core.datasets(category);

-- 2. core.dataset_versions : colonnes attendues par le Domain/hydratation.
--    Les colonnes historiques (version, record_count, checksum...) sont
--    conservées ; les nouvelles coexistent sans rupture.
ALTER TABLE core.dataset_versions
    ADD COLUMN IF NOT EXISTS version_number VARCHAR(50),
    ADD COLUMN IF NOT EXISTS feature_count INTEGER,
    ADD COLUMN IF NOT EXISTS checksum_sha256 VARCHAR(128),
    ADD COLUMN IF NOT EXISTS file_path TEXT,
    ADD COLUMN IF NOT EXISTS file_size_bytes BIGINT,
    ADD COLUMN IF NOT EXISTS validation_summary JSONB NOT NULL DEFAULT '{}'::jsonb,
    ADD COLUMN IF NOT EXISTS published_at TIMESTAMPTZ,
    ADD COLUMN IF NOT EXISTS description TEXT,
    ADD COLUMN IF NOT EXISTS created_by BIGINT,
    ADD COLUMN IF NOT EXISTS published_by BIGINT;

-- Backfill : aligne les nouvelles colonnes sur l'historique quand elles sont vides.
UPDATE core.dataset_versions SET version_number = version WHERE version_number IS NULL;
UPDATE core.dataset_versions SET feature_count = record_count WHERE feature_count IS NULL;
UPDATE core.dataset_versions SET checksum_sha256 = checksum WHERE checksum_sha256 IS NULL AND checksum IS NOT NULL;
UPDATE core.dataset_versions SET validation_summary = validation_report WHERE validation_summary = '{}'::jsonb AND validation_report IS NOT NULL AND validation_report <> '{}'::jsonb;

CREATE INDEX IF NOT EXISTS idx_dataset_versions_number ON core.dataset_versions(dataset_id, version_number);
