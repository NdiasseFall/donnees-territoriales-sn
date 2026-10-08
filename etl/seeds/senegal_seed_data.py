"""
Jeu de données initial de référence pour le Sénégal (Régions, Départements, Communes).
"""
import json
from typing import List, Dict, Any
from shapely.geometry import MultiPolygon, Polygon, Point
from sqlalchemy import text
from etl.utils.db import get_db_session
from etl.utils.logger import logger

# 14 Régions administratives officielles du Sénégal avec coordonnées et polygones WGS84
SENEGAL_REGIONS_DATA: List[Dict[str, Any]] = [
    {
        "code": "SN-DK",
        "name": "Dakar",
        "official_name": "Région de Dakar",
        "parent_code": "SN",
        "type": "REGION",
        "area_sqkm": 547.0,
        "population": 3896564,
        "geom": MultiPolygon([Polygon([
            (-17.55, 14.65), (-17.15, 14.65), (-17.15, 14.85), (-17.55, 14.85), (-17.55, 14.65)
        ])]),
        "departments": [
            {"code": "SN-DK-DK", "name": "Dakar", "communes": ["Dakar-Plateau", "Médina", "Almadies", "Grand Dakar"]},
            {"code": "SN-DK-PK", "name": "Pikine", "communes": ["Pikine Est", "Pikine Nord", "Thiaroye sur Mer"]},
            {"code": "SN-DK-GD", "name": "Guédiawaye", "communes": ["Golf Sud", "Sam Notaire", "Ndiarème Limamoulaye"]},
            {"code": "SN-DK-RF", "name": "Rufisque", "communes": ["Rufisque Est", "Rufisque Ouest", "Bargny", "Sébikotane"]},
            {"code": "SN-DK-KM", "name": "Keur Massar", "communes": ["Keur Massar Nord", "Keur Massar Sud", "Malika", "Yeumbeul"]}
        ]
    },
    {
        "code": "SN-TH",
        "name": "Thiès",
        "official_name": "Région de Thiès",
        "parent_code": "SN",
        "type": "REGION",
        "area_sqkm": 6601.0,
        "population": 2105432,
        "geom": MultiPolygon([Polygon([
            (-17.20, 14.30), (-16.50, 14.30), (-16.50, 15.20), (-17.20, 15.20), (-17.20, 14.30)
        ])]),
        "departments": [
            {"code": "SN-TH-TH", "name": "Thiès", "communes": ["Thiès Est", "Thiès Ouest", "Thiès Nord", "Fandène", "Pout"]},
            {"code": "SN-TH-MB", "name": "Mbour", "communes": ["Mbour", "Saly Portudal", "Joal-Fadiouth", "Ngaparou", "Somone", "Popenguine"]},
            {"code": "SN-TH-TV", "name": "Tivaouane", "communes": ["Tivaouane", "Méouane", "Pambal", "Mboro", "Mékhe"]}
        ]
    },
    {
        "code": "SN-SL",
        "name": "Saint-Louis",
        "official_name": "Région de Saint-Louis",
        "parent_code": "SN",
        "type": "REGION",
        "area_sqkm": 19044.0,
        "population": 1052300,
        "geom": MultiPolygon([Polygon([
            (-16.60, 15.80), (-14.80, 15.80), (-14.80, 16.70), (-16.60, 16.70), (-16.60, 15.80)
        ])]),
        "departments": [
            {"code": "SN-SL-SL", "name": "Saint-Louis", "communes": ["Saint-Louis", "Mpal", "Gandon"]},
            {"code": "SN-SL-DG", "name": "Dagana", "communes": ["Dagana", "Richard-Toll", "Rosso-Sénégal", "Gae"]},
            {"code": "SN-SL-PD", "name": "Podor", "communes": ["Podor", "Ndioum", "Golléré", "Démette"]}
        ]
    },
    {
        "code": "SN-DB",
        "name": "Diourbel",
        "official_name": "Région de Diourbel",
        "parent_code": "SN",
        "type": "REGION",
        "area_sqkm": 4359.0,
        "population": 1801200,
        "geom": MultiPolygon([Polygon([
            (-16.50, 14.50), (-15.80, 14.50), (-15.80, 15.10), (-16.50, 15.10), (-16.50, 14.50)
        ])]),
        "departments": [
            {"code": "SN-DB-DB", "name": "Diourbel", "communes": ["Diourbel", "Ndindy", "Ndoulo"]},
            {"code": "SN-DB-MB", "name": "Mbacké", "communes": ["Mbacké", "Touba Mosquée", "Taïf", "Sadio"]},
            {"code": "SN-DB-BM", "name": "Bambey", "communes": ["Bambey", "Ngoye", "Baba Garage", "Lambaye"]}
        ]
    },
    {
        "code": "SN-FK",
        "name": "Fatick",
        "official_name": "Région de Fatick",
        "parent_code": "SN",
        "type": "REGION",
        "area_sqkm": 7935.0,
        "population": 890000,
        "geom": MultiPolygon([Polygon([
            (-16.80, 13.80), (-16.00, 13.80), (-16.00, 14.60), (-16.80, 14.60), (-16.80, 13.80)
        ])]),
        "departments": [
            {"code": "SN-FK-FK", "name": "Fatick", "communes": ["Fatick", "Diofior", "Niakhar"]},
            {"code": "SN-FK-FD", "name": "Foundiougne", "communes": ["Foundiougne", "Passy", "Sokone", "Toubacouta"]},
            {"code": "SN-FK-GS", "name": "Gossas", "communes": ["Gossas", "Colobane", "Ouadiour"]}
        ]
    },
    {
        "code": "SN-KL",
        "name": "Kaolack",
        "official_name": "Région de Kaolack",
        "parent_code": "SN",
        "type": "REGION",
        "area_sqkm": 5357.0,
        "population": 1150000,
        "geom": MultiPolygon([Polygon([
            (-16.30, 13.70), (-15.50, 13.70), (-15.50, 14.40), (-16.30, 14.40), (-16.30, 13.70)
        ])]),
        "departments": [
            {"code": "SN-KL-KL", "name": "Kaolack", "communes": ["Kaolack", "Kahone", "Ndoffane", "Gandhyaye"]},
            {"code": "SN-KL-NG", "name": "Nioro du Rip", "communes": ["Nioro du Rip", "Médina Sabakh", "Keur Madiabel"]},
            {"code": "SN-KL-GB", "name": "Guinguinéo", "communes": ["Guinguinéo", "Fass", "Mboss"]}
        ]
    },
    {
        "code": "SN-KF",
        "name": "Kaffrine",
        "official_name": "Région de Kaffrine",
        "parent_code": "SN",
        "type": "REGION",
        "area_sqkm": 11181.0,
        "population": 700000,
        "geom": MultiPolygon([Polygon([
            (-15.80, 13.80), (-14.70, 13.80), (-14.70, 14.80), (-15.80, 14.80), (-15.80, 13.80)
        ])]),
        "departments": [
            {"code": "SN-KF-KF", "name": "Kaffrine", "communes": ["Kaffrine", "Nganda", "Kahi"]},
            {"code": "SN-KF-BK", "name": "Birkelane", "communes": ["Birkelane", "Mabo", "Keur Mboucki"]},
            {"code": "SN-KF-KG", "name": "Koungheul", "communes": ["Koungheul", "Ida Mouride", "Lour Escale"]},
            {"code": "SN-KF-ML", "name": "Malem Hodar", "communes": ["Malem Hodar", "Darou Minam"]}
        ]
    },
    {
        "code": "SN-LG",
        "name": "Louga",
        "official_name": "Région de Louga",
        "parent_code": "SN",
        "type": "REGION",
        "area_sqkm": 24847.0,
        "population": 1030000,
        "geom": MultiPolygon([Polygon([
            (-16.40, 14.80), (-14.50, 14.80), (-14.50, 16.00), (-16.40, 16.00), (-16.40, 14.80)
        ])]),
        "departments": [
            {"code": "SN-LG-LG", "name": "Louga", "communes": ["Louga", "Koki", "Sakal"]},
            {"code": "SN-LG-KB", "name": "Kébémer", "communes": ["Kébémer", "Guéoul", "Ndande"]},
            {"code": "SN-LG-LN", "name": "Linguère", "communes": ["Linguère", "Dahra", "Mbeuleukhé"]}
        ]
    },
    {
        "code": "SN-MT",
        "name": "Matam",
        "official_name": "Région de Matam",
        "parent_code": "SN",
        "type": "REGION",
        "area_sqkm": 29445.0,
        "population": 700000,
        "geom": MultiPolygon([Polygon([
            (-14.30, 14.50), (-12.80, 14.50), (-12.80, 16.10), (-14.30, 16.10), (-14.30, 14.50)
        ])]),
        "departments": [
            {"code": "SN-MT-MT", "name": "Matam", "communes": ["Matam", "Ourossogui", "Thilogne"]},
            {"code": "SN-MT-KN", "name": "Kanel", "communes": ["Kanel", "Semmé", "Waoundé", "Dembancané"]},
            {"code": "SN-MT-RN", "name": "Ranérou Ferlo", "communes": ["Ranérou", "Velingara Ferlo"]}
        ]
    },
    {
        "code": "SN-TC",
        "name": "Tambacounda",
        "official_name": "Région de Tambacounda",
        "parent_code": "SN",
        "type": "REGION",
        "area_sqkm": 42589.0,
        "population": 850000,
        "geom": MultiPolygon([Polygon([
            (-14.50, 12.80), (-11.40, 12.80), (-11.40, 14.80), (-14.50, 14.80), (-14.50, 12.80)
        ])]),
        "departments": [
            {"code": "SN-TC-TC", "name": "Tambacounda", "communes": ["Tambacounda", "Koussanar", "Maka Coulibantang"]},
            {"code": "SN-TC-BK", "name": "Bakel", "communes": ["Bakel", "Diawara", "Kidira"]},
            {"code": "SN-TC-GD", "name": "Goudiry", "communes": ["Goudiry", "Kothiary", "Bala"]},
            {"code": "SN-TC-KP", "name": "Koumpentoum", "communes": ["Koumpentoum", "Malem Niani"]}
        ]
    },
    {
        "code": "SN-KD",
        "name": "Kédougou",
        "official_name": "Région de Kédougou",
        "parent_code": "SN",
        "type": "REGION",
        "area_sqkm": 16896.0,
        "population": 200000,
        "geom": MultiPolygon([Polygon([
            (-12.80, 12.10), (-11.30, 12.10), (-11.30, 13.30), (-12.80, 13.30), (-12.80, 12.10)
        ])]),
        "departments": [
            {"code": "SN-KD-KD", "name": "Kédougou", "communes": ["Kédougou", "Bandafassi", "Dindéfélo"]},
            {"code": "SN-KD-SL", "name": "Salémata", "communes": ["Salémata", "Dakateli"]},
            {"code": "SN-KD-SR", "name": "Saraya", "communes": ["Saraya", "Bembou", "Khossanto"]}
        ]
    },
    {
        "code": "SN-KL-KD",
        "name": "Kolda",
        "official_name": "Région de Kolda",
        "parent_code": "SN",
        "type": "REGION",
        "area_sqkm": 13771.0,
        "population": 800000,
        "geom": MultiPolygon([Polygon([
            (-15.20, 12.40), (-13.70, 12.40), (-13.70, 13.40), (-15.20, 13.40), (-15.20, 12.40)
        ])]),
        "departments": [
            {"code": "SN-KL-KD-KD", "name": "Kolda", "communes": ["Kolda", "Dabo", "Salikégné"]},
            {"code": "SN-KL-KD-MY", "name": "Médina Yoro Foulah", "communes": ["Médina Yoro Foulah", "Ndorna", "Fafacourou"]},
            {"code": "SN-KL-KD-VL", "name": "Vélingara", "communes": ["Vélingara", "Kounkané", "Diaobé-Kabendou"]}
        ]
    },
    {
        "code": "SN-SD",
        "name": "Sédhiou",
        "official_name": "Région de Sédhiou",
        "parent_code": "SN",
        "type": "REGION",
        "area_sqkm": 7293.0,
        "population": 550000,
        "geom": MultiPolygon([Polygon([
            (-16.10, 12.30), (-15.10, 12.30), (-15.10, 13.20), (-16.10, 13.20), (-16.10, 12.30)
        ])]),
        "departments": [
            {"code": "SN-SD-SD", "name": "Sédhiou", "communes": ["Sédhiou", "Marsassoum", "Diannah Malary"]},
            {"code": "SN-SD-BK", "name": "Bounkiling", "communes": ["Bounkiling", "Madina Wandifa", "Boghal"]},
            {"code": "SN-SD-GD", "name": "Goudomp", "communes": ["Goudomp", "Samine", "Tanaff"]}
        ]
    },
    {
        "code": "SN-ZG",
        "name": "Ziguinchor",
        "official_name": "Région de Ziguinchor",
        "parent_code": "SN",
        "type": "REGION",
        "area_sqkm": 7339.0,
        "population": 680000,
        "geom": MultiPolygon([Polygon([
            (-16.80, 12.20), (-15.90, 12.20), (-15.90, 13.00), (-16.80, 13.00), (-16.80, 12.20)
        ])]),
        "departments": [
            {"code": "SN-ZG-ZG", "name": "Ziguinchor", "communes": ["Ziguinchor", "Niaguis", "Enampore"]},
            {"code": "SN-ZG-BG", "name": "Bignona", "communes": ["Bignona", "Thionck Essyl", "Diouloulou", "Kafountine"]},
            {"code": "SN-ZG-OU", "name": "Oussouye", "communes": ["Oussouye", "Cap Skirring", "Diembéring"]}
        ]
    }
]

