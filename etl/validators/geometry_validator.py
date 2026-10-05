"""
Module de validation et réparation géométrique pour PostGIS / Shapely.
"""
from typing import Tuple, Dict, Any, Optional
import shapely
from shapely.geometry import Polygon, MultiPolygon, Point, MultiPoint, mapping
from shapely.validation import make_valid, explain_validity
import pyproj

SENEGAL_BBOX = {
    "min_lon": -18.0,
    "min_lat": 12.0,
    "max_lon": -11.0,
    "max_lat": 17.0
}

class GeometryValidator:
    """Valide, normalise et répare les géométries PostGIS / GeoJSON."""

    @staticmethod
    def ensure_srid_4326(geom: shapely.Geometry, source_crs: Optional[Any] = None) -> shapely.Geometry:
        """Reprojette une géométrie vers EPSG:4326 (WGS84) si un CRS source est spécifié."""
        if source_crs is None or str(source_crs).upper() in ("EPSG:4326", "4326", "WGS 84", "WGS84"):
            return geom

        transformer = pyproj.Transformer.from_crs(source_crs, "EPSG:4326", always_xy=True)
        return shapely.ops.transform(transformer.transform, geom)

    @staticmethod
    def validate_and_repair(geom: shapely.Geometry) -> Tuple[shapely.Geometry, str, Dict[str, Any]]:
        """
        Valide une géométrie, la répare si nécessaire et génère les métadonnées de qualité.

        Returns:
            Tuple[shapely.Geometry, quality_status, validation_report]
        """
        report = {
            "is_empty": False,
            "is_valid_initial": True,
            "repaired": False,
            "within_senegal_bbox": True,
            "errors": []
        }

        if geom is None or geom.is_empty:
            report["is_empty"] = True
            report["errors"].append("Géométrie vide ou nulle")
            return geom, "INVALID", report

        # Vérification BBOX Sénégal
        bounds = geom.bounds  # (minx, miny, maxx, maxy) -> (min_lon, min_lat, max_lon, max_lat)
        if (bounds[0] < SENEGAL_BBOX["min_lon"] or bounds[1] < SENEGAL_BBOX["min_lat"] or
            bounds[2] > SENEGAL_BBOX["max_lon"] or bounds[3] > SENEGAL_BBOX["max_lat"]):
            report["within_senegal_bbox"] = False
            report["errors"].append(f"Géométrie hors limites du Sénégal (bounds: {bounds})")

        # Vérification de la validité topologique
        if not geom.is_valid:
            report["is_valid_initial"] = False
            reason = explain_validity(geom)
            report["errors"].append(f"Invalidité topologique: {reason}")

            # Réparation automatique
            repaired_geom = make_valid(geom)
            report["repaired"] = True
            geom = repaired_geom

        # Homogénéisation des types surfaciques en MultiPolygon
        if isinstance(geom, Polygon):
            geom = MultiPolygon([geom])
        elif isinstance(geom, Point):
            pass  # Conserver le point tel quel pour les localités

        # Détermination du statut de qualité
        if report["is_empty"]:
            status = "INVALID"
        elif not report["within_senegal_bbox"]:
            status = "WARNING"
        elif report["repaired"]:
            status = "WARNING"
        else:
            status = "VALID"

        return geom, status, report
