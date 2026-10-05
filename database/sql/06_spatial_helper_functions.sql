-- ============================================================================
-- 06_SPATIAL_HELPER_FUNCTIONS.SQL
-- Fonctions spatiales et utilitaires API (GeoJSON RFC 7946, Reverse Geocoding)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. EXPORT D'UN TERRITOIRE EN FORMAT GEOJSON FEATURE (RFC 7946)
-- ----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION published.fn_get_territory_geojson(p_code VARCHAR)
RETURNS JSONB AS $$
DECLARE
    v_result JSONB;
BEGIN
    SELECT jsonb_build_object(
        'type', 'Feature',
        'id', t.id,
        'geometry', ST_AsGeoJSON(t.geometry)::jsonb,
        'bbox', jsonb_build_array(
            ST_XMin(t.bbox), ST_YMin(t.bbox),
            ST_XMax(t.bbox), ST_YMax(t.bbox)
        ),
        'properties', jsonb_build_object(
            'code', t.code,
            'code_ansd', t.code_ansd,
            'code_anat', t.code_anat,
            'name', t.name,
            'official_name', t.official_name,
            'slug', t.slug,
            'type_code', t.type_code,
            'type_name', t.type_name,
            'level', t.level,
            'parent_code', t.parent_code,
            'parent_name', t.parent_name,
            'area_sqkm', t.area_sqkm,
            'population', t.population_census,
            'dataset_slug', t.dataset_slug,
            'dataset_version', t.dataset_version,
            'source', t.source_name,
            'license', t.license
        )
    ) INTO v_result
    FROM published.territories t
    WHERE t.code = p_code OR t.slug = p_code;

    RETURN v_result;
END;
$$ LANGUAGE plpgsql STABLE;

-- ----------------------------------------------------------------------------
-- 2. EXPORT D'UNE COLLECTION GEOJSON (FEATURECOLLECTION) AVEC FILTRES
-- ----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION published.fn_get_territories_geojson_collection(
    p_type_code VARCHAR DEFAULT NULL,
    p_parent_code VARCHAR DEFAULT NULL,
    p_min_lon NUMERIC DEFAULT NULL,
    p_min_lat NUMERIC DEFAULT NULL,
    p_max_lon NUMERIC DEFAULT NULL,
    p_max_lat NUMERIC DEFAULT NULL,
    p_use_simplified BOOLEAN DEFAULT TRUE,
    p_limit INTEGER DEFAULT 500
)
RETURNS JSONB AS $$
DECLARE
    v_bbox_geom GEOMETRY := NULL;
    v_result JSONB;
BEGIN
    -- Construction de l'enveloppe spatiale BBOX si fournie
    IF p_min_lon IS NOT NULL AND p_min_lat IS NOT NULL AND p_max_lon IS NOT NULL AND p_max_lat IS NOT NULL THEN
        v_bbox_geom := ST_MakeEnvelope(p_min_lon, p_min_lat, p_max_lon, p_max_lat, 4326);
    END IF;

    SELECT jsonb_build_object(
        'type', 'FeatureCollection',
        'features', COALESCE(jsonb_agg(
            jsonb_build_object(
                'type', 'Feature',
                'id', t.id,
                'geometry', CASE
                    WHEN p_use_simplified THEN ST_AsGeoJSON(t.simplified_geometry)::jsonb
                    ELSE ST_AsGeoJSON(t.geometry)::jsonb
                END,
                'properties', jsonb_build_object(
                    'code', t.code,
                    'name', t.name,
                    'slug', t.slug,
                    'type_code', t.type_code,
                    'type_name', t.type_name,
                    'level', t.level,
                    'parent_code', t.parent_code,
                    'parent_name', t.parent_name,
                    'area_sqkm', t.area_sqkm
                )
            )
        ), '[]'::jsonb)
    ) INTO v_result
    FROM (
        SELECT *
        FROM published.territories t
        WHERE (p_type_code IS NULL OR t.type_code = p_type_code)
          AND (p_parent_code IS NULL OR t.parent_code = p_parent_code)
          AND (v_bbox_geom IS NULL OR ST_Intersects(t.geometry, v_bbox_geom))
        ORDER BY t.level, t.name
        LIMIT p_limit
    ) t;

    RETURN v_result;
