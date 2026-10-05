"""
Module de chargement des fichiers géospatiaux bruts dans le schéma PostGIS 'raw'.
"""
import hashlib
import json
from pathlib import Path
from typing import Dict, Any, Optional
import geopandas as gpd
import shapely
from sqlalchemy import text
from etl.utils.db import engine, get_db_session
from etl.utils.logger import logger
from etl.validators.geometry_validator import GeometryValidator

class RawLoader:
    """Charge les fichiers Shapefiles, GeoJSON ou GeoPackage dans raw.dataset_imports et raw.import_features."""

    @staticmethod
    def compute_checksum(file_path: Path) -> str:
        """Calcule le hash SHA-256 du fichier pour traçabilité."""
        sha256 = hashlib.sha256()
        with open(file_path, "rb") as f:
            for chunk in iter(lambda: f.read(65536), b""):
                sha256.update(chunk)
        return sha256.hexdigest()

    def load_file(
        self,
        file_path: str,
        source_name: str = "ANAT",
        target_territory_type: str = "COMMUNE",
        metadata: Optional[Dict[str, Any]] = None
    ) -> int:
        """
        Charge un fichier géospatial dans le schéma raw.

        Returns:
            import_id (int): L'identifiant de la session d'importation créée.
        """
        path = Path(file_path)
        if not path.exists():
            raise FileNotFoundError(f"Fichier introuvable : {file_path}")

        checksum = self.compute_checksum(path)
        file_size = path.stat().st_size
        file_format = path.suffix.replace(".", "").upper()
        metadata = metadata or {}

        logger.info(f"Début de l'ingestion brute : [cyan]{path.name}[/] ({file_format}, {file_size / 1024:.1f} KB)")

        # Lecture du jeu de données via GeoPandas / GDAL
        gdf = gpd.read_file(path)
        source_crs = gdf.crs.to_string() if gdf.crs else "EPSG:4326"
        total_features = len(gdf)

        logger.info(f"Fichier parsé avec succès : [green]{total_features} entités[/], CRS détecté : [yellow]{source_crs}[/]")

        with get_db_session() as session:
            # 1. Enregistrement de la session d'importation
            stmt_import = text("""
                INSERT INTO raw.dataset_imports (
                    file_name, file_format, file_size_bytes, checksum,
                    source_name, target_territory_type, total_features, status, metadata
                ) VALUES (
                    :file_name, :file_format, :file_size_bytes, :checksum,
                    :source_name, :target_territory_type, :total_features, 'PARSING', :metadata
                ) RETURNING id;
            """)
            result = session.execute(stmt_import, {
                "file_name": path.name,
                "file_format": file_format,
                "file_size_bytes": file_size,
                "checksum": checksum,
                "source_name": source_name,
                "target_territory_type": target_territory_type.upper(),
                "total_features": total_features,
                "metadata": json.dumps({**metadata, "source_crs": source_crs})
            })
            import_id = result.scalar()

            # 2. Enregistrement du fichier source
            stmt_file = text("""
                INSERT INTO raw.import_files (import_id, file_path, file_type, file_size_bytes)
                VALUES (:import_id, :file_path, :file_type, :file_size_bytes);
            """)
            session.execute(stmt_file, {
                "import_id": import_id,
                "file_path": str(path.absolute()),
                "file_type": path.suffix.lower(),
                "file_size_bytes": file_size
            })

            # 3. Ingestion des entités géospatiales
            stmt_feature = text("""
                INSERT INTO raw.import_features (
                    import_id, feature_index, original_crs, raw_wkt, raw_geometry, properties
                ) VALUES (
                    :import_id, :feature_index, :original_crs, :raw_wkt,
                    ST_SetSRID(ST_GeomFromText(:raw_wkt), 4326),
                    :properties
                );
            """)

            for idx, row in gdf.iterrows():
                geom = row.geometry
                # Reprojection SRID 4326 si besoin
                geom_4326 = GeometryValidator.ensure_srid_4326(geom, source_crs)
                wkt = geom_4326.wkt if geom_4326 else None

                props = row.drop("geometry", errors="ignore").to_dict()
                # Sérialisation propre des types non-JSON (dates, int64, numpy)
                serializable_props = json.loads(json.dumps(props, default=str))

                session.execute(stmt_feature, {
                    "import_id": import_id,
                    "feature_index": idx,
                    "original_crs": source_crs,
                    "raw_wkt": wkt,
                    "properties": json.dumps(serializable_props)
                })

            # Mise à jour du statut
            session.execute(text("""
                UPDATE raw.dataset_imports
                SET status = 'STAGED', updated_at = clock_timestamp()
                WHERE id = :id;
            """), {"id": import_id})

        logger.info(f"[bold green]✓ Ingestion brute terminée ![/] ID de session : [bold]{import_id}[/]")
        return import_id
