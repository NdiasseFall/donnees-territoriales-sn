"""
Module de promotion des données validées de 'staging' vers 'core' et 'published'.
"""
import json
from typing import Optional
from sqlalchemy import text
from etl.utils.db import get_db_session
from etl.utils.logger import logger

class CorePromoter:
    """Promeut les enregistrements validés de staging vers le référentiel pivot core et published."""

    def promote_to_core(
        self,
        import_id: int,
        dataset_slug: str = "administrative-boundaries",
        version_str: str = "1.0",
        auto_publish: bool = True,
        promoted_by: str = "ETL_PIPELINE"
    ) -> int:
        """
        Déplace les entités validées de staging vers core.geometries et core.territories,
        puis publie la version si auto_publish=True.
        """
        logger.info(f"Promotion de l'import ID [bold cyan]{import_id}[/] vers 'core' (Version: [bold yellow]{version_str}[/])...")

        with get_db_session() as session:
            # 1. Vérification / Création de l'organisation par défaut
            org_id = session.execute(text("""
                INSERT INTO core.organizations (name, acronym, type, email)
                VALUES ('Agence Nationale de l''Aménagement du Territoire', 'ANAT', 'AGENCY', 'contact@anat.sn')
                ON CONFLICT DO NOTHING;
                SELECT id FROM core.organizations WHERE acronym = 'ANAT' LIMIT 1;
            """)).scalar()

            # 2. Vérification / Création de la source par défaut
            source_id = session.execute(text("""
                INSERT INTO core.sources (organization_id, name, source_type, license)
                VALUES (:org_id, 'Référentiel National du Découpage Administratif', 'OFFICIAL_CADASTRE', 'PUBLIC_DOMAIN')
                ON CONFLICT DO NOTHING;
                SELECT id FROM core.sources WHERE organization_id = :org_id LIMIT 1;
            """), {"org_id": org_id}).scalar()

            # 3. Vérification / Création du dataset
            dataset_id = session.execute(text("""
                INSERT INTO core.datasets (source_id, name, slug, dataset_type, geometry_type, access_level)
                VALUES (:source_id, 'Découpage Administratif Officiel', :slug, 'ADMINISTRATIVE_BOUNDARIES', 'MultiPolygon', 'PUBLIC')
                ON CONFLICT (slug) DO UPDATE SET updated_at = clock_timestamp()
                RETURNING id;
            """), {"source_id": source_id, "slug": dataset_slug}).scalar()

            # 4. Création de la version de jeu de données
            version_id = session.execute(text("""
                INSERT INTO core.dataset_versions (dataset_id, version, status, notes)
                VALUES (:dataset_id, :version, 'validated', 'Généré automatiquement par le pipeline ETL')
                ON CONFLICT (dataset_id, version) DO UPDATE SET status = 'validated', updated_at = clock_timestamp()
                RETURNING id;
            """), {"dataset_id": dataset_id, "version": version_str}).scalar()

            # 5. Insertion des géométries dans core.geometries et des territoires dans core.territories
            stmt_promote = text("""
                WITH inserted_geoms AS (
                    INSERT INTO core.geometries (source_version_id, geometry, quality_status)
                    SELECT
                        :version_id,
                        s.geometry,
                        s.validation_status
                    FROM staging.territories s
                    WHERE s.import_id = :import_id
                      AND s.validation_status IN ('VALID', 'WARNING')
                    RETURNING id, ST_AsText(geometry) AS geom_wkt
                )
                INSERT INTO core.territories (
                    territory_type_id, geometry_id, source_version_id,
                    code, code_ansd, code_anat, name, official_name,
                    slug, level, status, metadata
                )
                SELECT
                    tt.id AS territory_type_id,
                    g.id AS geometry_id,
                    :version_id,
                    s.code,
                    s.code_ansd,
                    s.code_anat,
                    s.name,
                    s.official_name,
                    LOWER(REPLACE(s.name, ' ', '-')),
                    tt.level,
                    'ACTIVE',
                    s.properties
                FROM staging.territories s
                JOIN core.territory_types tt ON s.territory_type_code = tt.code
                JOIN inserted_geoms g ON ST_Equals(s.geometry, ST_GeomFromText(g.geom_wkt, 4326))
                WHERE s.import_id = :import_id
                  AND s.validation_status IN ('VALID', 'WARNING')
                ON CONFLICT (code) DO UPDATE SET
                    geometry_id = EXCLUDED.geometry_id,
                    source_version_id = EXCLUDED.source_version_id,
                    name = EXCLUDED.name,
                    official_name = EXCLUDED.official_name,
                    level = EXCLUDED.level,
                    metadata = EXCLUDED.metadata,
                    updated_at = clock_timestamp();
            """)

            session.execute(stmt_promote, {
                "version_id": version_id,
                "import_id": import_id
            })

            # 6. Résolution automatique des relations hiérarchiques parent_id
            stmt_resolve_parents = text("""
                UPDATE core.territories t
                SET parent_id = p.id
                FROM staging.territories s
                JOIN core.territories p ON s.parent_code = p.code
                WHERE s.import_id = :import_id
                  AND t.code = s.code;
            """)
            session.execute(stmt_resolve_parents, {"import_id": import_id})

            # 7. Insertion dans core.territory_relationships
            stmt_rel = text("""
                INSERT INTO core.territory_relationships (parent_territory_id, child_territory_id, relationship_type, source_version_id)
                SELECT t.parent_id, t.id, 'ADMINISTRATIVE_PARENT', :version_id
                FROM core.territories t
                WHERE t.source_version_id = :version_id
                  AND t.parent_id IS NOT NULL
                ON CONFLICT (parent_territory_id, child_territory_id, relationship_type) DO NOTHING;
            """)
            session.execute(stmt_rel, {"version_id": version_id})

            # 8. Publication automatique si demandée
            if auto_publish:
                logger.info("Exécution de la publication vers 'published.territories'...")
                session.execute(text("SELECT published.fn_publish_dataset_version(:version_id, :promoted_by);"), {
                    "version_id": version_id,
                    "promoted_by": promoted_by
                })

        logger.info(f"[bold green]✓ Promotion vers core et publication achevées avec succès ![/] Version ID : [bold]{version_id}[/]")
        return version_id