END;
$$ LANGUAGE plpgsql STABLE;

-- ----------------------------------------------------------------------------
-- 3. REVERSE GEOCODING (Identifier toute la hiérarchie pour un point GPS)
-- ----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION published.fn_reverse_geocode(
    p_longitude DOUBLE PRECISION,
    p_latitude DOUBLE PRECISION
)
RETURNS JSONB AS $$
DECLARE
    v_point GEOMETRY;
    v_result JSONB;
BEGIN
    v_point := ST_SetSRID(ST_MakePoint(p_longitude, p_latitude), 4326);

    SELECT jsonb_build_object(
        'point', jsonb_build_object('longitude', p_longitude, 'latitude', p_latitude),
        'hierarchy', jsonb_object_agg(
            LOWER(t.type_code),
            jsonb_build_object(
                'id', t.id,
                'code', t.code,
                'name', t.name,
                'type', t.type_name,
                'level', t.level
            )
        )
    ) INTO v_result
    FROM published.territories t
    WHERE ST_Contains(t.geometry, v_point)
       OR (t.geometry_type = 'ST_Point' AND ST_DWithin(t.geometry::geography, v_point::geography, 1000));

    RETURN v_result;
END;
$$ LANGUAGE plpgsql STABLE;

-- ----------------------------------------------------------------------------
-- 4. RECHERCHE MULTI-CRITÈRES & PLEIN TEXTE OPTIMISÉE
-- ----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION published.fn_search_territories(
    p_query VARCHAR,
    p_limit INTEGER DEFAULT 20
)
RETURNS TABLE (
    id BIGINT,
    code VARCHAR,
    name VARCHAR,
    type_code VARCHAR,
    type_name VARCHAR,
    level INT,
    parent_name VARCHAR,
    similarity_score REAL
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        t.id,
        t.code,
        t.name,
        t.type_code,
        t.type_name,
        t.level,
        t.parent_name,
        similarity(t.name, p_query) AS similarity_score
    FROM published.territories t
    WHERE t.code ILIKE '%' || p_query || '%'
       OR t.name ILIKE '%' || p_query || '%'
       OR to_tsvector('french', unaccent(t.name)) @@ plainto_tsquery('french', unaccent(p_query))
    ORDER BY
        similarity(t.name, p_query) DESC,
        t.level ASC
    LIMIT p_limit;
END;
$$ LANGUAGE plpgsql STABLE;

-- ----------------------------------------------------------------------------
-- 5. RECONSTITUTION RÉCURSIVE DE L'ARBRE DES ANCÊTRES (core.territories)
-- ----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION core.fn_get_territory_ancestors(p_territory_id BIGINT)
RETURNS JSONB AS $$
DECLARE
    v_tree JSONB;
BEGIN
    WITH RECURSIVE hierarchy_tree AS (
        SELECT id, parent_id, code, name, level, territory_type_id, 1 as depth
        FROM core.territories
        WHERE id = p_territory_id
        UNION ALL
        SELECT t.id, t.parent_id, t.code, t.name, t.level, t.territory_type_id, h.depth + 1
        FROM core.territories t
        JOIN hierarchy_tree h ON t.id = h.parent_id
    )
    SELECT jsonb_agg(
        jsonb_build_object(
            'id', h.id,
            'code', h.code,
            'name', h.name,
            'level', h.level,
            'type_code', tt.code,
            'type_name', tt.name
        ) ORDER BY h.level ASC
    ) INTO v_tree
    FROM hierarchy_tree h
    JOIN core.territory_types tt ON h.territory_type_id = tt.id;

    RETURN COALESCE(v_tree, '[]'::jsonb);
END;
$$ LANGUAGE plpgsql STABLE;
