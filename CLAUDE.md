# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

National Territorial Data Platform of Senegal (*Plateforme Nationale de Données Territoriales du Sénégal*).
A geospatial infrastructure and Web GIS platform designed to centralize, standardize, validate, and expose Senegalese administrative boundaries and territorial data via REST/GeoJSON APIs.

- **Backend**: Laravel 10+ (Domain-Driven Design), REST API (GeoJSON RFC 7946), Laravel Sanctum, Spatie Permission.
- **Database / GIS**: PostgreSQL 15+ with PostGIS extension, isolated multi-schema architecture (`raw`, `staging`, `core`, `published`, `audit`).
- **ETL / Data Pipeline**: Python 3.11+, GDAL/OGR, GeoPandas, SQLAlchemy, psycopg2.
- **Frontend / Web GIS**: Next.js 14+ (App Router), MapLibre GL JS, Tailwind CSS, TypeScript (strict mode).

---

## Development Commands

### Backend (Laravel)
```bash
# Dependencies & Setup
cd backend && composer install
cp .env.example .env && php artisan key:generate

# Database & Migrations (PostGIS)
php artisan migrate
php artisan migrate --seed
php artisan migrate:fresh --seed

# Server & Queue
php artisan serve
php artisan queue:work

# Testing
php artisan test
php artisan test --filter=TerritoryApiTest
php artisan test tests/Feature/Api/TerritoryTest.php

# Code Style
./vendor/bin/pint
```

### Frontend (Next.js)
```bash
# Dependencies & Setup
cd frontent && npm install

# Development & Build
npm run dev
npm run build
npm run start

# Linting & Type Checking
npm run lint
npx tsc --noEmit

# Testing
npm test
npm test -- -t "MapComponent"
```

### Docker Environment
```bash
# Start all services (PostgreSQL/PostGIS, Redis, Backend, Frontend)
docker compose up -d
docker compose down

# Run backend commands inside container
docker compose exec backend php artisan migrate
docker compose exec backend php artisan test
```

---

## Architecture & Core Design

### 1. Territorial Hierarchy
```
Country (Niveau 0)
└── Region (Niveau 1)
    └── Department (Niveau 2)
        └── Arrondissement (Niveau 3)
            └── Commune (Niveau 4)
                └── Locality (Niveau 5)
                    └── Quarter / Village / Hamlet (Niveau 6)
```

### 2. Multi-Schema PostGIS Strategy
- `raw`: As-imported geospatial datasets (unmodified source data for traceability and audit).
- `staging`: Intermediate data undergoing cleaning, CRS reprojection, deduplication, and topological checks.
- `core`: Official internal validated repository (entities: `territories`, `territory_types`, `territory_relationships`, `geometries`, `datasets`, `sources`, `organizations`).
- `published`: Exposable public data consumed by the public platform and Web GIS (never query `raw` or `staging` directly from public APIs).
- `audit`: Logs of modifications, validations, imports, and administrative actions.

### 3. Backend Architecture (Laravel DDD)
Follow clean layer separation under `backend/app/`:
- `Domain/`: Core business models, interfaces, Value Objects, and business rules (isolated from Eloquent direct usage).
- `Application/`: Use cases, DTOs, Handlers, and background Jobs.
- `Infrastructure/`: PostGIS spatial queries, Eloquent Repository implementations, external services.
- `Http/`: API Controllers, FormRequests, GeoJSON API Resources (`RFC 7946`).

### 4. Geospatial & PostGIS Conventions
- **CRS / SRID**: Always enforce `EPSG:4326` (WGS84) for geometry storage and GeoJSON exchange.
- **Data Types**: Explicit geometry types, e.g., `GEOMETRY(MultiPolygon, 4326)` or `GEOMETRY(Point, 4326)`.
- **Validation**: Enforce `ST_IsValid()` and `ST_MakeValid()` when transitioning from `staging` to `core`.
- **Spatial Indexing**: Mandatory `GIST` indexes on all geometry columns (`geom`).
- **Spatial Queries & Filtering**: Prepared statements using `ST_Intersects`, `ST_Within`, and BBOX queries (`?bbox=min_lon,min_lat,max_lon,max_lat`).

### 5. Frontend & Web GIS Standards
- **Cartography**: MapLibre GL JS with dynamic zoom-based layer rendering (Region → Department → Commune → Locality).
- **TypeScript**: Strict typing required for all props and GeoJSON payloads (`GeoJSON.Feature`, `GeoJSON.FeatureCollection`). No `any`.
- **State Management**: Zustand or React state for map coordinates, active bounding box, selected entity, and layer toggles.

### 6. API Guidelines & Security
- **Versioned API**: Base route `/api/v1/`.
- **Output Format**: GeoJSON format (`application/geo+json`) for spatial endpoints; JSON (`application/json`) with standard `{ data, meta }` structure for standard endpoints.
- **Authentication & Rate Limiting**: Laravel Sanctum for API key management; rate limiting applied per tier (Public: 30-60 req/min, Developers: 100-600 req/min).
