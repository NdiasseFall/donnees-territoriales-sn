# Phase 4 — État d'arrêt, synthèse et plan (US-015 / Web GIS S4)

> Artefact de synthèse de la Phase 4 (saisie continue de la session). Conçu pour servir de point de repère avant le travail d'implémentation.

## 1. Fichiers lus (détaillés)

### Backend (API Laravel 10 — DDD)
- `docs/BACKLOG-MVP.md` — US-015 (Explorateur), US-016 (Recherche), US-017 (Fiche), US-018 (Accueil & catalogue), US-023 (Non-fonctionnel) ; epic 06/07 ; matrice d'affectation ; arbitrages.
- `docs/PLAN-REALISATION.md` — PHASE 4 = Web GIS (S4), US-015→US-023, prérequis seed complet, DoD (Lighthouse ≥ seuils, tests verts, critères §100.2→100.7).
- `app/Http/Controllers/Api/V1/SearchController.php` — moteur de recherche territoire (full-text + trigrammes, §85).
- `app/Http/Controllers/Api/V1/SpatialController.php` — `reverseGeocode`, `bbox`, `intersect`.
- `app/Http/Controllers/Api/V1/DatasetController.php` — catalogue + détail (utilisé par l'explorateur US-018 et par l'API consommée côté frontend).
- `app/Domain/Dataset/Repositories/DatasetRepositoryInterface.php` — `findAll` étendu (search/order/direction), `countByCriteria`, `findLatestPublishedVersion`.
- `app/Infrastructure/Persistence/Eloquent/Repositories/EloquentDatasetRepository.php` — implémentation PostGIS (ILIKE + `to_tsvector('french', immutable_unaccent(...))` + trigrammes, `similarity()` pour le tri).
- `app/Application/Dataset/DTOs/DatasetDTO.php` — DTO catalogue (avec `fromEntity` → version publiée la plus récente).
- `app/Http/Resources/Api/V1/DatasetResource.php` — champs exposés (name, description, source, category, access_level, license, tags, metadata, is_official, latest_version…).
- `app/Http/Requests/Api/V1/DatasetCatalogRequest.php` — validation (q min 2, order/direction, pagination).
- `app/Providers/RepositoryServiceProvider.php` — binding `DatasetRepositoryInterface → EloquentDatasetRepository`.
- `app/Http/Requests/Api/V1/GetTerritoriesRequest.php` — pattern de validation (reference).

### Frontend (Next.js 16 — TypeScript strict)
- `frontent/package.json` — stack : Next 16, React 19, MapLibre GL 6.12, Zustand 5, Tailwind 4, Jest 30 + ts-jest.
- `frontent/src/lib/api.ts` — `apiClient` (`/api/v1`, axios), `territoryApi` (list/getByCode/hierarchy/search/reverseGeocode/bbox) — pas encore de `datasets` (US-018).
- `frontent/src/lib/store.ts` — Zustand : `selectedTerritory`, `hoveredTerritory`, `searchQuery`, `searchResults`, `activeLayers`, `layerConfigs`, `bbox`, `isPanelOpen`, `isLoading`, `mapViewport`. Couches par défaut : Régions + Départements visibles, Communes/Arrondissements cachées.
- `frontent/src/types/territoire.ts` — `TerritoryFeature`, `TerritoryFeatureProperties`, `MapLayerId`, `MapLayerConfig`.
- `frontent/src/components/map/MapComponent.tsx` — analyse complète (lues en plusieurs passes, via shell + search_codebase) :
  - `initializeSourcesAndLayers` (L97-118) — sources GeoJSON vides + styles.
  - `updateSourceData` (L120-150) — `source.setData()` + point de contrôle sur l'erreur.
  - `loadInitialData` (L131-149) — `Promise.all([level 1 (limit 20), level 2 (limit 50)])` → `updateSourceData`.
  - `handleClick` (L151-163) — nettoyage du surlignage + `setSelectedTerritory`.
  - `handleMouseMove` (L165-179) — `queryRenderedFeatures` + curseur `pointer`.
  - `updateVisibleLayers` (L181-196) — seuils zoom : Régions [0,8), Départements [8,11), Arrondissements [11,13), Communes [13,22).
  - `useEffect('load')` (L198-245) — initialise la carte, appelle `loadInitialData`.
  - `useEffect(activeLayers)` (L247-256) — visibilité des calques.
