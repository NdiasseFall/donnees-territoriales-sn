# =============================================================================
# ETL Python (pipeline ETL + seed du référentiel) — etl/
# Contexte de build = racine du dépôt (les imports sont `etl.*`)
# =============================================================================

FROM python:3.11-slim

ENV PYTHONUNBUFFERED=1 \
    PYTHONDONTWRITEBYTECODE=1 \
    PIP_NO_CACHE_DIR=1

WORKDIR /app

# Couche dépendances (mise en cache tant que requirements.txt ne change pas).
COPY etl/requirements.txt ./etl/requirements.txt
RUN pip install -r etl/requirements.txt

# Code applicatif.
COPY etl/ ./etl/

# Les imports (etl.cli → etl.*) résolvent via PYTHONPATH=/app.
ENV PYTHONPATH=/app

# Point d'entrée par défaut : aide de la CLI ETL.
# Seed idempotent : `docker compose --profile tools run --rm etl python -m etl.cli seed`
CMD ["python", "-m", "etl.cli", "--help"]
