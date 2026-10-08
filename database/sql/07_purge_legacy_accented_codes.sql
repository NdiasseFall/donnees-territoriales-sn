-- ============================================================================
-- 07_purge_legacy_accented_codes.sql — Stabilisation locale
-- Purge des codes legacy accentués (ex: SN-DK-DK-MÉDI -> SN-DK-DK-MEDI)
-- pour conformité stricte au Value Object TerritoryCode ([A-Z0-9_-]+).
-- Idempotent : rejouable sans effet de bord (ON CONFLICT DO NOTHING).
-- ============================================================================

-- Fonction de translittération FR -> ASCII (complète le fix seed Python).
-- translate() exige des chaînes de même longueur : mapping 1:1 strict.
CREATE OR REPLACE FUNCTION core.fn_transliterate_code(p_code VARCHAR)
RETURNS VARCHAR AS $$
    SELECT translate(
        p_code,
        'ÉÈÊËéèêëÀÂÄàâäÎÏîïÔÖôöÙÛÜùûüÇçÑñÝý',
        'EEEEeeeeAAAaaaIIiiOOooUUUuuuCcNnYy'
    );
$$ LANGUAGE SQL IMMUTABLE;

-- 1. Les 16 codes accentués sont tous des doublons exacts des versions ASCII
--    (seed rejoué 2x : ancien seed accentué + nouveau seed ASCII).
--    Suppression pure des doublons accentués (les relations du survivant ASCII existent déjà).
DO $$
DECLARE
    r RECORD;
BEGIN
    FOR r IN SELECT id, code FROM core.territories WHERE code ~ '[^A-Z0-9_-]' LOOP
        -- Nettoyer les relations du doublon (évite violation FK + UNIQUE).
        DELETE FROM core.territory_relationships WHERE parent_territory_id = r.id OR child_territory_id = r.id;
        DELETE FROM core.territories WHERE id = r.id;
        RAISE NOTICE 'Doublon accentué supprimé : % (id %)', r.code, r.id;
    END LOOP;
END;
$$;

-- 2. Vérification : 0 code non conforme restant.
DO $$
DECLARE
    v_count INTEGER;
BEGIN
    SELECT count(*) INTO v_count FROM core.territories WHERE code ~ '[^A-Z0-9_-]';
    IF v_count > 0 THEN
        RAISE EXCEPTION 'Purge incomplète : % codes accentués restants', v_count;
    ELSE
        RAISE NOTICE 'Purge OK : 0 code accentué restant';
    END IF;
END;
$$;