- `frontent/src/components/ui/SidePanel.tsx` — `DetailsTab`, `HierarchyTab` (stub), `ActionsTab` (stub), affichage nom/code/niveau/status/superficie/population/chef-lieu/qualité/géométrie.
- `frontent/src/components/ui/Header.tsx` — recherche + barre d'outils (nécessite la vérification du formattage).
- `frontent/src/components/map/__tests__/**` — à vérifier (pas de résultat définitif pour l'instant).

## 2. Contexte et décisions

- **Commit de référence** : `fc56cfd feat: US-014 + US-022` — livraison backend complète sous PostGIS réel (1822 assertions, OpenAPI valide, contrôles sécurité OK).
- **État de l'architecture** : frontend structuré et testé (Zustand, MapLibre, Tailwind, Jest), mais l'implémentation de l'US-015 reste en phase d'évaluation/préparation.
- **Décision clé** : la couche `Dataset` (Domain / Application / Infrastructure) est déjà écrite dans le backend (checkpoint `2cd2897`/`a1a78b7`) et la route `GET /api/v1/datasets` est prête (catalogue + `{slug}`). La Phase 4 s'articule autour du catalogue dataset comme socle de l'US-018 (Accueil & catalogue) et de l'US-015 (Explorateur).
- **Arbitrage** : la recherche côté dataset doit reprendre le schéma terrain déjà certifié (US-012/§85) : `REPLACE(name, '''', '') ILIKE ?` + `to_tsvector('french', immutable_unaccent(...)) @@ plainto_tsquery(...)` + `pg_trgm`, sans concaténation SQL.

## 3. Évaluation de l'US-015 (critères BDD et niveau de maturité)

| Critère BDD (§100.2→100.6) | Niveau | Observations |
|---|---|---|
| Couches commutables Régions/Départements/Communes/Arrondissements | ✅ Partiel | Calques + `toggleLayer` existants ; la donnée est chargée pour Régions (+ Départements) ; les communes ne sont **pas** chargées à grande échelle. |
| Densité par niveau de zoom (Région→Département→Commune) | 🔴 Manquant | Seulement 2 niveaux chargés (1 et 2). Aucune simplification par zoom, aucun rechargement des communes quand on avance. |
| Clic → surlignage + panneau latéral à jour | ✅ Present | `handleClick` nettoie + `setSelectedTerritory` (L151-163) ; `SidePanel` affiche les attributs. |
| Survol → infobulle nom+type + curseur pointeur | 🔶 Partiel | `handleMouseMove` + `setHoveredTerritory` (L165-179) ; le curseur est géré ; l'**infobulle DOM** n'existe pas (le SidePanel affiche le survol quand aucune sélection). |
| Tolérance coupure réseau | 🔴 Amélioration | Try/catch + `setLoading(false)` existants ; **aucune journalisation** (`console.error`) ni réessai, et les couches déjà chargées doivent être réintroduites explicitement (responsabilité actuelle = `updateSourceData` qui remplace les données). |
| Toulouse 0 `any` (TS strict) | ✅ Respecté` | codebase respecte le strict. |

## 4. Points ouverts / décisions à prendre

1. **Datasets disponibles** (D-1 CDC) : le seed `core.datasets` est-il alimenté en CI (BLK-07) ? S'il manque des données, les tests d'intégration doivent être robustes à l'absence de données.
2. **Licences/diffusion** (D-2) : les datasets `RESTRICTED`/`CONFIDENTIAL` sont-ils visibles en lecture publique (US-018) ? → API doit filtrer.
3. **Ontologie dataset** : champ `source` → `source_name` (EloquentRepository) — cohérent avec le `DatasetResource` ?
4. **Infobulle tooltip** : création d'un composant `Tooltip` léger (SVG/HTML overlay) plutôt que de dépendre du SidePanel.
5. **Niveau de zoom par calque** : simplifier la géométrie des communes quand zoom < 13 pour la densité (ST_Transform + ST_Simplify — cf. CDC §83).

## 5. Plan de la Phase 4

### Backend (terminer + valider)
- [x] `DatasetCatalogRequest` — validation des critères.
- [x] `DatasetRepositoryInterface` — `findAll` (search/order/direction), `countByCriteria`, `findLatestPublishedVersion`.
- [x] `EloquentDatasetRepository` — implémentation PostGIS.
- [x] `DatasetController@index` — pagination (`page`/`per_page` + `limit`/`offset`) + meta + `q`/`category`/`access_level`/`order`/`direction`.
- [x] `DatasetController@show` — inchangé (gère le 404 `DATASET_NOT_FOUND`).
- [x] `tests/Feature/DatasetCatalogApiTest.php` — enveloppe paginée, filtres, ordre, 404, recherche insensible.
- [x] `storage/api/openapi.yaml` — ajout des paramètres dataset + extension du schéma de réponse.
- [ ] Correction de l'import inutilisé `SearchTerritoryRequest` dans `DatasetController`.
- [ ] Lint + `php -l` + (si shell disponible) `vendor/bin/phpunit --filter DatasetCatalog`.

### Frontend (préparer l'implémentation détaillée de l'US-015)
- [ ] Finaliser l'évaluation de l'US-015 (tableau §3 ci-dessus).
- [ ] Écrire le plan d'implémentation de l'US-015 (couches, densité, sélection, survol, tolérance).
- [ ] Implémenter : `loadInitialData` à niveau de zoom (communes), densité/simplification, journalisation erreur + tolérance, infobulle tooltip.
- [ ] Composants `Tooltip`, `SearchBar` (US-016), `TerritoryFiche` (US-017), accueil + catalogue (US-018).
- [ ] Tests Jest (store + `MapComponent` simulé).

## 6. Validation et briques manquantes

- **Backend** : `php -l` + `vendor/bin/phpunit` nécessitent une instance PostGIS réelle (Docker Compose présent). La suite existante (1822 assertions) est exécutée en CI.
- **Frontend** : `npm test` (Jest + ts-jest) ; maquette MapLibre glissée en simulation (pas de serveur Spot pour le moment).
- **CI** : `.github/workflows/ci.yml` (Redocly CLI + PHPUnit en PostGIS); à vérifier la prise en compte du nouveau test `DatasetCatalogApiTest`.
