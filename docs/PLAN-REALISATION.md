# PLAN DE RÉALISATION — De l'état actuel au déploiement

> **Usage** : document vivant. À mettre à jour à chaque fin de sprint (statuts ✅ 🟡 🔴).
> Sources : `docs/BACKLOG-MVP.md` (US, sprints, blocages), CDC (§10, §74-79, §91-93, §100, §102-103).
> Plan initial : Phase 0 validée le 05/10/2026 — exécution en cours.

---

## 0. État des lieux (snapshot initial)

| Domaine | État | Écart clé |
|---|---|---|
| **US-001** Build reproductible | 🟡 Livré (compose 5 services, 3 Dockerfiles, squelette Laravel booté, health HTTP 200) | Non exécuté sous Docker (machine sans Docker) — **validation requise (jalon J1)** |
| **US-002** CI/CD | 🟡 `ci.yml` (backend PostGIS réel + lint + frontend + images) | Premier exécution au premier push ; protections `main` à activer sur GitHub |
| **US-021** Tests | 🟡 47 tests, PostGIS réel en CI | 1 échec domain (`HierarchyLevel` — arbitrage Phase 0.5) ; validateurs ETL à couvrir |
| **Backend** | 17 routes, boot OK, health 200 | US-012 incomplet, OpenAPI absent, health DB → 503 à faire (US-003) |
| **Frontend** | Prototype 1 route, build + tsc verts | Écrans 01→07 absents ; `eslint`/`jest` à installer (scripts sans deps) |
| **Data** | Schémas SQL + seed ETL idempotent | D-1→D-4 non arbitrés (🔴 bloquent US-004/006/018/019) |
| **Admin** | 0 contrôleur, 0 écran | Back-office complet à faire (S5) |
| **Déploiement** | 0 | Nginx, TLS, compose prod, backups, monitoring, runbook : Phase 6 |

---

## 1. Vue d'ensemble — 6 phases, 14 semaines + runway déploiement

| Sprint | Semaines | Sprint Goal | US livrées | Agent |
|---|---|---|---|---|
| **S0 — Cadrage** | S1–S2 | Décisions §103 actées, architecture figée | D-1 à D-4, Phase 0 | PM + Architecte |
| **S1 — Fondations** | S3–S4 | Environnement reproductible et testable | US-001, US-002, US-021 | QA-DevOps |
| **S2 — Data** | S4–S5 | Import → staging → qualité | US-006, US-007, US-005 | Data-GIS |
| **S3 — API** | S5–S7 | API v1 complète et documentée | US-012, US-013, US-014, US-022 | Backend |
| **S4 — Web GIS** | S7–S9 | Explorateur, recherche, fiches | US-015, US-016, US-017, US-018, US-023 | Frontend |
| **S5 — Admin & Go-Live** | S10–S14 | Publication, recette, mise en production | US-008→011, US-019, US-020, US-003 | Backend + Frontend + QA |

---

## PHASE 0 — Cadrage & prérequis (S0, incompressible)

| # | Action | Livrable | Resp. | Statut |
|---|---|---|---|---|
| 0.1 | **Acter D-1 à D-4** (§103) : datasets ANAT, licences, codes canoniques, relation ANAT/ANSD | Décisions signées dans backlog | PM | 🔴 À faire (hors portée technique) |
| 0.2 | **Initialiser git + dépôt distant** + conventions de commit | Repo local init + `.gitignore` ; remote à créer, `main` protégée | QA-DevOps | 🟡 Local OK, remote à créer |
| 0.3 | **Cible d'infrastructure** : scénario par défaut **VM + Docker Compose** retenu, hébergeur à choisir | Choix final documenté, VM ≥ 4 vCPU/8 Go/100 Go | PM | 🟡 Par défaut acté |
| 0.4 | **Persister le plan** + statuts backlog | Ce document | PM | ✅ |
| 0.5 | **Arbitrage domain « parent direct »** (adjacence de niveau vs graphe de données) | `HierarchyLevel` OU `HierarchyLevelTest` corrigé | Architecte | ✅ Arbitré par CDC §70/§39 : paires parent-enfant encodées dans la VO (commune → département…) |

