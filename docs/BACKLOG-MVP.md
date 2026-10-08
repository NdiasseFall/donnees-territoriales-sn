# BACKLOG DÉTAILLÉ — MVP v1.0

**Plateforme Nationale de Données Territoriales du Sénégal**
Dérivé du *Cahier des Charges Fonctionnel et Technique v2.0* (§104 — artefact n°11)

| | |
|---|---|
| **Version backlog** | 1.0 |
| **Date** | Octobre 2026 |
| **Périmètre** | MVP (CDC §91) |
| **Source de vérité** | `CAHIER DES CHARGES FONCTIONNEL ET TECHNIQUE.md` |
| **Règle** | Toute US référence une section du CDC (traçabilité P1/P2) |

---

## 1. GAP ANALYSIS — ÉTAT INITIAL

### 1.1 Ce qui est déjà livré

| Domaine CDC | Livrable existant | Statut |
|---|---|---|
| §11-15 PostGIS | `database/sql/00_init_extensions_schemas.sql` → `06_spatial_helper_functions.sql` | ✅ |
| §69 Pipeline | `etl/loaders`, `transformers`, `validators`, `promoters`, `seeds`, `cli.py` | ✅ |
| §80 Backend DDD | 79 fichiers PHP — Domain / Application / Infrastructure / Http | ✅ |
| §51-53 Auth | `ApiKeyController`, Sanctum, `throttle:api.key` | ✅ |
| §34-47 API | 16 routes v1 (territories, spatial, search, datasets) | ⚠️ |
| §82 GIS Frontend | `MapComponent.tsx`, MapLibre GL JS, Zustand | ⚠️ |

### 1.2 Écarts bloquants pour la recette (MVP §91)

| # | Écart | Section CDC | Impact MVP |
|---|---|---|---|
| E-01 | Aucune US de test automatisé — `backend/tests/` inexistant | §91, §100 | 🔴 Bloquant UAT |
| E-02 | Back-office absent (0 contrôleur, 0 écran) | §63-68 | 🔴 Bloquant §91 |
| E-03 | OpenAPI 3.1 absent | §61, §102 | 🔴 Bloquant §91 |
| E-04 | Écrans publics 01→07 absents (seul `/` existe) | §56-62 | 🔴 Bloquant §100 |
| E-05 | Endpoints §38-49 : `children`, `parents`, `geometry`, `map` ✅ livrés (US-012) ; restent `versions`, `metadata` | §38-49 | 🟡 Mineur (US-012 done) |
| E-06 | Aucune CI GitHub Actions | §102, §10 | 🟠 Majeur |
| E-07 | `docker-compose` sans services applicatifs | §102, §10 | 🟠 Majeur |
| E-08 | URLs stables `/territoire/{type}/{slug}` absentes | §86 | 🟡 Mineur |

---

## 2. DÉFINITION DE L'ÉPIC 0 — FONDATIONS

> **Décision d'architecture non résolue (CDC §103).** Ces 4 décisions conditionnent le MLD final.
> **Tant qu'elles ne sont pas actées, US-004 (modèle) reste en statut `Blocked`.**

| # | Décision (§103) | Responsable | Échéance | Statut |
|---|---|---|---|---|
| D-1 | Datasets ANAT disponibles et réutilisables | PM + partenaires | S1 | 🔴 À arbitrer |
| D-2 | Règles de diffusion par dataset | PM + juridique | S1 | 🔴 À arbitrer |
| D-3 | Référentiel territorial + codes canoniques | Data-GIS + ANAT | S1 | 🔴 À arbitrer |
| D-4 | Relation ANAT / ANSD / autres sources | Data-GIS | S1 | 🔴 À arbitrer |

---

## 3. EPICS ET USER STORIES

Légende priorité : **P0** = Must (MVP §91) · **P1** = Should (V2) · **P2** = Could

### EPIC 01 — Infrastructure & Industrialisation

**Objectif CDC** : §10, §74, §75, §76, §102 · **Backlog** : INF-001 (P0) · **Agent** : `@Agent-QA-DevOps`

#### US-001 — Chaîne de build reproductible

> **En tant qu'administrateur,** je veux lancer l'ensemble de la plateforme via une seule commande `docker compose up` afin de disposer d'un environnement de développement et de recette identique à la production.

**Critères d'acceptation (BDD)**
- **Given** un poste vierge avec Docker, **When** j'exécute `docker compose up -d`, **Then** PostGIS 15+3.3, Redis 7, backend Laravel et frontend Next.js démarrent et passent leurs healthchecks.
- **Given** le service `postgis`, **When** le conteneur est prêt, **Then** les extensions `postgis`, `unaccent`, `pg_trgm`, `uuid-ossp` sont créées et les schémas `raw/staging/core/published/audit` existent.
- **Given** le projet, **When** j'exécute le seed, **Then** le référentiel territorial de référence est chargé de façon idempotente.

**Point de vigilance** : le `docker-compose.yml` actuel ne déclare que `postgis` et `redis` — les services applicatifs restent à ajouter.

#### US-002 — CI/CD GitHub Actions

> **En tant qu'équipe de développement,** je veux un pipeline automatique qui build, teste et vérifie la qualité à chaque push afin de ne jamais merger de code cassé.

