-- ============================================================================
-- 04_CREATE_STAGING_SCHEMA.SQL
-- Schema: staging
-- Zone tampon de transformation, nettoyage et validation topologique
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. STAGING_TERRITORIES (Territoires en cours de traitement)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS staging.territories (
    id BIGSERIAL PRIMARY KEY,
    import_id BIGINT NOT NULL REFERENCES raw.dataset_imports(id) ON DELETE CASCADE,
    raw_feature_id BIGINT REFERENCES raw.import_features(id) ON DELETE CASCADE,
    code VARCHAR(50),
    code_ansd VARCHAR(50),
    code_anat VARCHAR(50),
    name VARCHAR(255) NOT NULL,
    official_name VARCHAR(255),
    territory_type_code VARCHAR(50) NOT NULL,
    parent_code VARCHAR(50),
    geometry GEOMETRY(Geometry, 4326),
    is_geom_valid BOOLEAN DEFAULT FALSE,
    is_empty BOOLEAN DEFAULT FALSE,
    area_sqkm NUMERIC(16, 4),
    validation_status VARCHAR(50) NOT NULL DEFAULT 'PENDING', -- PENDING, VALID, WARNING, INVALID, DUPLICATE
    validation_errors JSONB NOT NULL DEFAULT '[]'::jsonb,
    properties JSONB DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
);

CREATE INDEX IF NOT EXISTS idx_staging_import ON staging.territories(import_id);
CREATE INDEX IF NOT EXISTS idx_staging_code ON staging.territories(code);
CREATE INDEX IF NOT EXISTS idx_staging_parent_code ON staging.territories(parent_code);
CREATE INDEX IF NOT EXISTS idx_staging_type ON staging.territories(territory_type_code);
CREATE INDEX IF NOT EXISTS idx_staging_status ON staging.territories(validation_status);
CREATE INDEX IF NOT EXISTS idx_staging_geom ON staging.territories USING GIST (geometry);

-- ----------------------------------------------------------------------------
-- 2. STAGING_QUALITY_REPORTS (Synthèse des contrôles qualité pour les reviewers)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS staging.quality_reports (
    id BIGSERIAL PRIMARY KEY,
    import_id BIGINT NOT NULL REFERENCES raw.dataset_imports(id) ON DELETE CASCADE,
    total_features INTEGER NOT NULL DEFAULT 0,
    valid_geometries INTEGER NOT NULL DEFAULT 0,
    invalid_geometries INTEGER NOT NULL DEFAULT 0,
    repaired_geometries INTEGER NOT NULL DEFAULT 0,
    empty_geometries INTEGER NOT NULL DEFAULT 0,
    duplicate_codes INTEGER NOT NULL DEFAULT 0,
    missing_parents INTEGER NOT NULL DEFAULT 0,
    score_percentage NUMERIC(5, 2) DEFAULT 0.00,
    is_approved BOOLEAN DEFAULT FALSE,
    reviewer_notes TEXT,
    reviewed_by VARCHAR(100),
    reviewed_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
);

CREATE INDEX IF NOT EXISTS idx_staging_reports_import ON staging.quality_reports(import_id);

-- ----------------------------------------------------------------------------
-- 3. FONCTION DE VALIDATION TOPOLOGIQUE & ATTRIBUTAIRE AUTOMATISÉE
-- ----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION staging.fn_validate_staging_dataset(p_import_id BIGINT)
RETURNS TABLE (
    total_count INT,
    valid_geom_count INT,
    invalid_geom_count INT,
    missing_parent_count INT,
    duplicate_code_count INT
) AS $$
DECLARE
    v_total INT;
    v_valid INT;
    v_invalid INT;
    v_missing_parent INT;
    v_dup INT;
    v_score NUMERIC(5,2);
BEGIN
    -- 1. Mise à jour de la validité géométrique élémentaire
    UPDATE staging.territories
    SET
        is_empty = ST_IsEmpty(geometry),
        is_geom_valid = ST_IsValid(geometry),
        area_sqkm = CASE
            WHEN geometry IS NOT NULL AND ST_GeometryType(geometry) IN ('ST_Polygon', 'ST_MultiPolygon')
            THEN ROUND((ST_Area(geometry::geography) / 1000000.0)::numeric, 4)
            ELSE 0
        END,
        validation_status = CASE
            WHEN geometry IS NULL OR ST_IsEmpty(geometry) THEN 'INVALID'
            WHEN NOT ST_IsValid(geometry) THEN 'WARNING'
            ELSE 'VALID'
        END,
        validation_errors = CASE
            WHEN geometry IS NULL OR ST_IsEmpty(geometry) THEN '[{"code": "EMPTY_GEOMETRY", "message": "Géométrie manquante ou vide"}]'::jsonb
            WHEN NOT ST_IsValid(geometry) THEN jsonb_build_array(jsonb_build_object('code', 'INVALID_GEOMETRY_REASON', 'message', ST_IsValidReason(geometry)))
            ELSE '[]'::jsonb
        END
    WHERE import_id = p_import_id;

    -- 2. Détection des doublons de codes au sein du lot
    UPDATE staging.territories t
    SET
        validation_status = 'DUPLICATE',
        validation_errors = t.validation_errors || '[{"code": "DUPLICATE_CODE", "message": "Code territorial en doublon dans le jeu de données"}]'::jsonb
    WHERE t.import_id = p_import_id
      AND t.code IN (
          SELECT code FROM staging.territories
          WHERE import_id = p_import_id AND code IS NOT NULL
          GROUP BY code HAVING COUNT(*) > 1
      );

    -- 3. Calcul des statistiques
    SELECT COUNT(*) INTO v_total FROM staging.territories WHERE import_id = p_import_id;
    SELECT COUNT(*) INTO v_valid FROM staging.territories WHERE import_id = p_import_id AND is_geom_valid = true AND NOT is_empty;
    SELECT COUNT(*) INTO v_invalid FROM staging.territories WHERE import_id = p_import_id AND (is_geom_valid = false OR is_empty = true);
    SELECT COUNT(*) INTO v_dup FROM staging.territories WHERE import_id = p_import_id AND validation_status = 'DUPLICATE';

    -- Vérification des parents manquants (hors pays)
    SELECT COUNT(*) INTO v_missing_parent
    FROM staging.territories t
    WHERE t.import_id = p_import_id
      AND t.territory_type_code <> 'COUNTRY'
      AND t.parent_code IS NOT NULL
      AND NOT EXISTS (SELECT 1 FROM core.territories c WHERE c.code = t.parent_code)
      AND NOT EXISTS (SELECT 1 FROM staging.territories s WHERE s.import_id = p_import_id AND s.code = t.parent_code);

    -- Calcul score qualité
    IF v_total > 0 THEN
        v_score := ROUND(((v_valid::numeric / v_total::numeric) * 100.0), 2);
    ELSE
        v_score := 0.00;
    END IF;

    -- 4. Enregistrement du rapport qualité
    INSERT INTO staging.quality_reports (
        import_id, total_features, valid_geometries, invalid_geometries,
        duplicate_codes, missing_parents, score_percentage
    ) VALUES (
        p_import_id, v_total, v_valid, v_invalid, v_dup, v_missing_parent, v_score
    );

    RETURN QUERY SELECT v_total, v_valid, v_invalid, v_missing_parent, v_dup;
END;
$$ LANGUAGE plpgsql;
