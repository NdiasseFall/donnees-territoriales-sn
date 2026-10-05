-- ============================================================================
-- 02_CREATE_CORE_SCHEMA.SQL
-- Schema: core
-- Modèle pivot officiel de données territoriales et géospatiales du Sénégal
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. ORGANIZATIONS (Producteurs / Gestionnaires de données)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS core.organizations (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT uuid_generate_v4(),
    name VARCHAR(255) NOT NULL,
    acronym VARCHAR(50),
    type VARCHAR(100) NOT NULL DEFAULT 'GOVERNMENT', -- GOVERNMENT, AGENCY, LOCAL_AUTHORITY, RESEARCH, PARTNER
    email VARCHAR(255),
    phone VARCHAR(50),
    website VARCHAR(255),
    status VARCHAR(50) NOT NULL DEFAULT 'ACTIVE', -- ACTIVE, INACTIVE
    created_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
);

CREATE INDEX IF NOT EXISTS idx_org_acronym ON core.organizations(acronym);
CREATE INDEX IF NOT EXISTS idx_org_status ON core.organizations(status);

-- ----------------------------------------------------------------------------
-- 2. SOURCES (Sources d'acquisition)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS core.sources (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT uuid_generate_v4(),
    organization_id BIGINT NOT NULL REFERENCES core.organizations(id) ON DELETE RESTRICT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    source_type VARCHAR(100) NOT NULL DEFAULT 'OFFICIAL_CADASTRE', -- OFFICIAL_CADASTRE, CENSUS, SURVEY, OPEN_DATA, PARTNER_FEED
    url TEXT,
    license VARCHAR(255) NOT NULL DEFAULT 'RESTRICTED', -- PUBLIC_DOMAIN, CC-BY, CC-BY-SA, RESTRICTED, INTERNAL, CONFIDENTIAL
    contact TEXT,
    acquired_at DATE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
);

CREATE INDEX IF NOT EXISTS idx_sources_org_id ON core.sources(organization_id);

-- ----------------------------------------------------------------------------
-- 3. DATASETS (Jeux de données géographiques)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS core.datasets (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT uuid_generate_v4(),
    source_id BIGINT NOT NULL REFERENCES core.sources(id) ON DELETE RESTRICT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    dataset_type VARCHAR(100) NOT NULL, -- ADMINISTRATIVE_BOUNDARIES, LOCALITIES, INFRASTRUCTURE, HEALTH, EDUCATION
    geometry_type VARCHAR(50) NOT NULL, -- MultiPolygon, Polygon, Point, MultiPoint, LineString, MultiLineString
    access_level VARCHAR(50) NOT NULL DEFAULT 'PUBLIC', -- PUBLIC, REGISTERED, RESTRICTED, INTERNAL, CONFIDENTIAL
    status VARCHAR(50) NOT NULL DEFAULT 'ACTIVE', -- ACTIVE, ARCHIVED, DEPRECATED
    created_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
);

CREATE INDEX IF NOT EXISTS idx_datasets_slug ON core.datasets(slug);
CREATE INDEX IF NOT EXISTS idx_datasets_type ON core.datasets(dataset_type);
CREATE INDEX IF NOT EXISTS idx_datasets_access ON core.datasets(access_level);

-- ----------------------------------------------------------------------------
-- 4. DATASET_VERSIONS (Versionnement formel des jeux de données)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS core.dataset_versions (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT uuid_generate_v4(),
    dataset_id BIGINT NOT NULL REFERENCES core.datasets(id) ON DELETE RESTRICT,
    version VARCHAR(50) NOT NULL, -- e.g. '1.0', '2026.1'
    release_date DATE NOT NULL DEFAULT CURRENT_DATE,
    effective_date DATE,
    file_path TEXT,
    checksum VARCHAR(128), -- SHA-256
    record_count INTEGER NOT NULL DEFAULT 0,
    status VARCHAR(50) NOT NULL DEFAULT 'draft', -- draft, processing, review, validated, published, deprecated, rejected
    validation_report JSONB DEFAULT '{}'::jsonb,
    notes TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
    CONSTRAINT uq_dataset_version UNIQUE(dataset_id, version)
);

CREATE INDEX IF NOT EXISTS idx_dataset_versions_status ON core.dataset_versions(status);

-- ----------------------------------------------------------------------------
-- 5. TERRITORY_TYPES (Niveaux de découpage hiérarchique)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS core.territory_types (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL, -- COUNTRY, REGION, DEPARTMENT, ARRONDISSEMENT, COMMUNE, LOCALITY, QUARTER, VILLAGE, HAMLET
    name VARCHAR(100) NOT NULL, -- Pays, Région, Département, Arrondissement, Commune, Localité, Quartier, Village, Hameau
    level INTEGER NOT NULL, -- 0 à 6
    description TEXT,
    is_spatial BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
);