**Critères d'acceptation (BDD)**
- **Given** un push sur `main` ou `develop`, **When** le pipeline démarre, **Then** il exécute lint, tests backend, tests frontend et typecheck dans cet ordre.
- **Given** un job `services`, **When** les tests backend tournent, **Then** une instance PostGIS avec extension PostGIS est disponible (tests géospatiaux réels, pas de mock).
- **Given** un échec de test, **When** le job se termine, **Then** le statut est `failure` et la merge est bloquée par branch protection.
- **Given** un tag `v*.*.*`, **When** le pipeline passe, **Then** une image Docker est publiée sur le registre avec le tag correspondant.

#### US-003 — Observabilité & sauvegardes

> **En tant qu'exploitant,** je veux superviser la plateforme et pouvoir restaurer les données afin de garantir la continuité de service et le respect du principe P2 (traçabilité).

**Critères d'acceptation (BDD)**
- **Given** l'endpoint `GET /api/v1/health`, **When** la base est injoignable, **Then** la réponse indique explicitement l'état `database: down` avec un code HTTP 503.
- **Given** le job de sauvegarde quotidien, **When** il s'exécute, **Then** un dump PostgreSQL est versionné dans le stockage objet avec rétention configurable.
- **Given** des métriques exposées, **When** la latence API dépasse le seuil, **Then** une alerte est déclenchée (§76).

---

### EPIC 02 — Modèle de données & référentiel

**Objectif CDC** : §14, §16-29, §33 · **Backlog** : DATA-001 (P0) · **Agent** : `@Agent-Data-GIS`

#### US-004 — Référentiel territorial complet

> **En tant qu'administrateur de la donnée,** je veux un référentiel territorial normalisé couvrant tous les niveaux (Pays → Hameau) afin de disposer d'une source unique de vérité.

**Critères d'acceptation (BDD)**
- **Given** la table `core.territory_types`, **When** elle est interrogée, **Then** les 9 codes prevus au CDC §22 existent (COUNTRY, REGION, DEPARTMENT, ARRONDISSEMENT, COMMUNE, LOCALITY, QUARTER, VILLAGE, HAMLET) avec leurs niveaux respectifs.
- **Given** un territoire, **When** il est créé, **Then** son `code` est unique, son `slug` est normalisé et son `level` est cohérent avec son type.
- **Given** un ensemble de territoires, **When** la hiérarchie est reconstruite, **Then** il n'existe aucun orphelin : tout territoire autre que le Pays a un parent résolu (§70).
- **Given** une requête sur `core.territories`, **When** elle filtre par code, **Then** l'index unique `idx_territories_code` est utilisé.
- **Given** un code territorial, **When** il est recherché par nom avec ou sans accent, **Then** la recherche full-text française (`to_tsvector('french', unaccent(name))`) retourne le bon résultat (§85).

**Note d'architecture** : les tables existent déjà (§14). La tâche porte sur la **complétude**, la **normalisation du seed** et la **validation des index**.

#### US-005 — Versionnement & traçabilité des datasets

> **En tant que relecteur de données,** je veux comparer deux versions d'un dataset afin de valider une correction sans perdre l'historique (principes P2, P3).

**Critères d'acceptation (BDD)**
- **Given** une nouvelle version d'un dataset, **When** elle est créée, **Then** elle reçoit un `checksum` (SHA-256), un `record_count`, un `release_date` et un `effective_date`, et la version précédente est marquée `deprecated` sans être supprimée.
- **Given** deux versions, **When** je les compare, **Then** l'API expose le nombre de features ajoutées, supprimées et modifiées.
- **Given** une version publiée, **When** elle est modifiée, **Then** la version antérieure reste accessible à son endpoint `/{version}` (§48).

---

### EPIC 03 — Import & Contrôle qualité

**Objectif CDC** : §12, §13, §65, §66, §69, §70 · **Backlog** : DATA-002, DATA-003 (P0) · **Agent** : `@Agent-Data-GIS`

#### US-006 — Import de dataset géospatial

> **En tant qu'éditeur de données,** je veux téléverser un Shapefile ou GeoJSON afin d'alimenter le référentiel sans écrire de SQL.

**Critères d'acceptation (BDD)**
- **Given** un Shapefile (`.shp`, `.dbf`, `.shx`, `.prj`), **When** je l'upload, **Then** le système détecte format, CRS et nombre de features, puis stocke l'original dans `raw.import_files` sans le modifier.
- **Given** un fichier uploadé, **When** l'analyse s'achève, **Then** les features sont décomposées en `raw.import_features` avec index et géométrie brute conservée.
- **Given** une transformation, **When** elle s'exécute, **Then** le CRS est reprojeté en **EPSG:4326** et les features sont écrites dans `staging.territories`.
- **Given** un mapping attributaire, **When** l'utilisateur l'ajuste, **Then** il peut associer les colonnes source aux champs cibles (`code`, `name`, `official_name`, `parent_code`…).
- **Given** une colonne `parent_code` absente, **When** le mapping est soumis, **Then** le système bloque l'import et signale les orphelins détectés (§70).

#### US-007 — Contrôle qualité automatique des géométries

