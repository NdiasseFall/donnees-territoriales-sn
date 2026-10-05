-- ============================================================================
-- 03_CREATE_RAW_SCHEMA.SQL
-- Schema: raw
-- Ingestion brute des fichiers sources pour audit et rejeu
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. DATASET_IMPORTS (Sessions d'ingestion de fichiers bruts)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS raw.dataset_imports (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT uuid_generate_v4(),
    file_name VARCHAR(255) NOT NULL,
    file_format VARCHAR(50) NOT NULL, -- SHAPEFILE, GEOJSON, GEOPACKAGE, CSV, KML
    file_size_bytes BIGINT,
    checksum VARCHAR(128) NOT NULL,
    source_name VARCHAR(255),
    target_territory_type VARCHAR(50), -- REGION, DEPARTMENT, COMMUNE, etc.
    total_features INTEGER DEFAULT 0,
    status VARCHAR(50) NOT NULL DEFAULT 'UPLOADED', -- UPLOADED, PARSING, STAGED, FAILED, ARCHIVED
    error_message TEXT,
    metadata JSONB DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
);

CREATE INDEX IF NOT EXISTS idx_raw_imports_status ON raw.dataset_imports(status);
CREATE INDEX IF NOT EXISTS idx_raw_imports_checksum ON raw.dataset_imports(checksum);

-- ----------------------------------------------------------------------------
-- 2. IMPORT_FILES (Fichiers annexes associés, ex: .shp, .dbf, .prj, .shx)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS raw.import_files (
    id BIGSERIAL PRIMARY KEY,
    import_id BIGINT NOT NULL REFERENCES raw.dataset_imports(id) ON DELETE CASCADE,
    file_path TEXT NOT NULL,
    file_type VARCHAR(50) NOT NULL, -- shp, dbf, prj, shx, cpg, geojson
    file_size_bytes BIGINT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
);

CREATE INDEX IF NOT EXISTS idx_raw_files_import ON raw.import_files(import_id);

-- ----------------------------------------------------------------------------
-- 3. IMPORT_FEATURES (Objets bruts stockés avec leurs géométries et attributs)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS raw.import_features (
    id BIGSERIAL PRIMARY KEY,
    import_id BIGINT NOT NULL REFERENCES raw.dataset_imports(id) ON DELETE CASCADE,
    feature_index INTEGER NOT NULL,
    original_crs VARCHAR(100),
    raw_wkt TEXT,
    raw_geometry GEOMETRY(Geometry, 4326),
    properties JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
);

CREATE INDEX IF NOT EXISTS idx_raw_features_import_id ON raw.import_features(import_id);
CREATE INDEX IF NOT EXISTS idx_raw_features_geom ON raw.import_features USING GIST(raw_geometry);
CREATE INDEX IF NOT EXISTS idx_raw_features_props ON raw.import_features USING GIN(properties);
