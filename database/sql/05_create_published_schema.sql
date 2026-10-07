-- ============================================================================
-- 05_CREATE_PUBLISHED_SCHEMA.SQL
-- Schema: published
-- Données dénormalisées haute performance pour le Web GIS et les APIs publiques
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. PUBLISHED_TERRITORIES (Table dénormalisée optimisée pour la consultation)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS published.territories (
    id BIGINT PRIMARY KEY, -- Référence directe à core.territories.id
    code VARCHAR(50) UNIQUE NOT NULL,
    code_ansd VARCHAR(50),
    code_anat VARCHAR(50),
    name VARCHAR(255) NOT NULL,
    official_name VARCHAR(255),
    slug VARCHAR(255) NOT NULL,
    type_code VARCHAR(50) NOT NULL,
    type_name VARCHAR(100) NOT NULL,
    level INTEGER NOT NULL,
    parent_id BIGINT,
    parent_code VARCHAR(50),
    parent_name VARCHAR(255),
    ancestors JSONB DEFAULT '[]'::jsonb, -- Liste ordonnée des parents [{level, code, name, type}]
    area_sqkm NUMERIC(16, 4),
    population_census BIGINT,
    centroid GEOMETRY(Point, 4326),
    bbox GEOMETRY(Polygon, 4326),
    geometry GEOMETRY(Geometry, 4326),
    simplified_geometry GEOMETRY(Geometry, 4326), -- Pour rendu cartographique rapide
    dataset_slug VARCHAR(255),
    dataset_version VARCHAR(50),
    source_name VARCHAR(255),
    license VARCHAR(255),
    properties JSONB DEFAULT '{}'::jsonb,
    published_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
);

-- Index spatiaux GiST sur la couche publiée
CREATE INDEX IF NOT EXISTS idx_pub_geom ON published.territories USING GIST (geometry);
CREATE INDEX IF NOT EXISTS idx_pub_simplified_geom ON published.territories USING GIST (simplified_geometry);
CREATE INDEX IF NOT EXISTS idx_pub_centroid ON published.territories USING GIST (centroid);
CREATE INDEX IF NOT EXISTS idx_pub_bbox ON published.territories USING GIST (bbox);

-- Index de recherche rapide
CREATE INDEX IF NOT EXISTS idx_pub_type ON published.territories (type_code);
CREATE INDEX IF NOT EXISTS idx_pub_level ON published.territories (level);
CREATE INDEX IF NOT EXISTS idx_pub_parent_id ON published.territories (parent_id);
CREATE INDEX IF NOT EXISTS idx_pub_parent_code ON published.territories (parent_code);
CREATE INDEX IF NOT EXISTS idx_pub_slug ON published.territories (slug);
CREATE INDEX IF NOT EXISTS idx_pub_name_trgm ON published.territories USING GIN (name gin_trgm_ops);
CREATE INDEX IF NOT EXISTS idx_pub_name_tsv ON published.territories USING GIN (to_tsvector('french', public.immutable_unaccent(name)));

-- ----------------------------------------------------------------------------
-- 2. PROCÉDURE DE PUBLICATION FORMELLE D'UNE VERSION DE JEU DE DONNÉES
-- ----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION published.fn_publish_dataset_version(
    p_version_id BIGINT,
    p_published_by VARCHAR(100) DEFAULT 'SYSTEM'
)
RETURNS VOID AS $$
DECLARE
    v_dataset_id BIGINT;
    v_version_str VARCHAR(50);
BEGIN
    -- 1. Vérification de l'existence et du statut de la version
    SELECT dataset_id, version INTO v_dataset_id, v_version_str
    FROM core.dataset_versions
    WHERE id = p_version_id;

    IF v_dataset_id IS NULL THEN
        RAISE EXCEPTION 'Dataset version ID % not found in core.dataset_versions', p_version_id;
    END IF;

    -- 2. Insertion / Remplacement des données dans published.territories
    INSERT INTO published.territories (
        id, code, code_ansd, code_anat, name, official_name, slug,
        type_code, type_name, level, parent_id, parent_code, parent_name,
        area_sqkm, population_census, centroid, bbox, geometry,
        simplified_geometry, dataset_slug, dataset_version,
        source_name, license, properties, published_at, updated_at
    )
    SELECT
        t.id,
        t.code,
        t.code_ansd,
        t.code_anat,
        t.name,
        t.official_name,
        t.slug,
        tt.code AS type_code,
        tt.name AS type_name,
        t.level,
        t.parent_id,
        p.code AS parent_code,
        p.name AS parent_name,
        COALESCE(t.area_sqkm, g.area_sqkm),
        t.population_census,
        g.centroid,
        g.bbox,
        g.geometry,
        COALESCE(g.simplified_geometry, g.geometry),
        d.slug AS dataset_slug,
        dv.version AS dataset_version,
        s.name AS source_name,
        s.license,
        t.metadata,
        clock_timestamp(),
        clock_timestamp()
    FROM core.territories t
    JOIN core.territory_types tt ON t.territory_type_id = tt.id
    LEFT JOIN core.territories p ON t.parent_id = p.id
    LEFT JOIN core.geometries g ON t.geometry_id = g.id
    JOIN core.dataset_versions dv ON t.source_version_id = dv.id
    JOIN core.datasets d ON dv.dataset_id = d.id
    JOIN core.sources s ON d.source_id = s.id
    WHERE t.source_version_id = p_version_id
      AND t.status = 'ACTIVE'
    ON CONFLICT (id) DO UPDATE SET
        code = EXCLUDED.code,
        code_ansd = EXCLUDED.code_ansd,
        code_anat = EXCLUDED.code_anat,
        name = EXCLUDED.name,
        official_name = EXCLUDED.official_name,
        slug = EXCLUDED.slug,
        type_code = EXCLUDED.type_code,
        type_name = EXCLUDED.type_name,
        level = EXCLUDED.level,
        parent_id = EXCLUDED.parent_id,
        parent_code = EXCLUDED.parent_code,
        parent_name = EXCLUDED.parent_name,
        area_sqkm = EXCLUDED.area_sqkm,
        population_census = EXCLUDED.population_census,
        centroid = EXCLUDED.centroid,
        bbox = EXCLUDED.bbox,
        geometry = EXCLUDED.geometry,
        simplified_geometry = EXCLUDED.simplified_geometry,
        dataset_slug = EXCLUDED.dataset_slug,
        dataset_version = EXCLUDED.dataset_version,
        source_name = EXCLUDED.source_name,
        license = EXCLUDED.license,
        properties = EXCLUDED.properties,
        updated_at = clock_timestamp();

    -- 3. Mise à jour du statut de la version
    UPDATE core.dataset_versions
    SET status = 'published', updated_at = clock_timestamp()
    WHERE id = p_version_id;

    -- 4. Journalisation de l'audit
    INSERT INTO audit.logs (
        user_id, action, entity_type, entity_id, new_values, metadata
    ) VALUES (
        p_published_by,
        'PUBLISH_DATASET_VERSION',
        'core.dataset_versions',
        p_version_id::text,
        jsonb_build_object('dataset_id', v_dataset_id, 'version', v_version_str, 'status', 'published'),
        jsonb_build_object('timestamp', clock_timestamp())
    );
END;
$$ LANGUAGE plpgsql;