---

## PHASE 1 — Fondations (S1) : environnement reproductible ET testable

### 1a. US-001 — Validation finale (QA-DevOps)

- [ ] Poste Docker : `docker compose up -d` → 4 services `healthy` (critère 1).
- [ ] Base : extensions `postgis, unaccent, pg_trgm, uuid-ossp` + schémas `raw/staging/core/published/audit` (critère 2).
- [ ] Seed ×2 : `docker compose --profile tools run --rm etl python -m etl.cli seed` → idempotent (critère 3).
- [ ] `docker compose exec backend php artisan test` → suite complète sous PostGIS.

### 1b. US-002 — CI/CD réellement verte (QA-DevOps)

- [x] Job lint strict : `php -l` réel + `vendor/bin/pint --test` (l'ancien `continue-on-error` ne détectait rien).
- [ ] Seed ETL en CI avant tests feature (schéma seul ≠ données) — *si tests feature l'exigent*.
- [ ] Frontend : installer `eslint-config-next` + `jest`/`@testing-library` puis activer les jobs lint/test (les scripts existent, les deps manquent).
- [x] Job **images** : build + push **GHCR** sur tag `v*.*.*` (critère US-002).
- [ ] Sur GitHub : création du remote, `main` protégée (PR obligatoire, checks verts).
- [x] `composer.lock` régénéré sous **PHP 8.2** (plateforme `platform.php=8.2.34` figée dans composer.json).

### 1c. US-021 — Couverture géospatiale complète (QA-DevOps)

- [ ] Valider en CI les perimètres : territoires, RFC 7946, recherche, bbox, reverse-geocode, datasets.
- [ ] Tests des **validateurs ETL** : polygone invalide, géométrie vide, mauvais SRID, code dupliqué → codes d'erreur.
- [ ] Test « `init_all.sql` rejoué + intégrité référentielle » (critère explicite).
- [x] Trancher l'échec `HierarchyLevelTest` (Phase 0.5) — arbitré CDC §70/§39, VO corrigée, 14/14 verts.
- **DoD** : CI verte sur `main`, **0 mock spatial**.

---

## PHASE 2 — Data (S2) : import → staging → qualité

**Précondition : D-1 → D-4 actées.** Ordre séquentiel recommandé :

1. **US-004** — Référentiel complet : 9 `territory_types` (§22), seed normalisé tous niveaux (Pays→Hameau, sans orphelin §70), index (`idx_territories_code`, trigram, GIST), full-text `unaccent` français (§85). *Conditionne tout le reste.*
2. **US-006** — Import ETL : upload SHP/GeoJSON → `raw.*` (original intact) → reprojection **EPSG:4326** → `staging.*` ; `ST_IsValid` / `ST_MakeValid` **jamais silencieux**.
3. **US-007** — Contrôle qualité : rapport (§66), codes d'erreur par défaut de données.
4. **US-005** — Versionnement (Should) : checksum SHA-256, `record_count`, dépréciation sans suppression, diff de versions.

**DoD** : seed complet joué en CI, requête par code → index utilisé, recherche « mbour / M'bour » homogène.

---

## PHASE 3 — API (S3) : v1 complète, documentée, sécurisée

1. **US-012** — Endpoints manquants : `code/{code}`, `/{id}/children`, `/{id}/parents`, `/{id}/geometry` (GeoJSON `application/geo+json`, RFC 7946), `/{id}/map`, filtres `type/level/parent/status/search/page` + `bbox` en **requête préparée** (`ST_Intersects`), erreurs enveloppe §50 (`TERRITORY_NOT_FOUND`).
2. **US-013** — OpenAPI 3.1 : 100 % des routes v1, schéma d'erreur §50 cohérent, `/api/docs` public, headers `X-RateLimit-*` documentés.
3. **US-014** — Reverse-geocode : hiérarchie complète `country → … → locality`, `404` hors emprise, indicateur de confiance.
4. **US-022** — Sécurité : `key_hash` uniquement (§52), 429 + `Retry-After`, SQL préparé partout, matrice RBAC §55, health DB → **503** (critère US-003).
5. Tests feature associés verts en CI.

**DoD** : tests contractuels au vert ; OpenAPI validé par linter (`@redocly/cli`).

---

## PHASE 4 — Web GIS (S4) : l'interface publique

**Prérequis** : seed complet Phase 2 (sinon carte vide — BLK-07).

1. **US-015** — Explorateur : couches commutables, densité par zoom (Région→Département→Commune), sélection/surlignage, panneau latéral, tooltips, tolérance coupure réseau (état : bounds/zoom/entité — Zustand).
2. **US-016** — Recherche : debounce 300 ms, casse/apostrophes, groupement par type, zoom animé ≥400 ms, état vide explicite.
3. **US-017** — Fiche territoriale SSR : `/territoire/{type}/{slug}` (E-08), « Voir sur la carte », « Copier le code », 404 utile.
4. **US-018** — Accueil + catalogue : hero, compteurs, filtres (type/source/format/licence/statut), datasets RESTRICTED invisibles.
5. **US-023** — Non-fonctionnel : perf (recherche <300 ms, API <500 ms, page <3 s), 7 breakpoints, parcours clavier, contrastes ; **tests frontend** (BLK-06).
6. TypeScript strict, **0 `any`**.

**DoD** : Lighthouse ≥ seuils, tests verts, critères §100.2→100.7 démontrés.

---

## PHASE 5 — Back-office & traçabilité (S5a)

1. **US-008** — Import guidé 5 étapes (Upload→Analyse→Mapping→Validation→Publication), blocage publication si rapport non vert, 403 pour PUBLIC/DEVELOPER.
2. **US-009** — Validation humaine : diff attributaire + superposition géométrique, approve / reject (motif obligatoire) / corriger, trace audit.
3. **US-010** — Publication atomique vers `published.*` uniquement (P5) ; plateforme publique **ne lit que `published`**.
4. **US-011** — Journal d'audit (§68) + écran filtrable (utilisateur, action, objet, période).
5. **US-019** — Clés API self-service : clair affiché une seule fois, hash, rotation, stats d'usage.
6. **US-020** — Dashboard d'usage (Could — couper en priorité si dérapage).

---

## PHASE 6 — Recette, préparation prod & DÉPLOIEMENT (S5b — scénario par défaut : VM + Docker Compose)

### 6.0 Provisionnement VM (une fois)

| Élément | Spécification |
|---|---|
| OS | Ubuntu 22.04/24.04 LTS |
| Ressources | ≥ 4 vCPU / 8 Go RAM / 100 Go SSD |
| Réseau | IP fixe, DNS `A` → VM ; **pare-feu : 80/443/SSH seulement** (5432/6379/8000/3000 fermés) |
| Accès | SSH par clé, port non standard, fail2ban, Docker 24+ (`compose-v2`), `certbot`, `postgresql-client` |

### 6.1 Livrables de déploiement

| # | Livrable | Contenu | Statut |
|---|---|---|---|
| 6.1 | `docker-compose.prod.yml` (overlay) | Pas de ports DB/Redis exposés, `restart: always`, `APP_DEBUG=false`, `env_file .env.production`, limites ressources | 🔴 |
| 6.2 | `deploy/nginx/default.conf` | HTTPS→HTTP/2, HSTS, `/api/`→backend:8000, `/`→frontent:3000, `client_max_body_size 512m`, `/healthz` | 🔴 |
| 6.3 | `.env.production.example` | `APP_KEY`, `DB_PASSWORD`, `REDIS requirepass`, `SENTRY_DSN`, CORS strictes | 🔴 |
| 6.4 | Sauvegardes (§75) | `pg_dump` quotidien + rotation 30 j + copie S3 versionnée, hebdo/mensuel, **restauration testée mensuellement** | 🔴 |
| 6.5 | Observabilité (§76) | Sentry (Laravel+Next, corrélé `request_id`), uptime `/api/v1/health`, latence/5xx/CPU/disque, alertes (API/DB down, error rate, disque plein, import échoué) | 🔴 |
| 6.6 | Sécurité (§74) | Firewall, HTTPS auto-renouvelé, secrets hors git, `composer audit`/`npm audit`, en-têtes (HSTS/CSP), RBAC revue | 🔴 |
| 6.7 | `docs/DEPLOIEMENT.md` | Runbook pas-à-pas, **rollback**, restauration, checklist go-live | 🔴 |

### 6.2 Chaîne de livraison

```
dev → PR → CI verte (Pint, PHPUnit+PostGIS, tsc, lint, build)
   → merge main → tag v1.0.0
   → CI : build images backend/frontent → push GHCR
   → VM : compose -f ... pull && up -d
   → healthchecks verts = succès ; sinon rollback image précédente
```

### 6.3 Recette (CDC §91, §100) — obligatoire avant go-live

1. **UAT sur staging = même compose que prod** (critère US-001 : environnement identique).
2. Grille : critères §100.1→100.10 + toutes les US **Must**.
3. Performance (§77) : recherche <300 ms, API <500 ms, page <3 s.
4. Tests de restauration backup + test de rollback.
5. Revue sécurité (§74).

### 6.4 Go-Live (jour J)

1. `.env.production` sur VM (générer `APP_KEY`, mots de passe forts — jamais réutiliser ceux du dev).
2. `compose up -d` → init SQL auto → vérifier extensions + schémas.
3. Seed (profil `tools`) → vérifier idempotence.
4. Certbot : staging d'abord, puis production ; redirect 80→443 actif.
5. DNS `A` → VM (TTL bas).
6. Checklist Go-Live (§ ci-dessous) → observation 48 h → annonce.

### 6.5 Déclencheurs temporels

| Quand | Livrable |
|---|---|
| Dès S1 (parallèle) | `docker-compose.prod.yml`, Nginx, `.env.production.example` |
| S3 | Sentry branché |
| S4 | Tests perf (§77) sur staging |
| S5 pré-recette | Backups + restauration, monitoring + alertes |
| **Fin recette** | **Go-Live** |

---

## Chemin critique & risques

1. **D-1→D-4** (PM) : conditionne S0→S2 — aucun développement Data sans elles.
2. **Remote GitHub + `main` protégée** : sans dépôt, ni US-002, ni livraison d'images.
3. **Validation Docker d'US-001** (machine actuelle sans Docker) : jalon d'entrée de S1.
4. **Seed complet avant S4** : explorateur sans données = démo vide (BLK-07).
5. **Contrat API figé (US-013 OpenAPI) avant US-015** : conditionne le parallélisme Backend/Frontend.
6. **Scope** : US-020 (Could) et V2 (US-024→027) hors MVP — trancher explicitement si dérapage.

---

## Checklist Go-Live

- [ ] D-1→D-4 actées · CI verte sur `main` · **toutes US Must livrées**
- [ ] Recette §91 + grille §100.1→100.10 signée
- [ ] 4 services `healthy` ; `GET /api/v1/health` → `status: healthy` (DB down → **503**)
- [ ] Extensions + 5 schémas vérifiés en prod ; seed chargé
- [ ] DNS + HTTPS valide, renouvellement auto testé
- [ ] Backup nocturne exécuté **et restauration testée**
- [ ] Monitoring + alertes actifs (Sentry sans erreur, uptime vert)
- [ ] `APP_DEBUG=false`, 0 secret en dépôt, ports DB/Redis fermés
- [ ] Perf : recherche <300 ms · API <500 ms · page <3 s
- [ ] Runbook `docs/DEPLOIEMENT.md` publié ; rollback démontré
- [ ] Backlog + ce plan mis à jour (statuts, vélocité S0→S5)

---

## Définition de Done (DoD) générique par US

Une US est **Done** quand : critères BDD vérifiés · tests ajoutés/verts en CI · code Pint propre · doc OpenAPI à jour si routes · statut mis à jour dans `BACKLOG-MVP.md` et dans ce plan · rien de l'interdiction : `any` TS, mock spatial, accès à `raw` depuis l'API publique, `ST_MakeValid` silencieux.




