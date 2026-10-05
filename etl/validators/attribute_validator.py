"""
Module de validation et normalisation des attributs territoriaux.
"""
import re
import unicodedata
from typing import Dict, Any, Optional

VALID_TERRITORY_LEVELS = {
    "COUNTRY": 0,
    "REGION": 1,
    "DEPARTMENT": 2,
    "ARRONDISSEMENT": 3,
    "COMMUNE": 4,
    "LOCALITY": 5,
    "QUARTER": 6,
    "VILLAGE": 6,
    "HAMLET": 6
}

def slugify(text: str) -> str:
    """Génère un slug URL propre et dénué d'accents."""
    text = unicodedata.normalize('NFKD', text).encode('ascii', 'ignore').decode('utf-8')
    text = re.sub(r'[^\w\s-]', '', text).strip().lower()
    return re.sub(r'[-\s]+', '-', text)

def clean_text(text: Optional[str]) -> str:
    """Nettoie les espaces multiples et normalise les apostrophes."""
    if not text:
        return ""
    text = text.replace("’", "'").strip()
    return re.sub(r'\s+', ' ', text)

class AttributeValidator:
    """Valide et normalise les attributs métier d'un territoire."""

    @staticmethod
    def normalize_attributes(raw_props: Dict[str, Any], territory_type: str) -> Dict[str, Any]:
        """Extrait et normalise les attributs standards à partir des propriétés brutes."""
        type_upper = territory_type.upper()
        if type_upper not in VALID_TERRITORY_LEVELS:
            raise ValueError(f"Type de territoire inconnu : {territory_type}")

        # Recherche flexible des clés courantes (Shapefiles / GeoJSON)
        name_key = next((k for k in raw_props if k.lower() in ["name", "nom", "commune", "region", "dept", "departement", "adm1_fr", "adm2_fr", "adm3_fr", "adm4_fr"]), None)
        code_key = next((k for k in raw_props if k.lower() in ["code", "code_adm", "adm1_pcode", "adm2_pcode", "adm3_pcode", "adm4_pcode", "id", "code_ansd"]), None)
        parent_key = next((k for k in raw_props if k.lower() in ["parent_code", "parent", "code_reg", "code_dept", "code_arr", "code_com"]), None)

        name = clean_text(str(raw_props.get(name_key, "Inconnu"))) if name_key else "Inconnu"
        code = clean_text(str(raw_props.get(code_key, "")).upper()) if code_key else ""
        parent_code = clean_text(str(raw_props.get(parent_key, "")).upper()) if parent_key else None

        slug = slugify(name) if name else "inconnu"

        return {
            "name": name,
            "official_name": raw_props.get("official_name", name),
            "code": code or f"SN-{slug.upper()}",
            "slug": slug,
            "territory_type_code": type_upper,
            "level": VALID_TERRITORY_LEVELS[type_upper],
            "parent_code": parent_code if parent_code else None,
            "code_ansd": raw_props.get("code_ansd"),
            "code_anat": raw_props.get("code_anat"),
            "population_census": raw_props.get("population") or raw_props.get("pop"),
            "properties": raw_props
        }
