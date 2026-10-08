# Senegal – Données Territoriales

**Plateforme nationale de données territoriales du Sénégal** — API REST GeoJSON, ETL PostGIS, Web GIS.

> Dépôt public : `NdiasseFall/donnees-territoriales-sn`
> Stack : Laravel 10 + PostGIS + Next.js 16 + Python/GDAL

---

## Structure du dépôt

| Dossier | Rôle |
|---|---|
| `backend/` | API Laravel 10 (DDD, REST, Sanctum, Spatie) |
| `frontent/` | Web GIS Next.js 16 (MapLibre, Tailwind, Zustand) |
| `etl/` | Pipeline Python 3.11 (GDAL/OGR, GeoPandas) |
| `database/` | Schémas PostGIS (raw/staging/core/published/audit) |
| `docs/` | Docs techniques |
| `.github/workflows/` | CI/CD GitHub Actions |
| `docker-compose.yml` | Environnement Docker (4 services) |

---

## Démarrage rapide

### Prérequis

| Outil | Version |
|---|---|
| Node.js | 22 LTS |
| npm / pnpm | latest |
| PHP | 8.2+ |
| Composer | latest |
| Docker & Docker Compose | latest |
| Git | latest |

### 1. Clone

```bash
git clone https://github.com/NdiasseFall/donnees-territoriales-sn.git
cd donnees-territoriales-sn
```

### 2. Docker Compose (recommandé)

```bash
docker compose up -d
```

Démarre 4 services : postgres, nginx, php, etl.

### 3. Frontend

```bash
cd frontent
npm ci
npm run dev        # http://localhost:3000
```

### 4. Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve   # http://localhost:8000
```

---

## Architecture technique

### Backend (Laravel 10 – DDD)

```text
app/
├── Domain/          # Entités métier pure (Auth, Dataset, Territory)
├── Application/     # Use cases, Jobs
├── Infrastructure/  # Repositories / PostGIS
├── Http/            # Contrôleurs, API Resources
└── Models/          # Eloquent
```

- ORM : Eloquent 10, Auth : Sanctum (JWT), Permissions : Spatie, Base : PostgreSQL 15 + PostGIS

### Frontend (Next.js 16 – TypeScript strict)


---

## Tests

### Backend (PHPUnit)

```bash
cd backend
composer install
vendor/bin/phpunit --testdox
```

> Les tests nécessitent une instance PostGIS réelle (fournie par Docker Compose).

### Frontend (Jest + TypeScript)

```bash
cd frontent
npm ci
npm run lint       # ESLint strict
npm test           # Jest
npx tsc --noEmit   # TypeScript check
npm run build      # Build Next.js


---

## Branches et flux de travail

### Branches principales

| Branche | Rôle |
|---|---|
| `main` | Version stable, **PROTÉGÉE** (PR + 3 checks requis) |
| `feat/*` | Nouvelles fonctionnalités |
| `fix/*` | Corrections |
| `docs/*` | Documentation |

### Commit conventionnels

- `feat:` Nouvelle fonctionnalité
- `fix:` Correction de bogue
- `docs:` Documentation
- `refactor:` Refactoring
- `chore:` Maintenance

### Flux de travail

1. Créez une branche : `git checkout -b feat/votre-feature`
2. Commitez avec un message conventionnel
3. Poussez : `git push -u origin feat/votre-feature`
4. Ouvrez une **Pull Request** vers `main`
5. Les **3 checks CI** doivent passer
6. Merge manuel via l'interface GitHub

> ⚠️ **Interdit sur `main`** : merge commits, push direct, commits avec `any`.

---

## Configuration de l'environnement

### Backend (`backend/.env`)

```ini
APP_NAME=Données Territoriales Sénégal
APP_ENV=local
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=territorial_test
DB_USERNAME=territorial
DB_PASSWORD=territorial


---

## Docker Compose

### Services

```yaml
services:
  postgres:      # PostGIS 15.3
  nginx:         # Proxy Reverse
  php:           # API Laravel 10
  etl:           # ETL Python/GDAL
```

### Commandes

```bash
docker compose up -d              # Démarrer
docker compose logs -f postgres   # Suivre les logs
docker compose exec php bash      # Shell PHP
docker compose down               # Arrêter
```

SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:8000
```

### Frontend (`frontent/.env.local`)

```ini
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
```

### ETL (`etl/config.py`)

```python
DB_URL = "postgresql://user:pass@localhost/dbname"
RAW_SCHEMA = "raw"
STAGING_SCHEMA = "staging"
CORE_SCHEMA = "core"
PUBLISHED_SCHEMA = "published"
```

```

### CI/CD (GitHub Actions)

Le pipeline `.github/workflows/ci.yml` exécute 3 checks requis à chaque PR :

| Job | Rôle | Critères |
|---|---|---|
| `backend` | PHPUnit + PostGIS | 47 tests, extensions installées |
| `lint` | Qualité de code | PHP syntaxe, Laravel Pint |
| `frontend` | tsc, ESLint, Jest, build | tsc ok, lint ok, tests ok, build ok |


```text
frontent/src/
├── app/         # Pages / routes
├── components/  # React (cartes, UI)
├── lib/         # Store, utils
├── types/       # Types TypeScript
└── __tests__/   # Jest
```

- UI : Next.js + React 19 + Tailwind 4
- Cartographie : MapLibre GL JS
- State : Zustand
- Tests : Jest + Testing Library

### ETL (Python 3.11 – GDAL/OGR)

```text
etl/
├── cli.py         # CLI d'entrée
├── config.py      # Configuration
├── loaders/       # Chargement brute
├── transformers/  # Transformation
├── validators/    # Validation géométrique
├── seeds/         # Données de test
└── utils/         # Utilitaires
```

- Output : Schémas PostGIS isolés (raw/staging/core/published/audit)
- Validation : ST_IsValid(), ST_MakeValid(), index GIST