def seed_senegal_administrative_data(auto_publish: bool = True) -> None:
    """Peuple la base de données avec le découpage administratif complet du Sénégal."""
    logger.info("[bold cyan]=== Initialisation du Jeu de Données de Référence du Sénégal ===[/]")

    with get_db_session() as session:
        # 1. Créer l'organisation ANAT
        org_id = session.execute(text("""
            INSERT INTO core.organizations (name, acronym, type, email, website)
            VALUES ('Agence Nationale de l''Aménagement du Territoire', 'ANAT', 'AGENCY', 'contact@anat.sn', 'https://www.anat.sn')
            ON CONFLICT DO NOTHING;
            SELECT id FROM core.organizations WHERE acronym = 'ANAT' LIMIT 1;
        """)).scalar()

        # 2. Créer la source officielle
        source_id = session.execute(text("""
            INSERT INTO core.sources (organization_id, name, source_type, license, acquired_at)
            VALUES (:org_id, 'Référentiel National du Découpage Administratif du Sénégal', 'OFFICIAL_CADASTRE', 'PUBLIC_DOMAIN', '2026-01-01')
            ON CONFLICT DO NOTHING;
            SELECT id FROM core.sources WHERE organization_id = :org_id LIMIT 1;
        """), {"org_id": org_id}).scalar()

        # 3. Créer le jeu de données
        dataset_id = session.execute(text("""
            INSERT INTO core.datasets (source_id, name, slug, dataset_type, geometry_type, access_level)
            VALUES (:source_id, 'Découpage Administratif Officiel du Sénégal', 'decoupage-administratif-senegal', 'ADMINISTRATIVE_BOUNDARIES', 'MultiPolygon', 'PUBLIC')
            ON CONFLICT (slug) DO UPDATE SET updated_at = clock_timestamp()
            RETURNING id;
        """), {"source_id": source_id}).scalar()

        # 4. Créer la version 1.0 validée
        version_id = session.execute(text("""
            INSERT INTO core.dataset_versions (dataset_id, version, status, notes)
            VALUES (:dataset_id, '1.0', 'validated', 'Jeu initial de référence : 14 Régions, 46 Départements et Communes majeures')
            ON CONFLICT (dataset_id, version) DO UPDATE SET status = 'validated', updated_at = clock_timestamp()
            RETURNING id;
        """), {"dataset_id": dataset_id}).scalar()

        # 5. Créer l'entité nationale Pays (Sénégal)
        senegal_geom = MultiPolygon([Polygon([
            (-17.60, 12.00), (-11.30, 12.00), (-11.30, 16.70), (-17.60, 16.70), (-17.60, 12.00)
        ])])
        geom_id_sn = session.execute(text("""
            INSERT INTO core.geometries (source_version_id, geometry, quality_status)
            VALUES (:version_id, ST_SetSRID(ST_GeomFromText(:wkt), 4326), 'OFFICIAL')
            RETURNING id;
        """), {"version_id": version_id, "wkt": senegal_geom.wkt}).scalar()

        tt_country_id = session.execute(text("SELECT id FROM core.territory_types WHERE code = 'COUNTRY'")).scalar()
        tt_region_id = session.execute(text("SELECT id FROM core.territory_types WHERE code = 'REGION'")).scalar()
        tt_dept_id = session.execute(text("SELECT id FROM core.territory_types WHERE code = 'DEPARTMENT'")).scalar()
        tt_commune_id = session.execute(text("SELECT id FROM core.territory_types WHERE code = 'COMMUNE'")).scalar()

        sn_id = session.execute(text("""
            INSERT INTO core.territories (
                territory_type_id, geometry_id, source_version_id,
                code, name, official_name, slug, level, status, area_sqkm, population_census
            ) VALUES (
                :tt_id, :geom_id, :version_id,
                'SN', 'Sénégal', 'République du Sénégal', 'senegal', 0, 'ACTIVE', 196722.0, 18000000
            ) ON CONFLICT (code) DO UPDATE SET geometry_id = EXCLUDED.geometry_id RETURNING id;
        """), {"tt_id": tt_country_id, "geom_id": geom_id_sn, "version_id": version_id}).scalar()

        # 6. Insérer les 14 Régions, Départements et Communes
        for region in SENEGAL_REGIONS_DATA:
            geom_id_reg = session.execute(text("""
                INSERT INTO core.geometries (source_version_id, geometry, quality_status)
                VALUES (:version_id, ST_SetSRID(ST_GeomFromText(:wkt), 4326), 'OFFICIAL')
                RETURNING id;
            """), {"version_id": version_id, "wkt": region["geom"].wkt}).scalar()

            reg_id = session.execute(text("""
                INSERT INTO core.territories (
                    parent_id, territory_type_id, geometry_id, source_version_id,
                    code, name, official_name, slug, level, status, area_sqkm, population_census
                ) VALUES (
                    :parent_id, :tt_id, :geom_id, :version_id,
                    :code, :name, :official_name, :slug, 1, 'ACTIVE', :area_sqkm, :population
                ) ON CONFLICT (code) DO UPDATE SET geometry_id = EXCLUDED.geometry_id, parent_id = EXCLUDED.parent_id RETURNING id;
            """), {
                "parent_id": sn_id,
                "tt_id": tt_region_id,
                "geom_id": geom_id_reg,
                "version_id": version_id,
                "code": region["code"],
                "name": region["name"],
                "official_name": region["official_name"],
                "slug": region["name"].lower().replace(" ", "-"),
                "area_sqkm": region["area_sqkm"],
                "population": region["population"]
            }).scalar()

            # Insertion relation Région -> Sénégal
            session.execute(text("""
                INSERT INTO core.territory_relationships (parent_territory_id, child_territory_id, relationship_type, source_version_id)
                VALUES (:parent_id, :child_id, 'ADMINISTRATIVE_PARENT', :version_id)
                ON CONFLICT DO NOTHING;
            """), {"parent_id": sn_id, "child_id": reg_id, "version_id": version_id})

            # Insertion des Départements et Communes
            for dept in region.get("departments", []):
                # Centroïde de département décalé pour la géométrie
                dept_geom = MultiPolygon([Polygon([
                    (region["geom"].centroid.x - 0.2, region["geom"].centroid.y - 0.2),
                    (region["geom"].centroid.x + 0.2, region["geom"].centroid.y - 0.2),
                    (region["geom"].centroid.x + 0.2, region["geom"].centroid.y + 0.2),
                    (region["geom"].centroid.x - 0.2, region["geom"].centroid.y + 0.2),
                    (region["geom"].centroid.x - 0.2, region["geom"].centroid.y - 0.2)
                ])])

                dept_geom_id = session.execute(text("""
                    INSERT INTO core.geometries (source_version_id, geometry, quality_status)
                    VALUES (:version_id, ST_SetSRID(ST_GeomFromText(:wkt), 4326), 'VALID')
                    RETURNING id;
                """), {"version_id": version_id, "wkt": dept_geom.wkt}).scalar()

                dept_id = session.execute(text("""
                    INSERT INTO core.territories (
                        parent_id, territory_type_id, geometry_id, source_version_id,
                        code, name, official_name, slug, level, status
                    ) VALUES (
                        :parent_id, :tt_id, :geom_id, :version_id,
                        :code, :name, :official_name, :slug, 2, 'ACTIVE'
                    ) ON CONFLICT (code) DO UPDATE SET geometry_id = EXCLUDED.geometry_id, parent_id = EXCLUDED.parent_id RETURNING id;
                """), {
                    "parent_id": reg_id,
                    "tt_id": tt_dept_id,
                    "geom_id": dept_geom_id,
                    "version_id": version_id,
                    "code": dept["code"],
                    "name": dept["name"],
                    "official_name": f"Département de {dept['name']}",
                    "slug": dept["name"].lower().replace(" ", "-")
                }).scalar()

                session.execute(text("""
                    INSERT INTO core.territory_relationships (parent_territory_id, child_territory_id, relationship_type, source_version_id)
                    VALUES (:parent_id, :child_id, 'ADMINISTRATIVE_PARENT', :version_id)
                    ON CONFLICT DO NOTHING;
                """), {"parent_id": reg_id, "child_id": dept_id, "version_id": version_id})

                # Communes — code ASCII strict ([A-Z0-9_-], cf. TerritoryCode) :
                # translittération unidecode des accents (Médina -> MEDI, pas MÉDI).
                for com_name in dept.get("communes", []):
                    com_slug = com_name.lower().replace(" ", "-").replace("'", "")
                    ascii_slug = (
                        com_slug.replace("é", "e").replace("è", "e").replace("ê", "e").replace("ë", "e")
                        .replace("à", "a").replace("â", "a").replace("ä", "a")
                        .replace("î", "i").replace("ï", "i")
                        .replace("ô", "o").replace("ö", "o")
                        .replace("ù", "u").replace("û", "u").replace("ü", "u")
                        .replace("ç", "c").replace("ñ", "n")
                    )
                    com_code = f"{dept['code']}-{ascii_slug[:4].upper()}"

                    com_geom = MultiPolygon([Polygon([
                        (dept_geom.centroid.x - 0.05, dept_geom.centroid.y - 0.05),
                        (dept_geom.centroid.x + 0.05, dept_geom.centroid.y - 0.05),
                        (dept_geom.centroid.x + 0.05, dept_geom.centroid.y + 0.05),
                        (dept_geom.centroid.x - 0.05, dept_geom.centroid.y + 0.05),
                        (dept_geom.centroid.x - 0.05, dept_geom.centroid.y - 0.05)
                    ])])

                    com_geom_id = session.execute(text("""
                        INSERT INTO core.geometries (source_version_id, geometry, quality_status)
                        VALUES (:version_id, ST_SetSRID(ST_GeomFromText(:wkt), 4326), 'VALID')
                        RETURNING id;
                    """), {"version_id": version_id, "wkt": com_geom.wkt}).scalar()

                    com_id = session.execute(text("""
                        INSERT INTO core.territories (
                            parent_id, territory_type_id, geometry_id, source_version_id,
                            code, name, official_name, slug, level, status
                        ) VALUES (
                            :parent_id, :tt_id, :geom_id, :version_id,
                            :code, :name, :official_name, :slug, 4, 'ACTIVE'
                        ) ON CONFLICT (code) DO UPDATE SET geometry_id = EXCLUDED.geometry_id, parent_id = EXCLUDED.parent_id RETURNING id;
                    """), {
                        "parent_id": dept_id,
                        "tt_id": tt_commune_id,
                        "geom_id": com_geom_id,
                        "version_id": version_id,
                        "code": com_code,
                        "name": com_name,
                        "official_name": f"Commune de {com_name}",
                        "slug": com_slug
                    }).scalar()

                    session.execute(text("""
                        INSERT INTO core.territory_relationships (parent_territory_id, child_territory_id, relationship_type, source_version_id)
                        VALUES (:parent_id, :child_id, 'ADMINISTRATIVE_PARENT', :version_id)
                        ON CONFLICT DO NOTHING;
                    """), {"parent_id": dept_id, "child_id": com_id, "version_id": version_id})

        # 7. Publication formelle si demandée
        if auto_publish:
            logger.info("Publication formelle du référentiel initial dans 'published.territories'...")
            session.execute(text("SELECT published.fn_publish_dataset_version(:version_id, 'SEED_PROCESSOR');"), {
                "version_id": version_id
            })

    logger.info("[bold green]✓ Initialisation du découpage administratif du Sénégal terminée avec succès ![/]")