CREATE INDEX IF NOT EXISTS idx_territory_types_level ON core.territory_types(level);

-- Insertion des types de territoires canoniques du Sénégal
INSERT INTO core.territory_types (code, name, level, description, is_spatial)
VALUES
    ('COUNTRY', 'Pays', 0, 'Territoire national souverain du Sénégal', true),
    ('REGION', 'Région', 1, 'Collectivité territoriale et circonscription administrative régionale', true),
    ('DEPARTMENT', 'Département', 2, 'Circonscription administrative départementale', true),
    ('ARRONDISSEMENT', 'Arrondissement', 3, 'Circonscription administrative d’arrondissement', true),
    ('COMMUNE', 'Commune', 4, 'Collectivité territoriale de base (commune urbaine / rurale)', true),
    ('LOCALITY', 'Localité', 5, 'Entité humaine ou centre de peuplement', true),
    ('QUARTER', 'Quartier', 6, 'Division urbaine intra-communale', true),
    ('VILLAGE', 'Village', 6, 'Établissement humain rural traditionnel', true),
    ('HAMLET', 'Hameau', 6, 'Sous-ensemble rattaché à un village', true)
ON CONFLICT (code) DO NOTHING;

-- ----------------------------------------------------------------------------
-- 6. GEOMETRIES (Table pivot de stockage des primitives PostGIS)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS core.geometries (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT uuid_generate_v4(),
    source_version_id BIGINT REFERENCES core.dataset_versions(id) ON DELETE SET NULL,
    geometry_type VARCHAR(50) NOT NULL, -- MultiPolygon, Point, etc.
    srid INTEGER NOT NULL DEFAULT 4326,
    geometry GEOMETRY(Geometry, 4326) NOT NULL,
    simplified_geometry GEOMETRY(Geometry, 4326),
    centroid GEOMETRY(Point, 4326),
    bbox GEOMETRY(Polygon, 4326),
    area_sqkm NUMERIC(16, 4), -- en kilomètres carrés
    perimeter_km NUMERIC(16, 4), -- en kilomètres
    quality_status VARCHAR(50) NOT NULL DEFAULT 'VALID', -- UNKNOWN, VALID, WARNING, INVALID, VERIFIED, OFFICIAL
    validation_score NUMERIC(5, 2) DEFAULT 100.00,
    validation_details JSONB DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
    CONSTRAINT chk_geometry_srid CHECK (ST_SRID(geometry) = 4326)
);

-- Index spatiaux GiST sur géométries
CREATE INDEX IF NOT EXISTS idx_core_geometries_geom ON core.geometries USING GIST (geometry);
CREATE INDEX IF NOT EXISTS idx_core_geometries_simplified ON core.geometries USING GIST (simplified_geometry);
CREATE INDEX IF NOT EXISTS idx_core_geometries_centroid ON core.geometries USING GIST (centroid);
CREATE INDEX IF NOT EXISTS idx_core_geometries_bbox ON core.geometries USING GIST (bbox);
CREATE INDEX IF NOT EXISTS idx_core_geometries_quality ON core.geometries (quality_status);

-- ----------------------------------------------------------------------------
-- 7. TERRITORIES (Entité centrale territoriale du Sénégal)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS core.territories (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT uuid_generate_v4(),
    parent_id BIGINT REFERENCES core.territories(id) ON DELETE SET NULL,
    territory_type_id BIGINT NOT NULL REFERENCES core.territory_types(id) ON DELETE RESTRICT,
    geometry_id BIGINT UNIQUE REFERENCES core.geometries(id) ON DELETE SET NULL,
    source_version_id BIGINT REFERENCES core.dataset_versions(id) ON DELETE SET NULL,
    code VARCHAR(50) UNIQUE NOT NULL, -- Code officiel unique (ex: SN-DK, SN-TH-MB, etc.)
    code_ansd VARCHAR(50), -- Code officiel ANSD
    code_anat VARCHAR(50), -- Code officiel ANAT
    name VARCHAR(255) NOT NULL,
    official_name VARCHAR(255),
    slug VARCHAR(255) NOT NULL,
    level INTEGER NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'ACTIVE', -- ACTIVE, INACTIVE, PENDING, DISPUTED, HISTORICAL
    country_code CHAR(2) NOT NULL DEFAULT 'SN',
    area_sqkm NUMERIC(16, 4),
    population_census BIGINT,
    metadata JSONB DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
);

-- Index sur core.territories
CREATE INDEX IF NOT EXISTS idx_territories_parent_id ON core.territories(parent_id);
CREATE INDEX IF NOT EXISTS idx_territories_type_id ON core.territories(territory_type_id);
CREATE INDEX IF NOT EXISTS idx_territories_level ON core.territories(level);
CREATE INDEX IF NOT EXISTS idx_territories_status ON core.territories(status);
CREATE INDEX IF NOT EXISTS idx_territories_slug ON core.territories(slug);
CREATE INDEX IF NOT EXISTS idx_territories_code_ansd ON core.territories(code_ansd);
CREATE INDEX IF NOT EXISTS idx_territories_code_anat ON core.territories(code_anat);

