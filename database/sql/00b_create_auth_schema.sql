-- ============================================================================
-- 00B_CREATE_AUTH_SCHEMA.SQL
-- Schema: auth
-- Authentification applicative (US-022, CDC §52-53, §55).
--
-- Le schéma `auth` isole la gestion des identités et des clés API du
-- référentiel géospatial. Principe §52 : le jeton en clair n'est JAMAIS
-- persisté — seule son empreinte SHA-256 (`token_hash`) l'est. La colonne
-- `token_prefix` (tnp_xxxxxxxxxxxx) permet d'identifier une clé dans l'UI
-- sans jamais exposer le secret.
-- ============================================================================

-- Schéma d'isolation dédié à l'authentification (cf. 00_init_extensions_schemas.sql)
CREATE SCHEMA IF NOT EXISTS auth;

COMMENT ON SCHEMA auth IS 'Identités, sessions et clés API de la plateforme (séparé du référentiel géospatial).';

-- ----------------------------------------------------------------------------
-- 1. USERS (identités Sanctum — provider Eloquent par défaut)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS auth.users (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT uuid_generate_v4(),
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMPTZ,
    password VARCHAR(255) NOT NULL,
    -- RBAC §55 : un rôle global simple suffit au MVP (admin vs membre).
    role VARCHAR(50) NOT NULL DEFAULT 'member', -- admin, member
    status VARCHAR(50) NOT NULL DEFAULT 'ACTIVE', -- ACTIVE, SUSPENDED
    remember_token VARCHAR(100),
    created_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
);

CREATE INDEX IF NOT EXISTS idx_auth_users_role ON auth.users(role);

-- ----------------------------------------------------------------------------
-- 2. PERSONAL_ACCESS_TOKENS (jetons Sanctum, schéma auth)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS auth.personal_access_tokens (
    id BIGSERIAL PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    abilities TEXT,
    last_used_at TIMESTAMPTZ,
    expires_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ,
    updated_at TIMESTAMPTZ
);

CREATE INDEX IF NOT EXISTS idx_pat_tokenable ON auth.personal_access_tokens(tokenable_type, tokenable_id);

-- ----------------------------------------------------------------------------
-- 3. API_KEYS (clés API hachées — CDC §52)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS auth.api_keys (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT uuid_generate_v4(),
    user_id BIGINT NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    -- §52 : seul le hash SHA-256 du jeton est stocké. Jamais le clair.
    token_hash VARCHAR(64) UNIQUE NOT NULL,
    token_prefix VARCHAR(32) NOT NULL,
    tier VARCHAR(50) NOT NULL DEFAULT 'public', -- public, developer, admin
    permissions JSONB NOT NULL DEFAULT '[]'::jsonb,
    description TEXT,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    expires_at TIMESTAMPTZ,
    last_used_at TIMESTAMPTZ,
    created_by_ip INET,
    created_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT clock_timestamp()
);

CREATE INDEX IF NOT EXISTS idx_api_keys_user ON auth.api_keys(user_id);
CREATE INDEX IF NOT EXISTS idx_api_keys_hash ON auth.api_keys(token_hash);
CREATE INDEX IF NOT EXISTS idx_api_keys_active ON auth.api_keys(is_active) WHERE is_active = TRUE;

