"""
Module de transformation et validation dans le schéma PostGIS 'staging'.
"""
import json
from typing import Dict, Any
import shapely.wkt
from sqlalchemy import text
from etl.utils.db import get_db_session
from etl.utils.logger import logger
from etl.validators.geometry_validator import GeometryValidator
from etl.validators.attribute_validator import AttributeValidator

class StagingTransformer:
    """Transforme les données de raw vers staging, applique le nettoyage et génère le rapport qualité."""

    def process_import(self, import_id: int) -> Dict[str, Any]:
        """
        Exécute la transformation et les contrôles qualité pour un import_id.
        """
        logger.info(f"Début du traitement vers staging pour l'import ID : [bold cyan]{import_id}[/]")

        with get_db_session() as session:
            # Récupérer les informations de la session d'import
            import_info = session.execute(
                text("SELECT target_territory_type FROM raw.dataset_imports WHERE id = :id"),
                {"id": import_id}
            ).fetchone()

            if not import_info:
                raise ValueError(f"Session d'import {import_id} introuvable dans raw.dataset_imports")

            target_type = import_info[0]

            # Récupérer toutes les entités brutes
            raw_features = session.execute(
                text("SELECT id, feature_index, raw_wkt, properties FROM raw.import_features WHERE import_id = :id"),
                {"id": import_id}
            ).fetchall()

            logger.info(f"Traitement de {len(raw_features)} entités géométriques...")

            stmt_insert = text("""
                INSERT INTO staging.territories (
                    import_id, raw_feature_id, code, code_ansd, code_anat,
                    name, official_name, territory_type_code, parent_code,
                    geometry, properties
                ) VALUES (
                    :import_id, :raw_feature_id, :code, :code_ansd, :code_anat,
                    :name, :official_name, :territory_type_code, :parent_code,
                    ST_SetSRID(ST_GeomFromText(:wkt), 4326), :properties
                );
            """)

            for feature in raw_features:
                raw_id, feat_idx, raw_wkt, props = feature

                # 1. Normalisation des attributs
                norm_attr = AttributeValidator.normalize_attributes(props, target_type)

                # 2. Validation et réparation géométrique via Shapely
                repaired_wkt = None
                if raw_wkt:
                    geom = shapely.wkt.loads(raw_wkt)
                    repaired_geom, quality_status, report = GeometryValidator.validate_and_repair(geom)
                    repaired_wkt = repaired_geom.wkt if repaired_geom else None

                session.execute(stmt_insert, {
                    "import_id": import_id,
                    "raw_feature_id": raw_id,
                    "code": norm_attr["code"],
                    "code_ansd": norm_attr["code_ansd"],
                    "code_anat": norm_attr["code_anat"],
                    "name": norm_attr["name"],
                    "official_name": norm_attr["official_name"],
                    "territory_type_code": norm_attr["territory_type_code"],
                    "parent_code": norm_attr["parent_code"],
                    "wkt": repaired_wkt or raw_wkt,
                    "properties": json.dumps(norm_attr["properties"])
                })

            # 3. Exécution de la procédure SQL de validation topologique globale
            logger.info("Exécution de la procédure de validation topologique et attributaire SQL...")
            val_result = session.execute(
                text("SELECT * FROM staging.fn_validate_staging_dataset(:import_id);"),
                {"import_id": import_id}
            ).fetchone()

            # Récupération du rapport de qualité complet
            report_row = session.execute(
                text("SELECT * FROM staging.quality_reports WHERE import_id = :import_id ORDER BY id DESC LIMIT 1"),
                {"import_id": import_id}
            ).mappings().fetchone()

        report_dict = dict(report_row) if report_row else {}
        logger.info(f"[bold green]✓ Validation staging terminée ![/] Score qualité : [bold yellow]{report_dict.get('score_percentage', 0)}%[/]")
        logger.info(f"Détails : Valides: [green]{report_dict.get('valid_geometries')}[/], Invalides: [red]{report_dict.get('invalid_geometries')}[/], Doublons: [yellow]{report_dict.get('duplicate_codes')}[/]")

        return report_dict