-- Index de recherche plein texte (GIN avec unaccent et français)
CREATE INDEX IF NOT EXISTS idx_territories_name_trgm ON core.territories USING GIN (name gin_trgm_ops);
CREATE INDEX IF NOT EXISTS idx_territories_name_tsv ON core.territories USING GIN (to_tsvector('french', unaccent(name)));
CREATE INDEX IF NOT EXISTS idx_territories_metadata_gin ON core.territories USING GIN (metadata);

-- ----------------------------------------------------------------------------
-- 8. TERRITORY_RELATIONSHIPS (Historique et relations administratives n-aires)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS core.territory_relationships (
    id BIGSERIAL PRIMARY KEY,
    parent_territory_id BIGINT NOT NULL REFERENCES core.territories(id) ON DELETE CASCADE,
    child_territory_id BIGINT NOT NULL REFERENCES core.territories(id) ON DELETE CASCADE,
    relationship_type VARCHAR(50) NOT NULL DEFAULT 'ADMINISTRATIVE_PARENT', -- ADMINISTRATIVE_PARENT, HISTORICAL_SUCCESSOR, CUSTOMARY
    valid_from DATE,
    valid_to DATE,
    status VARCHAR(50) NOT NULL DEFAULT 'ACTIVE', -- ACTIVE, OBSOLETE, DISPUTED
    source_version_id BIGINT REFERENCES core.dataset_versions(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
    CONSTRAINT uq_parent_child_rel UNIQUE (parent_territory_id, child_territory_id, relationship_type)
);

CREATE INDEX IF NOT EXISTS idx_rel_parent ON core.territory_relationships(parent_territory_id);
CREATE INDEX IF NOT EXISTS idx_rel_child ON core.territory_relationships(child_territory_id);

-- ----------------------------------------------------------------------------
-- 9. TRIGGERS AUTOMATIQUES DE CALCUL GÉOSPATIAL (core.geometries)
-- ----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION core.fn_compute_geometry_attributes()
RETURNS TRIGGER AS $$
BEGIN
    -- Forcer le SRID 4326 si non défini
    IF ST_SRID(NEW.geometry) = 0 THEN
        NEW.geometry := ST_SetSRID(NEW.geometry, 4326);
    END IF;

    -- Validation topologique et réparation si nécessaire
    IF NOT ST_IsValid(NEW.geometry) THEN
        NEW.geometry := ST_MakeValid(NEW.geometry);
        NEW.quality_status := 'WARNING';
    END IF;

    -- Détermination du type de géométrie
    NEW.geometry_type := ST_GeometryType(NEW.geometry);

    -- Calcul du centroïde (Point garanti sur la surface si polygone)
    IF NEW.geometry_type IN ('ST_Polygon', 'ST_MultiPolygon') THEN
        NEW.centroid := ST_PointOnSurface(NEW.geometry);
        -- Calcul superficie en km² (sur ellipsoïde WGS84 via geography)
        NEW.area_sqkm := ROUND((ST_Area(NEW.geometry::geography) / 1000000.0)::numeric, 4);
        -- Calcul périmètre en km
        NEW.perimeter_km := ROUND((ST_Perimeter(NEW.geometry::geography) / 1000.0)::numeric, 4);
        -- Simplification topologique pour zoom rapide (tolérance ~100m)
        NEW.simplified_geometry := ST_SimplifyPreserveTopology(NEW.geometry, 0.001);
    ELSEIF NEW.geometry_type IN ('ST_Point', 'ST_MultiPoint') THEN
        NEW.centroid := ST_Centroid(NEW.geometry);
        NEW.area_sqkm := 0.0;
        NEW.perimeter_km := 0.0;
        NEW.simplified_geometry := NEW.geometry;
    ELSE
        NEW.centroid := ST_Centroid(NEW.geometry);
        NEW.simplified_geometry := NEW.geometry;
    END IF;

    -- Calcul Bounding Box
    NEW.bbox := ST_Envelope(NEW.geometry);
    NEW.updated_at := clock_timestamp();

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_compute_geometry_attributes ON core.geometries;
CREATE TRIGGER trg_compute_geometry_attributes
    BEFORE INSERT OR UPDATE ON core.geometries
    FOR EACH ROW
    EXECUTE FUNCTION core.fn_compute_geometry_attributes();

-- Trigger de synchronisation superficie sur core.territories
CREATE OR REPLACE FUNCTION core.fn_sync_territory_from_geometry()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.geometry_id IS NOT NULL THEN
        UPDATE core.territories
        SET area_sqkm = g.area_sqkm
        FROM core.geometries g
        WHERE core.territories.geometry_id = g.id
          AND core.territories.id = NEW.id;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
