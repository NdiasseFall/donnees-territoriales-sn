"""
Configuration module for the Senegal Territorial Data ETL Pipeline.
"""
import os
from pathlib import Path
from dotenv import load_dotenv

# Charger les variables d'environnement
load_dotenv()

BASE_DIR = Path(__file__).resolve().parent.parent

# Paramètres Base de Données PostGIS
DB_HOST = os.getenv("DB_HOST", "localhost")
DB_PORT = os.getenv("DB_PORT", "5432")
DB_NAME = os.getenv("DB_DATABASE", "donnees_territoriales_sn")
DB_USER = os.getenv("DB_USERNAME", "postgres")
DB_PASSWORD = os.getenv("DB_PASSWORD", "postgres")

DATABASE_URL = os.getenv(
    "DATABASE_URL",
    f"postgresql://{DB_USER}:{DB_PASSWORD}@{DB_HOST}:{DB_PORT}/{DB_NAME}"
)

# Paramètres Géospatiaux
TARGET_SRID = 4326  # EPSG:4326 (WGS 84)
SIMPLIFICATION_TOLERANCE_DEG = 0.001  # Tolérance de simplification cartographique (~100m)

# Schémas PostGIS
SCHEMA_RAW = "raw"
SCHEMA_STAGING = "staging"
SCHEMA_CORE = "core"
SCHEMA_PUBLISHED = "published"
SCHEMA_AUDIT = "audit"
