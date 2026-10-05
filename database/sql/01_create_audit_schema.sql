-- ============================================================================
-- 01_CREATE_AUDIT_SCHEMA.SQL
-- Schema: audit
-- Traçabilité et journalisation des modifications
-- ============================================================================

CREATE TABLE IF NOT EXISTS audit.logs (
    id BIGSERIAL PRIMARY KEY,
    user_id VARCHAR(100),
    user_email VARCHAR(255),
    ip_address INET,
    action VARCHAR(50) NOT NULL, -- INSERT, UPDATE, DELETE, IMPORT, VALIDATION, PUBLISH, ARCHIVE
    entity_type VARCHAR(100) NOT NULL, -- core.territories, core.geometries, core.dataset_versions, etc.
    entity_id VARCHAR(100) NOT NULL,
    old_values JSONB,
    new_values JSONB,
    metadata JSONB DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
);

-- Index pour requêtes d'audit rapides
CREATE INDEX IF NOT EXISTS idx_audit_entity ON audit.logs(entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_audit_action ON audit.logs(action);
CREATE INDEX IF NOT EXISTS idx_audit_created_at ON audit.logs(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_audit_user_id ON audit.logs(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_metadata_gin ON audit.logs USING GIN (metadata);

COMMENT ON TABLE audit.logs IS 'Journal immuable de traçabilité des opérations de données administratives et spatiales.';