> **En tant que relecteur,** je veux un rapport de qualité automatique avant toute validation humaine afin de ne pas relire des données manifestement défectueuses.

**Critères d'acceptation (BDD)**
- **Given** une géométrie, **When** elle est contrôlée, **Then** `ST_IsValid`, `ST_IsEmpty`, `ST_GeometryType` et `ST_SRID` sont exécutées et un `quality_status` est positionné parmi `UNKNOWN | VALID | WARNING | INVALID | VERIFIED | OFFICIAL` (§31).
- **Given** une géométrie invalide, **When** le contrôle s'achève, **Then** le rapport la liste avec son `explain_validity` et propose une réparation `ST_MakeValid` — **sans repair automatique silencieux**.
- **Given** deux features de même `code`, **When** le contrôle tourne, **Then** elles sont comptabilisées en `duplicate_codes` avec les codes concernés.
- **Given** un dataset, **When** le rapport est généré, **Then** il présente le format du CDC §66 (geometry valid/invalid, duplicate codes, missing parent, invalid CRS).
- **Given** un rapport de qualité, **When** il est consulté, **Then** il est persisté dans `staging.quality_reports` avec `total_features` et les compteurs par critère.

**Point d'attention** : principe P4 — le `quality_status` `VALID` ne doit **jamais** être promu automatiquement en `OFFICIAL` (§31 : *VALID ≠ OFFICIAL*).

---

### EPIC 04 — Publication & Back-office

**Objectif CDC** : §15, §30, §63-68 · **Backlog** : ADMIN-002, ADMIN-003 (P0) · **Agents** : `@Agent-Backend` + `@Agent-Frontend`

#### US-008 — Back-office : import guidé en 5 étapes

> **En tant qu'éditeur de données,** je veux suivre un workflow visuel Upload → Analyse → Mapping → Validation → Publication afin de ne jamais publier sans avoir vu ce qui va être publié.

**Critères d'acceptation (BDD)**
- **Given** un utilisateur avec le rôle `DATA_EDITOR`, **When** il accède à `/admin/datasets`, **Then** il voit la liste des datasets avec statut, version, producteur et dernière mise à jour.
- **Given** l'étape Analyse, **When** l'analyse termine, **Then** l'écran affiche le nombre de features, les types de géométrie détectés et le rapport de qualité (§66).
- **Given** l'étape Validation, **When** des erreurs sont présentes, **Then** l'étape Publication est bloquée tant que le rapport n'est pas au vert ou qu'une correction n'est pas demandée.
- **Given** un utilisateur `PUBLIC` ou `DEVELOPER`, **When** il tente d'accéder à `/admin`, **Then** il reçoit une erreur 403 (matrice §55).

#### US-009 — Validation humaine avec comparaison de versions

> **En tant que relecteur,** je veux comparer visuellement l'ancienne et la nouvelle version, et approuver, rejeter ou demander une correction.

**Critères d'acceptation (BDD)**
- **Given** une version en statut `review`, **When** le relecteur l'ouvre, **Then** il voit l'ancienne version, la nouvelle, les différences d'attributs, les géométries superposables, la source et les métadonnées (§67).
- **Given** une différence détectée, **When** le relecteur clique sur Approve, **Then** la version passe à `validated` et l'entrée correspondante dans `audit.logs` est écrite.
- **Given** un rejet, **When** le relecteur saisit un motif, **Then** le motif est obligatoire, la version passe à `rejected` et l'éditeur est notifié.
- **Given** une demande de correction, **When** elle est émise, **Then** la version repasse à `draft` avec le commentaire attaché.

#### US-010 — Cycle de vie & publication

> **En tant qu'administrateur,** je veux publier une version validée de façon atomique afin que les données exposées soient toujours cohérentes (principe P5 — séparation RAW / PUBLISHED).

