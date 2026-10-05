"""
Database utilities and session management for PostGIS.
"""
from contextlib import contextmanager
from sqlalchemy import create_engine, text
from sqlalchemy.orm import sessionmaker, declarative_base
from etl.config import DATABASE_URL

engine = create_engine(
    DATABASE_URL,
    pool_size=10,
    max_overflow=20,
    pool_pre_ping=True
)

SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
Base = declarative_base()

@contextmanager
def get_db_session():
    """Fournit une session de base de données transactionnelle."""
    session = SessionLocal()
    try:
        yield session
        session.commit()
    except Exception:
        session.rollback()
        raise
    finally:
        session.close()

def check_database_connection() -> bool:
    """Vérifie la connexion à PostgreSQL et la présence de PostGIS."""
    with engine.connect() as conn:
        result = conn.execute(text("SELECT PostGIS_Version();")).fetchone()
        if result:
            return True
    return False
