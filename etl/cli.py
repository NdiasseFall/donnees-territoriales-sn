"""
Command Line Interface for the Senegal Territorial Data ETL Pipeline.
"""
import sys
from pathlib import Path
import click
from sqlalchemy import text
from etl.utils.db import get_db_session, check_database_connection
from etl.utils.logger import logger, console
from etl.loaders.raw_loader import RawLoader
from etl.transformers.staging_transformer import StagingTransformer
from etl.promoters.core_promoter import CorePromoter
from etl.seeds.senegal_seed_data import seed_senegal_administrative_data

@click.group()
def cli():
    """Outil CLI ETL et d'administration des données territoriales du Sénégal."""
    pass

@cli.command()
def check_db():
    """Vérifie la connectivité à PostgreSQL et l'extension PostGIS."""
    try:
        if check_database_connection():
            logger.info("[bold green]✓ Connexion PostGIS opérationnelle ![/]")
        else:
            logger.error("[bold red]✗ Échec de connexion ou PostGIS non détecté.[/]")
            sys.exit(1)
    except Exception as e:
        logger.error(f"[bold red]Erreur de connexion : {e}[/]")
        sys.exit(1)

@cli.command()
@click.option("--sql-dir", default="database/sql", help="Répertoire contenant les fichiers SQL de migration.")
def migrate(sql_dir: str):
    """Exécute les scripts SQL PostGIS d'initialisation des schémas et tables."""
    sql_path = Path(sql_dir)
    if not sql_path.exists():
        logger.error(f"[bold red]Répertoire SQL introuvable : {sql_dir}[/]")
        sys.exit(1)

    scripts = [
        "00_init_extensions_schemas.sql",
        "01_create_audit_schema.sql",
        "02_create_core_schema.sql",
        "03_create_raw_schema.sql",
        "04_create_staging_schema.sql",
        "05_create_published_schema.sql",
        "06_spatial_helper_functions.sql"
    ]

    with get_db_session() as session:
        for script_name in scripts:
            file_path = sql_path / script_name
            if file_path.exists():
                logger.info(f"Exécution de la migration : [cyan]{script_name}[/]...")
                with open(file_path, "r", encoding="utf-8") as f:
                    session.execute(text(f.read()))
            else:
                logger.warning(f"Fichier non trouvé : {script_name}")

    logger.info("[bold green]✓ Toutes les migrations SQL PostGIS ont été exécutées avec succès ![/]")

@cli.command()
@click.argument("file_path", type=click.Path(exists=True))
@click.option("--source", default="ANAT", help="Nom de l'organisme source.")
@click.option("--type", "target_type", default="COMMUNE", help="Type de territoire cible (REGION, DEPARTMENT, COMMUNE, LOCALITY).")
def load_raw(file_path: str, source: str, target_type: str):
    """Charge un fichier Shapefile/GeoJSON/GeoPackage dans le schéma raw."""
    loader = RawLoader()
    import_id = loader.load_file(file_path, source_name=source, target_territory_type=target_type)
    logger.info(f"Session d'import créée : [bold green]{import_id}[/]")

@cli.command()
@click.argument("import_id", type=int)
def process_staging(import_id: int):
    """Transforme, nettoie et valide les données de raw vers staging."""
    transformer = StagingTransformer()
    report = transformer.process_import(import_id)
    console.print_json(data=report)

@cli.command()
@click.argument("import_id", type=int)
@click.option("--slug", default="decoupage-administratif", help="Slug du jeu de données.")
@click.option("--version", "version_str", default="1.0", help="Numéro de version.")
@click.option("--publish/--no-publish", default=True, help="Publier automatiquement après validation.")
def promote(import_id: int, slug: str, version_str: str, publish: bool):
    """Promeut les données validées de staging vers core et published."""
    promoter = CorePromoter()
    version_id = promoter.promote_to_core(
        import_id=import_id,
        dataset_slug=slug,
        version_str=version_str,
        auto_publish=publish
    )
    logger.info(f"Promotion achevée. ID version : [bold green]{version_id}[/]")

@cli.command()
@click.argument("file_path", type=click.Path(exists=True))
@click.option("--source", default="ANAT", help="Source du jeu de données.")
@click.option("--type", "target_type", default="COMMUNE", help="Type de territoire.")
@click.option("--version", "version_str", default="1.0", help="Version.")
def ingest(file_path: str, source: str, target_type: str, version_str: str):
    """Exécute le pipeline ETL de bout en bout (Raw -> Staging -> Core -> Published)."""
    logger.info(f"[bold cyan]=== Lancement du Pipeline ETL Complet pour {file_path} ===[/]")

    # Étape 1 : Ingestion brute
    loader = RawLoader()
    import_id = loader.load_file(file_path, source_name=source, target_territory_type=target_type)

    # Étape 2 : Transformation & Validation Staging
    transformer = StagingTransformer()
    report = transformer.process_import(import_id)

    # Étape 3 : Promotion & Publication
    promoter = CorePromoter()
    version_id = promoter.promote_to_core(
        import_id=import_id,
        version_str=version_str,
        auto_publish=True
    )

    logger.info(f"[bold green]✓ Pipeline ETL achevé avec succès ! (Import ID: {import_id}, Version ID: {version_id})[/]")

@cli.command()
@click.option("--publish/--no-publish", default=True, help="Publier directement dans published.territories.")
def seed(publish: bool):
    """Initialise le référentiel territorial national (14 Régions, 46 Départements, Communes)."""
    seed_senegal_administrative_data(auto_publish=publish)

if __name__ == "__main__":
    cli()