**Critères d'acceptation (BDD)**
- **Given** une version `validated`, **When** je lance la publication, **Then** seules les lignes dont `access_level` l'autorise sont copiées vers `published.territories` — et vers rien d'autre.
- **Given** une publication en cours, **When** elle échoue, **Then** l'opération est atomique : aucune donnée partielle n'est visible dans `published` (transaction unique).
- **Given** la plateforme publique, **When** elle interroge la base, **Then** elle ne lit **que** le schéma `published` (§15 — interdiction d'accéder à `raw`).
- **Given** un dataset archivé, **When** l'archivage est lancé, **Then** il disparaît des listes publiques mais reste consultable par les administrateurs.

#### US-011 — Journal d'audit

> **En tant qu'administrateur,** je veux tracer chaque opération sensible afin de satisfaire les principes P2 et P5.

**Critères d'acceptation (BDD)**
- **Given** une opération sensible (import, modification, validation, publication, gestion d'utilisateur), **When** elle s'exécute, **Then** `audit.logs` enregistre : utilisateur, action, objet, ancienne valeur, nouvelle valeur, date, IP et résultat (§68).
- **Given** un administrateur, **When** il ouvre l'écran d'audit, **Then** il peut filtrer par utilisateur, action, objet et période.

---

### EPIC 05 — API Géographique

**Objectif CDC** : §34-50, §100 · **Backlog** : API-001, API-002 (P0) · **Agent** : `@Agent-Backend`

#### US-012 — Compléter les endpoints territoires (écart E-05)

> **En tant qu'intégrateur,** je veux consommer les territoires selon leur code, leur hiérarchie et leur géométrie via des endpoints stables `/api/v1` afin d'intégrer la donnée dans une autre application (critère §100.10).

**Critères d'acceptation (BDD)**
- **Given** un code valide, **When** j'appelle `GET /api/v1/territories/code/{code}`, **Then** je reçois le territoire avec `id`, `code`, `name`, `type`, `level`, `status`.
- **Given** un territoire, **When** j'appelle `GET /api/v1/territories/{id}/children`, **Then** je reçois ses enfants directs ; `GET /{id}/parents` retourne la chaîne complète Sénégal → Thiès → Mbour → Commune de Mbour (§39).
- **Given** un territoire, **When** j'appelle `GET /api/v1/territories/{id}/geometry?format=geojson`, **Then** la réponse est une `Feature` GeoJSON **conforme RFC 7946** servie en `application/geo+json`.
- **Given** un territoire, **When** j'appelle `GET /api/v1/territories/{id}/map`, **Then** la réponse contient `geometry`, `bbox`, `centroid`, `properties` et `children` (§41).
- **Given** des paramètres `type`, `level`, `parent`, `status`, `search`, `page`, `per_page`, **When** j'appelle `GET /api/v1/territories`, **Then** ils sont tous pris en charge avec pagination.
- **Given** un `?bbox=min_lon,min_lat,max_lon,max_lat`, **When** je filtre, **Then** la requête utilise `ST_Intersects` avec requête préparée (pas de concaténation SQL).
- **Given** un code inexistant, **When** je l'appelle, **Then** la réponse suit l'enveloppe d'erreur du §50 : `{"error":{"code":"TERRITORY_NOT_FOUND","message":"..."}}` avec HTTP 404.

#### US-013 — Documentation OpenAPI 3.1

> **En tant que développeur tiers,** je veux consulter une documentation OpenAPI interactive afin d'intégrer l'API sans assistance (CDC §61, §100.9).

**Critères d'acceptation (BDD)**
- **Given** la spécification, **When** je la valide, **Then** elle respecte OpenAPI **3.1** et couvre 100 % des routes v1 avec paramètres, exemples et schémas de réponse.
- **Given** une réponse d'erreur, **When** elle est documentée, **Then** le schéma d'erreur du §50 est appliqué de façon cohérente sur tous les endpoints.
- **Given** la documentation, **When** elle est publiée, **Then** elle est accessible sur `/api/docs` en lecture anonyme, avec exemples `curl`.
- **Given** les limites de débit, **When** je consulte les headers de réponse, **Then** `X-RateLimit-Limit`, `X-RateLimit-Remaining` et `X-RateLimit-Reset` sont documentés.

#### US-014 — Reverse geocoding & filtres

> **En tant qu'application grand public,** je veux transformer des coordonnées en nom de territoire afin d'afficher une localisation en texte lisible (CDC §44).

**Critères d'acceptation (BDD)**
- **Given** `lat` et `lng` dans l'emprise du Sénégal, **When** j'appelle `GET /api/v1/spatial/reverse-geocode`, **Then** je reçois la hiérarchie complète `country → region → department → arrondissement → commune → locality`.
- **Given** un point hors emprise ou dans l'océan, **When** j'appelle le service, **Then** il retourne un `404` explicite, jamais une commune arbitraire.
- **Given** un point proche d'une frontière, **When** je le géocode, **Then** la tolérance est gérée et le résultat porte un indicateur de confiance.

---

### EPIC 06 — Web GIS (écran principal)

**Objectif CDC** : §57, §82, §83, §85, §86 · **Backlog** : GIS-001, GIS-002, GIS-003, TERR-001 (P0) · **Agent** : `@Agent-Frontend`

> **Constat** : seule la route `/` existe. L'explorateur est un prototype (3 composants : `MapComponent`, `Header`, `SidePanel`). Il reste à fiabiliser et à étendre aux écrans 02→04.

#### US-015 — Explorateur cartographique opérationnel

> **En tant qu'utilisateur,** je veux voir le Sénégal et naviguer dans ses limites par zoom afin de comprendre l'organisation administrative du pays (critère §100.2).

**Critères d'acceptation (BDD)**
- **Given** l'écran d'accueil, **When** la carte se charge, **Then** les régions s'affichent avec les couches Régions/Départements/Communes/Arrondissements commutables (§83).
- **Given** un zoom faible, **When** le rendu s'effectue, **Then** seules les régions sont affichées ; à moyenne échelle les départements ; à grande échelle les communes — la densité évolue selon le zoom (§83).
- **Given** un clic sur un territoire, **When** je sélectionne, **Then** sa limite est surlignée, ses attributs s'affichent et le panneau latéral se met à jour (§100.5-100.6).
- **Given** un survol de la carte, **When** le curseur passe sur une entité, **Then** une infobulle affiche nom et type, et le curseur devient un pointeur.
- **Given** une coupure réseau, **When** une source ne répond pas, **Then** les couches déjà chargées restent affichées et l'erreur est journalisée sans casser la page.

#### US-016 — Recherche territoriale depuis la carte

> **En tant qu'utilisateur,** je veux rechercher une commune par son nom afin de la localiser et d'y zoomer automatiquement (critère §100.3).

**Critères d'acceptation (BDD)**
- **Given** la saisie « Mbour », « mbour », « MBOUR » ou « M'bour », **When** je valide, **Then** les mêmes résultats sont retournés (insensibilité à la casse et aux apostrophes, §85).
- **Given** un terme d'au moins 2 caractères, **When** je tape, **Then** la recherche est débattue après un délai de 300 ms et les résultats sont groupés par type de territoire (§58).
- **Given** un résultat cliqué, **When** la sélection est appliquée, **Then** la carte zoome et se centre sur le territoire avec une animation d'au moins 400 ms.
- **Given** une recherche sans résultat, **When** elle se termine, **Then** un message explicite « Aucun résultat pour "…" » est affiché (§58).

#### US-017 — Fiche territoriale & URL stable

> **En tant qu'utilisateur,** je veux consulter la fiche complète d'un territoire et partager son URL afin de faire circuler une référence stable (critère §100.7).

**Critères d'acceptation (BDD)**
- **Given** une URL `/territoire/sn/commune/mbour`, **When** je l'ouvre, **Then** la fiche s'affiche côté serveur (SSR) avec nom, type, code, parent, superficie, centre, statut, source et version (§59).
- **Given** la fiche, **When** je clique sur « Voir sur la carte », **Then** l'explorateur s'ouvre avec ce territoire centré.
- **Given** la fiche, **When** je clique sur « Copier le code », **Then** le code canonique est dans le presse-papier avec un retour visuel de confirmation.
- **Given** un code inexistant, **When** j'ouvre l'URL, **Then** le serveur renvoie une page 404 avec un message utile, pas une page blanche.

---

### EPIC 07 — Portail développeur & Analytics

**Objectif CDC** : §56, §60-62, §88, §99 · **Backlog** : DEV-001, ANALYTICS-001 · **Agents** : `@Agent-Frontend` + `@Agent-Backend`

#### US-018 — Accueil & catalogue de datasets

> **En tant qu'utilisateur,** je veux comprendre ce que la plateforme propose et parcourir les jeux de données disponibles afin de trouver l'information dont j'ai besoin.

**Critères d'acceptation (BDD)**
- **Given** la page d'accueil, **When** elle se charge, **Then** elle affiche un hero, une recherche territoriale, une carte du Sénégal, les compteurs de datasets et l'accès à l'API (§56).
- **Given** le catalogue, **When** je filtre, **Then** je peux filtrer par type, source, format, date, statut et licence (§60).
- **Given** un dataset, **When** je l'ouvre, **Then** je vois nom, description, producteur, version, dernière mise à jour, format et licence (§60).
- **Given** un dataset `RESTRICTED` ou `CONFIDENTIAL`, **When** je le consulte, **Then** il n'est pas listé publiquement et l'API refuse de le servir (licence §73).

#### US-019 — Gestion des clés API

> **En tant que développeur,** je veux gérer mes clés afin de sécuriser l'accès à mon application sans intervention du support (CDC §52, §62).

**Critères d'acceptation (BDD)**
- **Given** un utilisateur authentifié, **When** je crée une clé, **Then** elle est affichée **une seule fois** en clair puis masquée ; seul son `key_hash` est stocké (§52).
- **Given** une clé expirée ou révoquée, **When** elle est utilisée, **Then** la requête est rejetée avec un message explicite.
- **Given** une rotation, **When** je régénère une clé, **Then** l'ancienne est invalidée immédiatement et l'opération est tracée dans l'audit.
- **Given** ma liste d'applications, **When** je la consulte, **Then** je vois pour chaque clé : statut, nombre de requêtes, limite appliquée et dernière utilisation.

#### US-020 — Tableau de bord d'usage

> **En tant que propriétaire de produit,** je veux mesurer l'usage de la plateforme afin de démontrer l'adoption (CDC §63, §88, §99).

**Critères d'acceptation (BDD)**
- **Given** le back-office, **When** j'ouvre le dashboard, **Then** j'y vois datasets, territoires, géométries, utilisateurs, requêtes API, erreurs et validations en attente (§63).
- **Given** les compteurs, **When** je consulte les statistiques d'usage, **Then** elles respectent les règles de protection des données personnelles : aucune donnée personnelle identifiable n'est agrégée ni exposée (§88).
- **Given** un pic de requêtes, **When** j'observe les métriques, **Then** le taux d'erreur et la latence sont visibles pour détecter une dégradation.

---

### EPIC 08 — Qualité, Sécurité & Non-fonctionnel

**Objectif CDC** : §74, §77-79 · **Backlog** : transverses (P0) · **Agents** : `@Agent-QA-DevOps` + tous

#### US-021 — Tests automatisés géospatiaux

> **En tant que lead technique,** je veux une suite de tests couvrant l'API et le pipeline afin de garantir la non-régression sur un système dont les erreurs sont silencieuses.

**Critères d'acceptation (BDD)**
- **Given** la CI, **When** je lance la suite, **Then** elle s'exécute sur une **instance PostGIS réelle** (extension PostGIS active) et non sur des mocks spatiaux.
- **Given** un changeset SQL modifiant un schéma ou une fonction, **When** le test tourne, **Then** `init_all.sql` est rejoué et l'intégrité référentielle est vérifiée.
- **Given** les endpoints, **When** je teste, **Then** le périmètre minimal couvert est : territoires (index, show, hierarchy), géométrie RFC 7946, recherche (casse/accent), bbox, reverse-geocode, datasets.
- **Given** les validateurs ETL, **When** je teste, **Then** un polygone invalide, une géométrie vide, un mauvais SRID et un code dupliqué sont chacun détectés avec le bon code d'erreur.
- **Given** une régression, **When** elle est introduite, **Then** la CI échoue et la merge est bloquée.

#### US-022 — Sécurité applicative & API

> **En tant que responsable sécurité,** je veux que l'authentification, les quotas et la validation des entrées soient effectifs afin de protéger la donnée publique.

**Critères d'acceptation (BDD)**
- **Given** une clé API, **When** elle est stockée, **Then** le clair n'existe jamais en base — seul le `key_hash` l'est (§52).
- **Given** un utilisateur public, **When** il dépasse sa limite, **Then** la requête reçoit HTTP 429 avec les en-têtes `Retry-After` et `X-RateLimit-*`.
- **Given** une entrée utilisateur sur `search` ou `bbox`, **When** elle est traitée, **Then** les requêtes SQL sont préparées — aucune concaténation de valeur dans du SQL.
- **Given** une tentative d'accès à une route `/admin` par un utilisateur non autorisé, **When** elle est faite, **Then** elle est refusée (matrice RBAC §55).

#### US-023 — Non-fonctionnel : performance, responsive, accessibilité

> **En tant qu'utilisateur sur mobile ou sur poste de travail,** je veux une interface rapide et accessible afin de consulter les données sur n'importe quel appareil.

**Critères d'acceptation (BDD)**
- **Given** une recherche, **When** elle est exécutée, **Then** la réponse est **< 300 ms** ; une API standard **< 500 ms** ; la page publique **< 3 s** (§77). Ces cibles sont mesurées par la CI sur un environnement de staging.
- **Given** les breakpoints 375 / 390 / 430 / 768 / 1024 / 1440 / 1920, **When** je navigue sur mobile, tablette et desktop, **Then** aucun débordement horizontal ni élément de carte illisible (§78).
- **Given** un utilisateur clavier uniquement, **When** je navigue, **Then** le focus est visible, l'ordre de tabulation est logique et chaque contrôle a un `label` (§79).
- **Given** un statut affiché, **When** il est représenté, **Then** il n'est pas signalé par la couleur seule — un texte ou une icône accompagne toujours la couleur (§79).

---

## 4. PRIORISATION (MoSCoW + RICE)

### 4.1 MoSCoW

| US | Titre | Epic | MoSCoW | RICE | Agent |
|---|---|---|---|---|---|
| US-001 | Build reproductible | 01 | **Must** | 6.0 | QA-DevOps |
| US-002 | CI/CD GitHub Actions | 01 | **Must** | 5.0 | QA-DevOps |
| US-004 | Référentiel territorial | 02 | **Must** | 8.1 | Data-GIS |
| US-006 | Import de dataset | 03 | **Must** | 5.6 | Data-GIS |
| US-007 | Contrôle qualité | 03 | **Must** | 6.3 | Data-GIS |
| US-008 | Back-office import | 04 | **Must** | 4.8 | Backend + Frontend |
| US-009 | Validation humaine | 04 | **Must** | 5.2 | Backend + Frontend |
| US-010 | Cycle de vie & publication | 04 | **Must** | 7.1 | Backend |
| US-011 | Journal d'audit | 04 | **Must** | 4.4 | Backend |
| US-012 | Endpoints territoires | 05 | **Must** | 7.5 | Backend |
| US-013 | OpenAPI 3.1 | 05 | **Must** | 5.9 | Backend |
| US-015 | Explorateur cartographique | 06 | **Must** | 8.7 | Frontend |
| US-016 | Recherche territoriale | 06 | **Must** | 7.2 | Frontend |
| US-017 | Fiche & URL stable | 06 | **Must** | 6.5 | Frontend |
| US-018 | Accueil & catalogue | 07 | **Must** | 5.4 | Frontend |
| US-021 | Tests automatisés | 08 | **Must** | 9.3 | QA-DevOps |
| US-022 | Sécurité | 08 | **Must** | 8.0 | Backend + QA |
| US-023 | Non-fonctionnel | 08 | **Must** | 5.1 | Frontend + QA |
| US-003 | Observabilité & backups | 01 | **Should** | 4.2 | QA-DevOps |
| US-005 | Versionnement avancé | 02 | **Should** | 4.9 | Data-GIS |
| US-014 | Reverse-geocoding | 05 | **Should** | 5.0 | Backend |
| US-019 | Clés API (self-service) | 07 | **Should** | 4.7 | Backend + Frontend |
| US-020 | Dashboard d'usage | 07 | **Could** | 3.2 | Backend + Frontend |

**RICE** = (Reach × Impact × Confidence) ÷ Effort. Valeurs indicatives, à recalibrer après le cadrage (semaine 1).

### 4.2 V2 / V3 (hors MVP)

| US | Titre | Version | Agent |
|---|---|---|---|
| US-024 | Vector tiles PBF + CDN | V2 | Data-GIS + QA |
| US-025 | Requêtes spatiales (contains / nearby) | V2 | Backend |
| US-026 | Téléchargements avancés (GeoJSON / SHP / PMTiles) | V2 | Backend |
| US-027 | Historique cartographique (comparaison de dates) | V2 | Frontend |
| US-028 | Données statistiques (population, ménages) | V3 | Data-GIS |

---

## 5. PLANIFICATION — 6 SPRINTS (14 semaines, CDC §92-93)

| Sprint | Semaines | Sprint Goal | US livrées | Agent dominant |
|---|---|---|---|---|
| **S0 — Cadrage** | S1–S2 | Décisions §103 actées, architecture figée | D-1 à D-4, US-004 (partiel) | PM + Architecte |
| **S1 — Fondations** | S3–S4 | Environnement reproductible et testable | US-001, US-002, US-021 | QA-DevOps |
| **S2 — Data** | S4–S5 | Pipeline import → staging → qualité | US-006, US-007, US-005 | Data-GIS |
| **S3 — API** | S5–S7 | API v1 complète et documentée | US-012, US-013, US-014, US-022 | Backend |
| **S4 — Web GIS** | S7–S9 | Explorateur, recherche et fiches opérationnels | US-015, US-016, US-017, US-018, US-023 | Frontend |
| **S5 — Admin & Go-Live** | S10–S14 | Publication, recette, mise en production | US-008, US-009, US-010, US-011, US-019, US-020, US-003 | Backend + Frontend + QA |

```
        S1 S2 S3 S4 S5 S6 S7 S8 S9 S10 S11 S12 S13 S14
S0      ██ ██
S1            ██ ██
S2               ██ ██
S3                  ██ ██ ██
S4                        ██ ██ ██
S5                              ██ ██ ██ ██ ██
```

---

## 6. MATRICE D'AFFECTATION PAR AGENT

| Agent | US attribuées | Charge estimée | Points de vigilance |
|---|---|---|---|
| `@Agent-QA-DevOps` | US-001, US-002, US-003, US-021, US-024 | Élevé | PostGIS réel obligatoire en CI — pas de mock spatial |
| `@Agent-Data-GIS` | US-004, US-005, US-006, US-007, US-024, US-028 | Élevé | EPSG:4326 systématique ; jamais de `ST_MakeValid` silencieux |
| `@Agent-Backend` | US-010, US-011, US-012, US-013, US-014, US-022, US-025 | Très élevé | GeoJSON RFC 7946 ; requêtes préparées sur tout `bbox`/`search` |
| `@Agent-Frontend` | US-008, US-009, US-015, US-016, US-017, US-018, US-023 | Très élevé | TypeScript strict — aucun `any` ; chargement par niveau de zoom |
| `@Agent-Architecte` | D-1 à D-4, arbitrage DDD, revue des contrats | Moyen | Aucun modèle Eloquent dans le domaine |
| **Product & Project Manager** | Gap analysis, priorisation, UAT, arbitrages §103, reporting | Continu | **D-1 à D-4 bloquent le MLD final** |

---

## 7. DÉPENDANCES & BLOCAGES

| ID | Dépendance | Bloque | Statut | Action requise |
|---|---|---|---|---|
| BLK-01 | D-3 (référentiel & codes canoniques) | US-004, tout le modèle | 🔴 Ouvert | Valider avec l'ANAT |
| BLK-02 | D-1 / D-2 (datasets ANAT & licences) | US-006, US-018, US-019 | 🔴 Ouvert | Obtenir les autorisations |
| BLK-03 | `backend/tests/` inexistant | US-021, US-022, toute UAT | ✅ Résolu | 47 tests créés (Unit + Feature PostGIS) — couverture US-021 à compléter |
| BLK-04 | Docker sans services applicatifs | US-001, US-021 | ✅ Résolu | Compose 5 services + Dockerfiles livrés — validation sous Docker requise (Phase 1a) |
| BLK-05 | Pas de `.github/workflows/` | US-002, merge protégé | 🟡 Partiel | `ci.yml` créé — remote GitHub + protection `main` restent à faire |
| BLK-06 | Frontend sans tests | US-023 | 🟡 Ouvert | S4 — installer eslint/jest + tests |
| BLK-07 | Pas de seed réel (14 régions) | US-015 démonstration | ✅ Résolu | `etl seed` idempotent disponible (profil `tools`) |

---

## 8. RISQUES (CDC §95)

| Risque | Probabilité | Impact | Mitigation proposée | Responsable |
|---|---|---|---|---|
| **R1** Données ANAT indisponibles | Élevée | 🔴 Critique | Inventaire + accord de diffusion signé avant S2 ; pipeline adaptable | PM |
| **R2** Limites contradictoires entre sources | Moyenne | 🔴 Critique | Version, source, statut, validation humaine obligatoire | Data-GIS |
| **R3** Statut `DISPUTED` utilisé hors règles institutionnelles | Faible | 🟠 Élevé | Geler le statut tant que D-1/D-2 ne sont pas actées | PM + Data-GIS |
| **R4** Géométries trop lourdes | Moyenne | 🟠 Élevé | Simplification + préparation des vector tiles dès S2 | Data-GIS |
| **R5** Normalisation difficile | Élevée | 🟠 Élevé | `staging`, mapping configurable, validation humaine | Data-GIS |
| **R6** Coût infrastructure | Moyenne | 🟡 Modéré | Architecture progressive, cache Redis, CDN | QA-DevOps |
| **R7** Régression silencieuse (données géo) | Élevée | 🔴 Critique | US-021 avec PostGIS réel en CI | QA-DevOps |
| **R8** Endpoints API instables cassant les intégrateurs | Moyenne | 🟠 Élevé | Versionnement `/v1` strict + contrat OpenAPI | Backend |

---

## 9. CRITÈRES DE GO / NO-GO (Recette UAT — CDC §91)

**GO requis — parcours utilisateur (§100)**
- [ ] Ouvrir la plateforme et voir le Sénégal
- [ ] Rechercher une commune (ex. « Mbour »)
- [ ] Cliquer sur le résultat et voir sa limite
- [ ] Voir son territoire parent
- [ ] Consulter ses informations
- [ ] Récupérer sa géométrie
- [ ] Appeler l'API
- [ ] Intégrer la donnée dans une autre application

**GO requis — parcours administrateur (§100)**
- [ ] Importer un dataset
- [ ] Le contrôler
- [ ] Corriger les erreurs
- [ ] Le faire valider
- [ ] Créer une version
- [ ] Publier
- [ ] Exposer les données via API
- [ ] Consulter les logs

**GO requis — transverse**
- [ ] CI verte avec tests PostGIS réels
- [ ] OpenAPI 3.1 complète et validée
- [ ] Cibles de performance mesurées (< 300 ms recherche, < 500 ms API, < 3 s page)
- [ ] Audit de sécurité passé (RBAC, clés hashées, injections SQL)
- [ ] Responsive 375 → 1920 et accessibilité WCAG vérifiées
- [ ] Sauvegardes testées par une restauration réelle

---

## 10. SUIVI D'AVANCEMENT

| Sprint | Capacité (j/h) | Réalisé | Restant | Vélocité | Statut |
|---|---|---|---|---|---|
| S0 | — | — | — | — | 🔴 En attente arbitrage §103 |
| S1 | — | — | — | — | ⚪ Non démarré |
| S2 | — | — | — | — | ⚪ Non démarré |
| S3 | — | — | — | — | ⚪ Non démarré |
| S4 | — | — | — | — | ⚪ Non démarré |
| S5 | — | — | — | — | ⚪ Non démarré |

### 10.1 Prochaines actions immédiates

| # | Action | Responsable | Échéance | Statut |
|---|---|---|---|---|
| 1 | Activer les décisions D-1 à D-4 (CDC §103) — **bloquant** | PM | Fin S1 | 🔴 À arbitrer |
| 2 | Créer `backend/tests/` + premiers tests PostGIS (US-021) | QA-DevOps | Fin S2 | ✅ 47 tests créés — couverture à compléter |
| 3 | Compléter `docker-compose.yml` avec backend + frontend (US-001) | QA-DevOps | Fin S2 | ✅ Livré — validation Docker requise |
| 4 | Ajouter `.github/workflows/ci.yml` (US-002) | QA-DevOps | Fin S2 | 🟡 Créé — 1ᵉʳ exécution au 1ᵉʳ push |
| 5 | Compléter `/territories/{id}/children` et `/parents` (US-012) | Backend | Fin S3 | ✅ Livré — `code/{code}`, `children`, `parents`, `geometry` (RFC 7946), `map` (§41) + filtres `type/status/search/page/per_page/bbox` (ST_Intersects préparé) |

---

## 11. ARBITRAGES EN ATTENTE (PM)

**A-01 — Vitesse du MVP : back-office complet ou import semi-automatique ?**

| Scénario | Description | Délai | Coût | Impact qualité |
|---|---|---|---|---|
| **A (recommandé)** | Back-office complet (US-008 à US-011) | +3 semaines | Élevé | Traçabilité et contrôle fin, conforme §91 |
| B | Import via CLI uniquement, back-office en V2 | −3 semaines | Faible | Risque d'erreur humaine non contrôlée |
| C | Back-office minimal (upload + validation) sans comparaison de versions | 0 | Moyen | Audit dégradé, écart au §67 |

**Recommandation : A** — le CDC rend la publication humaine obligatoire et auditable. L'option B déplacerait le risque vers la qualité de la donnée, qui est précisément ce que la plateforme doit garantir.

**A-02 — Format de référence pour les géométries**

| Scénario | Description | Délai | Impact |
|---|---|---|---|
| **A (recommandé)** | GeoJSON EPSG:4326 (WGS84) | Nul | Aucun |
| B | TopoJSON (plus compact) | +2 semaines | Gain ~40 % payload, incompatible avec l'exigence GeoJSON RFC 7946 |

**Recommandation : A** — le CDC impose GeoJSON (P6, §40). TopoJSON reste une optimisation V2 à évaluer sur mesure réelle.

---

*Backlog généré par le Product & Project Manager IA à partir du CDC v2.0. Toute modification du CDC doit déclencher une revue de ce backlog.*
