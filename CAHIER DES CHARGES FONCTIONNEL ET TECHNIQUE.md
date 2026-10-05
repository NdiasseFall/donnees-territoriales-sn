# **CAHIER DES CHARGES FONCTIONNEL ET TECHNIQUE**

## **PLATEFORME NATIONALE DE DONNÉES TERRITORIALES**

### **Web GIS — API — Catalogue de données — Infrastructure géospatiale**

**Version :** 2.0  
**Pays :** Sénégal  
**Périmètre initial :** territoire national sénégalais  
**Source principale envisagée :** ANAT  
**Sources complémentaires :** ANSD et autres organismes autorisés  
**Statut :** Document de conception / préparation MVP  
**Date :** Octobre 2026

---

# **1\. RÉSUMÉ EXÉCUTIF**

Le projet consiste à concevoir et développer une plateforme numérique permettant de centraliser, structurer, documenter, visualiser et exploiter les données territoriales et géospatiales du Sénégal.

La plateforme reposera sur trois piliers :

1. **un référentiel géospatial centralisé ;**  
2. **une plateforme Web GIS interactive ;**  
3. **une infrastructure API permettant aux applications tierces d'accéder aux données.**

Le système permettra notamment de rechercher un territoire, afficher ses limites sur une carte, naviguer dans la hiérarchie administrative, consulter ses métadonnées, exploiter sa géométrie et, selon les droits de diffusion applicables, télécharger ou consommer ses données via API.

Le produit doit être conçu comme une **infrastructure de données géospatiales**, et non comme un simple site cartographique.

---

# **2\. VISION DU PRODUIT**

## **2.1 Vision**

Transformer les données territoriales du Sénégal en une infrastructure numérique :

**structurée → fiable → géolocalisée → documentée → accessible → exploitable.**

## **2.2 Proposition de valeur**

> « Rendre les données territoriales du Sénégal facilement accessibles, compréhensibles et exploitables par les citoyens, administrations, entreprises, chercheurs et développeurs. »

## **2.3 Positionnement**

La plateforme se positionne à l'intersection de :

* la géomatique ;  
* l'administration territoriale ;  
* la donnée publique ;  
* l'open data ;  
* les infrastructures numériques ;  
* les API ;  
* les systèmes d'information géographique.

---

# **3\. PROBLÉMATIQUE**

Les données territoriales peuvent être dispersées entre plusieurs formats, bases, producteurs et systèmes.

Exemples :

* Shapefile ;  
* GeoJSON ;  
* GeoPackage ;  
* CSV ;  
* Excel ;  
* données raster ;  
* bases SIG ;  
* cartes ;  
* documents administratifs ;  
* référentiels de localités.

La difficulté n'est donc pas uniquement de disposer d'une carte.

Il faut être capable de répondre à des questions telles que :

* Quel est le territoire ?  
* Quel est son code ?  
* De quel territoire dépend-il ?  
* Quelle est sa géométrie ?  
* Quelle est la source de cette géométrie ?  
* Quand a-t-elle été produite ?  
* Quelle est sa version ?  
* Est-elle validée ?  
* Peut-elle être diffusée ?  
* Quelle est sa précision ?  
* Quelle est sa licence ?  
* Comment une application peut-elle l'utiliser ?

Le projet doit répondre à l'ensemble de ces besoins.

---

# **4\. OBJECTIFS**

## **4.1 Objectif général**

Mettre en place une infrastructure numérique permettant de gérer, visualiser, rechercher et exposer les données territoriales et géospatiales du Sénégal.

## **4.2 Objectifs spécifiques**

Le système devra :

* centraliser les données ;  
* normaliser les référentiels ;  
* gérer les géométries ;  
* gérer la hiérarchie territoriale ;  
* assurer la traçabilité des sources ;  
* gérer les versions ;  
* contrôler la qualité ;  
* visualiser les limites sur une carte ;  
* permettre la recherche territoriale ;  
* proposer des fiches territoriales ;  
* exposer une API ;  
* documenter l'API ;  
* gérer les clés API ;  
* gérer les utilisateurs ;  
* gérer les droits ;  
* permettre l'import de nouveaux datasets ;  
* assurer la validation avant publication ;  
* conserver l'historique des changements.

---

# **5\. PÉRIMÈTRE DU PROJET**

## **5.1 Périmètre géographique**

Sénégal.

## **5.2 Périmètre territorial initial**

Le MVP doit pouvoir gérer :

Pays  
└── Région  
    └── Département  
        └── Arrondissement  
            └── Commune  
                └── Localité  
                    └── Quartier / Village / Hameau

La structure doit toutefois être générique afin de permettre l'ajout futur d'autres types de territoires.

---

# **6\. TYPES DE DONNÉES**

Le système distinguera plusieurs catégories.

## **6.1 Données référentielles**

Exemples :

* codes ;  
* noms officiels ;  
* types de territoires ;  
* relations hiérarchiques.

## **6.2 Données géométriques**

Exemples :

* Polygon ;  
* MultiPolygon ;  
* Point ;  
* MultiPoint ;  
* LineString ;  
* MultiLineString.

## **6.3 Données descriptives**

Exemples :

* nom ;  
* type ;  
* statut ;  
* description ;  
* superficie ;  
* centre géographique.

## **6.4 Données statistiques**

Exemples futurs :

* population ;  
* ménages ;  
* concessions ;  
* indicateurs socio-économiques.

## **6.5 Métadonnées**

Exemples :

* source ;  
* producteur ;  
* date ;  
* version ;  
* précision ;  
* CRS ;  
* licence ;  
* niveau de qualité.

---

# **7\. PRINCIPES DIRECTEURS**

Le système devra respecter les principes suivants :

### **P1 — Source**

Toute donnée doit avoir une origine identifiable.

### **P2 — Traçabilité**

Toute modification importante doit pouvoir être retracée.

### **P3 — Versionnement**

Une nouvelle version ne doit pas détruire silencieusement l'historique.

### **P4 — Qualité**

Les géométries et relations doivent être contrôlées avant publication.

### **P5 — Séparation**

Les données brutes ne doivent pas être directement exposées comme données officielles publiées.

### **P6 — Interopérabilité**

Les données doivent pouvoir être utilisées dans différents logiciels GIS.

### **P7 — API First**

Toute donnée publiée doit être pensée pour être consommable programmatiquement lorsque les droits le permettent.

### **P8 — Performance**

La carte doit utiliser des mécanismes adaptés aux volumes géographiques importants.

---

# **8\. ARCHITECTURE FONCTIONNELLE**

Le produit sera organisé en six domaines.

01 DATA  
02 GIS  
03 PLATFORM  
04 API  
05 ADMIN  
06 DEVELOPER

## **DATA**

Gestion des datasets, sources, versions et métadonnées.

## **GIS**

Carte, géométries, couches, recherche spatiale.

## **PLATFORM**

Interface publique.

## **API**

Services REST géographiques.

## **ADMIN**

Administration, import, validation, publication.

## **DEVELOPER**

Documentation, clés API, statistiques d'utilisation.

---

# **9\. ARCHITECTURE TECHNIQUE**

                        INTERNET  
                            │  
                            ▼  
                    CDN / WAF / HTTPS  
                            │  
              ┌─────────────┴─────────────┐  
              │                           │  
              ▼                           ▼  
         WEB APPLICATION                API  
           Next.js                    Laravel  
              │                           │  
              └─────────────┬─────────────┘  
                            │  
                            ▼  
                     SERVICE LAYER  
                            │  
              ┌─────────────┼─────────────┐  
              │             │             │  
              ▼             ▼             ▼  
           Redis         PostgreSQL     Object  
                         \+ PostGIS      Storage  
              │             │             │  
              └─────────────┼─────────────┘  
                            │  
                            ▼  
                     DATA PIPELINE  
                     GDAL / Python  
                            │  
                            ▼  
                 SOURCES GÉOSPATIALES

---

# **10\. STACK TECHNIQUE**

| Domaine | Technologie cible |
| ----- | ----- |
| Frontend | Next.js / React |
| Cartographie | MapLibre GL JS |
| Backend | Laravel |
| API | REST |
| Documentation | OpenAPI |
| Base | PostgreSQL |
| GIS | PostGIS |
| Cache | Redis |
| ETL | GDAL / Python |
| Object storage | S3 compatible |
| Tiles | Vector Tiles / PMTiles |
| Auth | Laravel Sanctum |
| Reverse Proxy | Nginx |
| Conteneurisation | Docker |
| CI/CD | GitHub Actions |
| Monitoring | Sentry \+ Grafana |
| Infrastructure | Cloud Linux |

---

# **11\. ARCHITECTURE POSTGIS**

## **11.1 Principe**

PostGIS constitue le cœur géospatial du système.

La base devra séparer :

RAW  
STAGING  
CORE  
PUBLISHED  
AUDIT

---

# **12\. SCHÉMA RAW**

Le schéma `raw` contient les données telles qu'importées.

Exemple :

raw.dataset\_imports  
raw.import\_features  
raw.import\_files

Objectif :

* conserver l'original ;  
* permettre une nouvelle transformation ;  
* faciliter l'audit ;  
* éviter la perte de données.

---

# **13\. SCHÉMA STAGING**

Le schéma `staging` contient les données transformées avant validation.

Exemple :

staging.territories  
staging.localities  
staging.boundaries

C'est dans cette zone que sont effectués :

* nettoyage ;  
* transformation CRS ;  
* normalisation ;  
* détection des doublons ;  
* contrôle des géométries.

---

# **14\. SCHÉMA CORE**

Le schéma `core` contient le référentiel officiel interne du système.

Tables principales :

core.territories  
core.territory\_types  
core.territory\_relationships  
core.geometries  
core.datasets  
core.dataset\_versions  
core.sources  
core.organizations

---

# **15\. SCHÉMA PUBLISHED**

Le schéma `published` contient uniquement les données autorisées à être exposées.

Exemple :

published.territories  
published.boundaries  
published.localities

La plateforme publique ne doit pas interroger directement les tables RAW.

---

# **16\. MCD — MODÈLE CONCEPTUEL DE DONNÉES**

## **Entités principales**

ORGANIZATION  
      │  
      ▼  
DATA\_SOURCE  
      │  
      ▼  
DATASET  
      │  
      ▼  
DATASET\_VERSION  
      │  
      ▼  
TERRITORY  
      │  
      ├──────── TERRITORY\_TYPE  
      │  
      ├──────── TERRITORY\_RELATIONSHIP  
      │  
      └──────── GEOMETRY

USER  
 │  
 └──── AUDIT\_LOG

---

# **17\. ENTITÉ ORGANIZATION**

Représente une organisation productrice ou gestionnaire de données.

### **Attributs**

id  
name  
acronym  
type  
email  
phone  
website  
status  
created\_at  
updated\_at

Exemples :

ANAT  
ANSD  
Collectivité territoriale  
Ministère  
Université  
Partenaire

---

# **18\. ENTITÉ DATA\_SOURCE**

Représente une source de données.

### **Attributs**

id  
organization\_id  
name  
description  
source\_type  
url  
license  
contact  
acquired\_at  
created\_at  
updated\_at

---

# **19\. ENTITÉ DATASET**

Représente un ensemble cohérent de données.

### **Attributs**

id  
source\_id  
name  
slug  
description  
dataset\_type  
geometry\_type  
access\_level  
status  
created\_at  
updated\_at

Exemples :

Administrative Boundaries  
Localities  
Communes  
Departments  
Regions

---

# **20\. ENTITÉ DATASET\_VERSION**

Permet de versionner les données.

id  
dataset\_id  
version  
release\_date  
effective\_date  
file\_path  
checksum  
record\_count  
status  
notes  
created\_at

Statuts :

draft  
processing  
review  
validated  
published  
deprecated  
rejected

---

# **21\. ENTITÉ TERRITORY**

Entité centrale.

id  
parent\_id  
territory\_type\_id  
code  
name  
official\_name  
slug  
level  
status  
country\_code  
centroid  
area  
geometry\_id  
source\_version\_id  
created\_at  
updated\_at

---

# **22\. ENTITÉ TERRITORY\_TYPE**

Permet de ne pas coder les niveaux en dur.

id  
code  
name  
level  
description  
is\_spatial  
created\_at  
updated\_at

Exemple :

| Code | Nom | Niveau |
| ----- | ----- | ----- |
| COUNTRY | Pays | 0 |
| REGION | Région | 1 |
| DEPARTMENT | Département | 2 |
| ARRONDISSEMENT | Arrondissement | 3 |
| COMMUNE | Commune | 4 |
| LOCALITY | Localité | 5 |
| QUARTER | Quartier | 6 |
| VILLAGE | Village | 6 |
| HAMLET | Hameau | 6 |

---

# **23\. RELATION TERRITORIALE**

Même si `parent_id` peut suffire pour le MVP, il est recommandé de prévoir une table de relations.

territory\_relationships

Attributs :

id  
parent\_territory\_id  
child\_territory\_id  
relationship\_type  
valid\_from  
valid\_to  
status  
source\_version\_id

Cela permet de gérer l'évolution administrative.

---

# **24\. GÉOMÉTRIES**

Table :

geometries

Attributs :

id  
territory\_id  
geometry  
geometry\_type  
srid  
area  
perimeter  
centroid  
simplified\_geometry  
quality\_status  
validation\_score  
source\_version\_id  
created\_at  
updated\_at

---

# **25\. CONTRAINTES GÉOMÉTRIQUES**

Avant publication :

ST\_IsValid  
ST\_IsEmpty  
ST\_GeometryType  
ST\_SRID

Contrôles :

✓ géométrie non vide  
✓ SRID valide  
✓ type attendu  
✓ géométrie valide  
✓ pas de doublon  
✓ relation territoriale valide

---

# **26\. DICTIONNAIRE DE DONNÉES — TERRITORY**

| Champ | Type | Obligatoire | Description |
| ----- | ----- | ----- | ----- |
| id | BIGINT | Oui | Identifiant interne |
| parent\_id | BIGINT | Non | Territoire parent |
| territory\_type\_id | BIGINT | Oui | Type |
| code | VARCHAR | Oui | Code territorial |
| name | VARCHAR | Oui | Nom courant |
| official\_name | VARCHAR | Non | Nom officiel |
| slug | VARCHAR | Oui | URL |
| level | INTEGER | Oui | Niveau hiérarchique |
| status | VARCHAR | Oui | Statut |
| country\_code | CHAR(2) | Oui | Code pays |
| area | NUMERIC | Non | Superficie |
| centroid | GEOMETRY | Non | Centre |
| geometry\_id | BIGINT | Oui | Géométrie |

---

# **27\. DICTIONNAIRE — GEOMETRY**

| Champ | Type | Description |
| ----- | ----- | ----- |
| id | BIGINT | Identifiant |
| territory\_id | BIGINT | Territoire |
| geometry | GEOMETRY | Géométrie |
| geometry\_type | VARCHAR | Polygon/MultiPolygon/etc. |
| srid | INTEGER | Référence spatiale |
| area | NUMERIC | Surface |
| perimeter | NUMERIC | Périmètre |
| centroid | GEOMETRY | Centroïde |
| quality\_status | VARCHAR | Qualité |
| validation\_score | NUMERIC | Score qualité |
| source\_version\_id | BIGINT | Version source |

---

# **28\. DICTIONNAIRE — DATASET**

| Champ | Type | Description |
| ----- | ----- | ----- |
| id | BIGINT | Identifiant |
| source\_id | BIGINT | Source |
| name | VARCHAR | Nom |
| slug | VARCHAR | Identifiant URL |
| description | TEXT | Description |
| dataset\_type | VARCHAR | Type |
| geometry\_type | VARCHAR | Géométrie |
| access\_level | VARCHAR | Niveau d'accès |
| status | VARCHAR | Statut |

---

# **29\. DICTIONNAIRE — SOURCE**

| Champ | Type | Description |
| ----- | ----- | ----- |
| id | BIGINT | Identifiant |
| organization\_id | BIGINT | Organisation |
| name | VARCHAR | Nom |
| source\_type | VARCHAR | Type |
| url | TEXT | Référence |
| license | TEXT | Licence |
| contact | TEXT | Contact |
| acquired\_at | DATE | Date acquisition |

---

# **30\. STATUT DES DONNÉES**

Les données doivent avoir un cycle de vie.

IMPORTED  
    ↓  
PROCESSING  
    ↓  
VALIDATION  
    ↓  
REVIEW  
    ↓  
APPROVED  
    ↓  
PUBLISHED

En cas de problème :

VALIDATION  
    ↓  
REJECTED

---

# **31\. STATUT DE GÉOMÉTRIE**

UNKNOWN  
VALID  
WARNING  
INVALID  
VERIFIED  
OFFICIAL

Important :

**VALID ≠ OFFICIAL**

Une géométrie peut être techniquement valide sans être juridiquement ou institutionnellement validée.

---

# **32\. STATUT DE TERRITOIRE**

ACTIVE  
INACTIVE  
PENDING  
DISPUTED  
HISTORICAL

Le statut `DISPUTED` ou équivalent devra être utilisé uniquement selon les règles institutionnelles définies par le propriétaire des données.

---

# **33\. INDEX POSTGIS**

Index principal :

CREATE INDEX idx\_territories\_geometry  
ON core.territories  
USING GIST (geometry);

Index code :

CREATE UNIQUE INDEX idx\_territories\_code  
ON core.territories(code);

Index recherche :

CREATE INDEX idx\_territories\_name  
ON core.territories  
USING GIN (to\_tsvector('french', name));

---

# **34\. API — PRINCIPES**

L'API sera versionnée :

/api/v1

Format par défaut :

application/json

Format géospatial :

application/geo+json

---

# **35\. API — TERRITOIRES**

### **Liste**

GET /api/v1/territories

Paramètres :

type  
level  
parent  
status  
search  
page  
per\_page

---

# **36\. API — DÉTAIL**

GET /api/v1/territories/{id}

Réponse :

{  
  "id": 125,  
  "code": "SN-...",  
  "name": "Mbour",  
  "type": "commune",  
  "level": 4,  
  "status": "published"  
}

---

# **37\. API — CODE**

GET /api/v1/territories/code/{code}

---

# **38\. API — ENFANTS**

GET /api/v1/territories/{id}/children

---

# **39\. API — PARENTS**

GET /api/v1/territories/{id}/parents

Réponse :

Sénégal  
→ Thiès  
→ Mbour  
→ Commune de Mbour

---

# **40\. API — GÉOMÉTRIE**

GET /api/v1/territories/{id}/geometry

Retour :

GeoJSON Feature

---

# **41\. API — CARTE**

GET /api/v1/territories/{id}/map

Retour possible :

geometry  
bbox  
centroid  
properties  
children

---

# **42\. API — RECHERCHE**

GET /api/v1/search?q=Mbour

Résultats :

{  
  "data": \[  
    {  
      "id": 1,  
      "name": "Mbour",  
      "type": "commune"  
    }  
  \]  
}

---

# **43\. API — FILTRAGE**

Exemple :

GET /api/v1/communes?region=thiès

Autres possibilités :

?department=  
?arrondissement=  
?status=  
?bbox=  
?parent=

---

# **44\. API — REVERSE GEOCODING**

Service futur :

GET /api/v1/reverse-geocode

Paramètres :

lat  
lng

Réponse :

{  
  "country": "Sénégal",  
  "region": "...",  
  "department": "...",  
  "arrondissement": "...",  
  "commune": "...",  
  "locality": "..."  
}

---

# **45\. API — REQUÊTES SPATIALES**

Version future :

POST /api/v1/spatial/intersects  
POST /api/v1/spatial/contains  
POST /api/v1/spatial/nearby

Exemples :

* territoires intersectant une géométrie ;  
* territoires contenant un point ;  
* territoires proches d'un point.

---

# **46\. API — DATASETS**

GET /api/v1/datasets  
GET /api/v1/datasets/{id}  
GET /api/v1/datasets/{id}/versions

---

# **47\. API — MÉTADONNÉES**

GET /api/v1/datasets/{id}/metadata

---

# **48\. API — VERSIONNEMENT**

GET /api/v1/datasets/{id}/versions  
GET /api/v1/datasets/{id}/versions/{version}

---

# **49\. API — TUILES**

Pour la cartographie à grande échelle :

GET /tiles/{z}/{x}/{y}.pbf

Les données détaillées ne doivent pas nécessairement être envoyées au navigateur sous forme de GeoJSON complet.

---

# **50\. FORMAT DE RÉPONSE API**

Format standard :

{  
  "data": {},  
  "meta": {  
    "version": "1.0",  
    "timestamp": "..."  
  }  
}

Liste :

{  
  "data": \[\],  
  "meta": {  
    "page": 1,  
    "per\_page": 20,  
    "total": 200  
  }  
}

Erreur :

{  
  "error": {  
    "code": "TERRITORY\_NOT\_FOUND",  
    "message": "Territory not found"  
  }  
}

---

# **51\. AUTHENTIFICATION API**

Trois niveaux :

### **Public**

Accès limité aux données publiques.

### **Registered Developer**

Accès avec API Key.

### **Enterprise / Institutional**

Accès contractuel avec quotas spécifiques.

---

# **52\. API KEY**

Table :

api\_keys

Champs :

id  
user\_id  
name  
key\_hash  
environment  
rate\_limit  
expires\_at  
last\_used\_at  
status  
created\_at

La clé en clair ne doit jamais être stockée.

---

# **53\. RATE LIMITING**

Exemple initial :

Public  
60 req/min

Developer  
600 req/min

Institution  
selon contrat

Les valeurs finales devront être définies après tests de charge et modèle commercial.

---

# **54\. RÔLES UTILISATEURS**

## **PUBLIC**

Consultation publique.

## **DEVELOPER**

API et documentation.

## **DATA\_EDITOR**

Import et préparation des données.

## **DATA\_REVIEWER**

Contrôle et validation.

## **GIS\_ADMIN**

Gestion des couches et paramètres GIS.

## **ADMIN**

Gestion générale.

## **SUPER\_ADMIN**

Administration complète.

---

# **55\. MATRICE DES PERMISSIONS**

| Fonction | Public | Developer | Editor | Reviewer | GIS Admin | Admin |
| ----- | ----- | ----- | ----- | ----- | ----- | ----- |
| Carte | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Recherche | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| API publique | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| API Key |  | ✓ |  |  |  | ✓ |
| Import |  |  | ✓ | ✓ | ✓ | ✓ |
| Modifier dataset |  |  | ✓ |  | ✓ | ✓ |
| Valider |  |  |  | ✓ | ✓ | ✓ |
| Publier |  |  |  | ✓ | ✓ | ✓ |
| Utilisateurs |  |  |  |  |  | ✓ |
| Paramètres |  |  |  |  | ✓ | ✓ |
| Audit |  |  |  | ✓ | ✓ | ✓ |

---

# **56\. ÉCRANS — SITE PUBLIC**

## **Écran 01 — Accueil**

Contenu :

Hero  
Recherche territoriale  
Carte du Sénégal  
Datasets  
API  
Documentation  
Statistiques

CTA :

Explorer la carte  
Consulter les données  
Utiliser l'API

---

# **57\. ÉCRAN 02 — EXPLORATEUR CARTOGRAPHIQUE**

Écran principal.

┌─────────────────────────────────────────────┐  
│ Recherche                                   │  
├─────────────┬───────────────────────────────┤  
│ Filtres     │                               │  
│             │                               │  
│ Région      │             CARTE             │  
│ Département │                               │  
│ Commune     │                               │  
│ Localité    │                               │  
│             │                               │  
└─────────────┴───────────────────────────────┘

Fonctions :

* zoom ;  
* sélection ;  
* recherche ;  
* couches ;  
* légende ;  
* localisation ;  
* partage.

---

# **58\. ÉCRAN 03 — RÉSULTATS DE RECHERCHE**

Recherche : Mbour

Communes  
→ Mbour

Départements  
→ Mbour

Localités  
→ Mbour

---

# **59\. ÉCRAN 04 — FICHE TERRITOIRE**

Contenu :

Nom  
Type  
Code  
Parent  
Superficie  
Centre  
Statut  
Source  
Version

Carte :

\[ POLYGONE DU TERRITOIRE \]

Actions :

Partager  
Copier le code  
Voir sur la carte  
API  
Télécharger

---

# **60\. ÉCRAN 05 — DATA CATALOG**

Filtres :

Type  
Source  
Format  
Date  
Statut  
Licence

Chaque dataset possède :

Nom  
Description  
Producteur  
Version  
Dernière mise à jour  
Format  
Licence

---

# **61\. ÉCRAN 06 — API DEVELOPER**

Contenu :

Introduction  
Authentification  
Endpoints  
Territories  
Datasets  
Search  
Spatial  
Examples  
Errors  
Rate limits

---

# **62\. ÉCRAN 07 — API KEYS**

My Applications

Application  
Status  
Requests  
Limit  
API Key

Actions :

Create application  
Generate key  
Revoke key  
Rotate key

---

# **63\. BACK-OFFICE — DASHBOARD**

Indicateurs :

Datasets  
Territories  
Geometries  
Users  
API requests  
Errors  
Pending validations

---

# **64\. BACK-OFFICE — DATASET**

Actions :

Créer  
Importer  
Modifier  
Versionner  
Valider  
Publier  
Archiver

---

# **65\. BACK-OFFICE — IMPORT**

Workflow visuel :

1 Upload  
2 Analyse  
3 Mapping  
4 Validation  
5 Publication

---

# **66\. BACK-OFFICE — CONTRÔLE QUALITÉ**

Exemple :

Dataset : Communes

Features : 557

Geometry valid : 553  
Geometry invalid : 4

Duplicate codes : 0  
Missing parent : 2  
Invalid CRS : 0

---

# **67\. BACK-OFFICE — VALIDATION**

Le reviewer voit :

Ancienne version  
Nouvelle version  
Différences  
Géométrie  
Attributs  
Source  
Commentaires

Actions :

Approve  
Reject  
Request correction

---

# **68\. BACK-OFFICE — AUDIT**

Chaque opération importante :

Utilisateur  
Action  
Objet  
Ancienne valeur  
Nouvelle valeur  
Date  
IP  
Résultat

---

# **69\. PIPELINE DE DONNÉES**

SOURCE  
 ↓  
DOWNLOAD / RECEIVE  
 ↓  
RAW STORAGE  
 ↓  
IMPORT  
 ↓  
NORMALIZATION  
 ↓  
GEOMETRY VALIDATION  
 ↓  
ATTRIBUTE VALIDATION  
 ↓  
RELATION VALIDATION  
 ↓  
QUALITY CONTROL  
 ↓  
HUMAN REVIEW  
 ↓  
PUBLISH  
 ↓  
API / WEB GIS

---

# **70\. CONTRÔLES DE QUALITÉ**

## **Contrôles attributaires**

* nom obligatoire ;  
* code obligatoire ;  
* code unique ;  
* type valide ;  
* parent existant.

## **Contrôles géométriques**

* géométrie non vide ;  
* géométrie valide ;  
* CRS valide ;  
* type géométrique attendu.

## **Contrôles hiérarchiques**

* commune → département ;  
* département → région ;  
* localité → commune ;  
* absence d'orphelin.

---

# **71\. VERSIONNEMENT DES DONNÉES**

Exemple :

ADMINISTRATIVE\_BOUNDARIES

v1.0  
2026-01

v1.1  
2026-04

v2.0  
2027-01

Chaque version possède :

* source ;  
* date ;  
* checksum ;  
* nombre d'objets ;  
* changelog ;  
* statut.

---

# **72\. MÉTADONNÉES MINIMALES**

Chaque dataset devra contenir :

Nom  
Résumé  
Description  
Producteur  
Source  
Date  
Version  
CRS  
Type géométrique  
Échelle  
Précision  
Licence  
Conditions d'utilisation  
Contact  
Statut

---

# **73\. LICENCES ET DROITS**

Le système devra distinguer :

PUBLIC  
REGISTERED  
RESTRICTED  
INTERNAL  
CONFIDENTIAL

Aucune donnée ANAT ou autre donnée institutionnelle ne devra être redistribuée publiquement avant vérification des droits de réutilisation et de diffusion applicables.

---

# **74\. SÉCURITÉ**

## **Infrastructure**

* HTTPS ;  
* firewall ;  
* WAF ;  
* sauvegardes ;  
* secrets management ;  
* accès SSH restreint.

## **Application**

* validation des entrées ;  
* protection XSS ;  
* protection CSRF ;  
* SQL injection ;  
* rate limiting ;  
* gestion de sessions ;  
* RBAC.

## **API**

* API Keys hashées ;  
* expiration ;  
* quotas ;  
* logs ;  
* rotation ;  
* révocation.

---

# **75\. SAUVEGARDES**

Politique recommandée :

Database backup  
→ quotidien

Object storage  
→ versionné

Backup long terme  
→ hebdomadaire / mensuel

Tester régulièrement la restauration.

---

# **76\. OBSERVABILITÉ**

Indicateurs :

API latency  
HTTP 4xx  
HTTP 5xx  
Database latency  
Tile latency  
Requests/minute  
CPU  
RAM  
Storage

Alertes :

API unavailable  
Database unavailable  
High error rate  
High latency  
Storage almost full  
Failed import

---

# **77\. NON-FONCTIONNEL**

## **Performance**

Objectifs initiaux :

Recherche : \< 300 ms cible  
API standard : \< 500 ms cible  
Page publique : \< 3 s cible

Ces valeurs devront être confirmées par tests.

## **Disponibilité**

Cible MVP :

≥ 99 % mensuel

Puis amélioration progressive.

---

# **78\. RESPONSIVE**

La plateforme devra fonctionner sur :

Mobile  
Tablet  
Desktop

Breakpoints recommandés :

375  
390  
430  
768  
1024  
1440  
1920

---

# **79\. ACCESSIBILITÉ**

Le système devra respecter les bonnes pratiques WCAG.

Notamment :

* contraste ;  
* navigation clavier ;  
* focus visible ;  
* labels ;  
* textes alternatifs ;  
* taille de texte ;  
* statut non basé uniquement sur la couleur.

---

# **80\. ARCHITECTURE DES MODULES BACKEND**

app/  
├── Domain/  
│   ├── Territory/  
│   ├── Dataset/  
│   ├── Geometry/  
│   ├── Source/  
│   ├── User/  
│   └── Api/  
│  
├── Http/  
│   ├── Controllers/  
│   ├── Requests/  
│   └── Resources/  
│  
├── Services/  
├── Jobs/  
├── Policies/  
└── Console/

---

# **81\. ARCHITECTURE FRONTEND**

src/  
├── app/  
├── components/  
├── features/  
│   ├── map/  
│   ├── search/  
│   ├── territories/  
│   ├── datasets/  
│   └── developer/  
├── lib/  
├── services/  
├── hooks/  
└── types/

---

# **82\. ARCHITECTURE GIS FRONTEND**

Composants :

Map  
MapControls  
LayerSwitcher  
TerritoryLayer  
BoundaryLayer  
SelectionLayer  
SearchResultLayer  
Popup  
Legend  
Scale  
Coordinates

---

# **83\. DESIGN DU SYSTÈME CARTOGRAPHIQUE**

La carte doit distinguer :

Pays  
Région  
Département  
Arrondissement  
Commune  
Localité

La densité visuelle doit évoluer selon le zoom.

À petite échelle :

Régions

À moyenne échelle :

Départements  
Communes

À grande échelle :

Localités  
Quartiers  
Villages

---

# **84\. VECTOR TILES**

Architecture :

PostGIS  
 ↓  
Tile generation  
 ↓  
PBF  
 ↓  
CDN  
 ↓  
MapLibre

Objectif :

* réduire le volume transféré ;  
* accélérer la navigation ;  
* supporter beaucoup de géométries.

---

# **85\. RECHERCHE**

Le moteur de recherche doit gérer :

Mbour  
mbour  
MBOUR  
M'bour  
variantes de noms  
codes

Il devra pouvoir rechercher :

nom  
code  
type  
parent

---

# **86\. URLS TERRITORIALES**

Chaque territoire doit posséder une URL stable.

Exemple conceptuel :

/territoire/sn/region/thies  
/territoire/sn/departement/mbour  
/territoire/sn/commune/mbour

---

# **87\. PARTAGE**

La fiche d'un territoire pourra être partagée :

URL  
QR Code  
API URL  
GeoJSON URL

---

# **88\. ANALYTICS**

Le système pourra mesurer :

territoires consultés  
recherches  
datasets consultés  
API calls  
téléchargements

Les statistiques devront être conçues en respectant les règles applicables à la protection des données personnelles.

---

# **89\. BACKLOG MVP**

## **EPIC 01 — Infrastructure**

### **US-001**

**En tant qu'administrateur**, je veux disposer de l'environnement technique afin de déployer la plateforme.

Critères :

* Docker ;  
* PostgreSQL ;  
* PostGIS ;  
* Redis ;  
* Laravel ;  
* Next.js ;  
* CI/CD.

---

## **EPIC 02 — Data Model**

### **US-002**

Créer le modèle territorial.

Critères :

* territoire ;  
* type ;  
* parent ;  
* source ;  
* version ;  
* géométrie.

---

## **EPIC 03 — Import**

### **US-003**

Importer un dataset géographique.

Critères :

* upload ;  
* détection ;  
* mapping ;  
* conversion ;  
* validation.

---

## **EPIC 04 — Quality**

### **US-004**

Contrôler automatiquement les géométries.

Critères :

* validité ;  
* CRS ;  
* géométrie vide ;  
* doublons.

---

## **EPIC 05 — Publication**

### **US-005**

Publier une version validée.

Critères :

* validation humaine ;  
* version ;  
* statut ;  
* publication.

---

## **EPIC 06 — Carte**

### **US-006**

Afficher les territoires sur une carte.

Critères :

* carte ;  
* limites ;  
* zoom ;  
* sélection ;  
* popup.

---

## **EPIC 07 — Recherche**

### **US-007**

Rechercher un territoire.

Critères :

* nom ;  
* code ;  
* type ;  
* résultat ;  
* zoom automatique.

---

## **EPIC 08 — Fiche**

### **US-008**

Consulter la fiche d'un territoire.

Critères :

* informations ;  
* hiérarchie ;  
* géométrie ;  
* source ;  
* version.

---

## **EPIC 09 — API**

### **US-009**

Consulter les territoires via API.

Critères :

GET /territories  
GET /territories/{id}  
GET /territories/{id}/geometry

---

## **EPIC 10 — Developer**

### **US-010**

Créer une clé API.

Critères :

* authentification ;  
* création ;  
* révocation ;  
* quota ;  
* logs.

---

## **EPIC 11 — Documentation**

### **US-011**

Consulter la documentation API.

Critères :

* OpenAPI ;  
* exemples ;  
* paramètres ;  
* réponses ;  
* erreurs.

---

## **EPIC 12 — Administration**

### **US-012**

Gérer les datasets.

Critères :

* création ;  
* modification ;  
* version ;  
* publication ;  
* archivage.

---

# **90\. BACKLOG PRIORISÉ**

| ID | Fonction | Priorité |
| ----- | ----- | ----- |
| INF-001 | Infrastructure | P0 |
| DATA-001 | Modèle PostGIS | P0 |
| DATA-002 | Import | P0 |
| DATA-003 | Validation | P0 |
| GIS-001 | Carte | P0 |
| GIS-002 | Limites | P0 |
| GIS-003 | Recherche | P0 |
| TERR-001 | Fiche territoire | P0 |
| API-001 | API territoires | P0 |
| API-002 | GeoJSON | P0 |
| API-003 | Documentation | P0 |
| ADMIN-001 | Auth | P0 |
| ADMIN-002 | Dataset management | P0 |
| ADMIN-003 | Publication | P0 |
| DEV-001 | API Keys | P1 |
| API-004 | Spatial queries | P1 |
| GIS-004 | Vector tiles | P1 |
| DATA-004 | Versioning avancé | P1 |
| ANALYTICS-001 | Dashboard usage | P2 |

---

# **91\. DÉFINITION DU MVP**

Le MVP est considéré terminé lorsque :

### **Données**

* les données territoriales sélectionnées sont intégrées ;  
* les hiérarchies fonctionnent ;  
* les géométries sont validées ;  
* les sources sont documentées.

### **Carte**

* le Sénégal est affiché ;  
* les limites fonctionnent ;  
* la recherche fonctionne ;  
* la sélection fonctionne.

### **API**

* endpoints principaux disponibles ;  
* GeoJSON fonctionnel ;  
* documentation disponible.

### **Administration**

* import ;  
* validation ;  
* publication ;  
* versionnement minimal.

---

# **92\. PLANNING**

## **Phase 0 — Cadrage**

**Semaine 1–2**

Livrables :

* cahier des charges ;  
* inventaire des données ;  
* architecture ;  
* modèle de données ;  
* UX ;  
* stratégie juridique/licence.

---

## **Phase 1 — Data Engineering**

**Semaine 3–5**

Livrables :

* PostgreSQL ;  
* PostGIS ;  
* pipeline ;  
* import ;  
* normalisation ;  
* contrôles qualité.

---

## **Phase 2 — Backend**

**Semaine 4–7**

Livrables :

* Laravel ;  
* modèles ;  
* services ;  
* API ;  
* authentification ;  
* documentation.

---

## **Phase 3 — Web GIS**

**Semaine 6–9**

Livrables :

* interface ;  
* carte ;  
* couches ;  
* recherche ;  
* fiches ;  
* navigation territoriale.

---

## **Phase 4 — Administration**

**Semaine 8–10**

Livrables :

* dashboard ;  
* import ;  
* validation ;  
* datasets ;  
* versions ;  
* utilisateurs.

---

## **Phase 5 — API Developer**

**Semaine 10–11**

Livrables :

* API keys ;  
* quotas ;  
* documentation ;  
* exemples ;  
* analytics basiques.

---

## **Phase 6 — QA / Production**

**Semaine 12–14**

Livrables :

* tests ;  
* sécurité ;  
* performance ;  
* correction ;  
* déploiement ;  
* monitoring.

---

# **93\. PLANNING GLOBAL**

                S1 S2 S3 S4 S5 S6 S7 S8 S9 S10 S11 S12 S13 S14

Cadrage          ██ ██

Data Engineering       ██ ██ ██

Backend                   ██ ██ ██ ██

Web GIS                         ██ ██ ██ ██

Admin                              ██ ██ ██

Developer                              ██ ██

QA / Production                           ██ ██ ██ ██

---

# **94\. ÉQUIPE RECOMMANDÉE**

## **Équipe MVP**

### **1 — Product / Project Manager**

Responsabilités :

* backlog ;  
* coordination ;  
* validation ;  
* relations partenaires.

### **2 — GIS / Data Engineer**

Responsabilités :

* PostGIS ;  
* GDAL ;  
* géométries ;  
* ETL ;  
* qualité.

### **3 — Backend Engineer**

Responsabilités :

* Laravel ;  
* API ;  
* sécurité ;  
* base de données.

### **4 — Frontend / GIS Developer**

Responsabilités :

* Next.js ;  
* MapLibre ;  
* interface ;  
* performance cartographique.

### **Support**

* UX/UI ;  
* DevOps ;  
* expert géomatique ;  
* expert juridique/licences.

---

# **95\. RISQUES**

## **Risque 1 — Données non disponibles**

Solution :

* définir précisément les datasets nécessaires ;  
* obtenir les autorisations ;  
* construire un pipeline adaptable.

## **Risque 2 — Limites contradictoires**

Solution :

* version ;  
* source ;  
* statut ;  
* validation ;  
* historique.

## **Risque 3 — Géométries lourdes**

Solution :

* simplification ;  
* vector tiles ;  
* CDN ;  
* niveaux de zoom.

## **Risque 4 — Données difficiles à normaliser**

Solution :

* staging ;  
* mapping ;  
* règles de qualité ;  
* validation humaine.

## **Risque 5 — Coût infrastructure**

Solution :

* architecture progressive ;  
* stockage objet ;  
* cache ;  
* vector tiles ;  
* monitoring.

---

# **96\. ÉVOLUTION APRÈS MVP**

## **V2**

* reverse geocoding ;  
* requêtes spatiales ;  
* statistiques ;  
* téléchargements avancés ;  
* vector tiles avancées ;  
* historique cartographique.

## **V3**

* données d'infrastructures ;  
* routes ;  
* bâtiments ;  
* équipements ;  
* foncier ;  
* occupation du sol.

## **V4**

* services B2B ;  
* API premium ;  
* SLA ;  
* portail institutionnel ;  
* services géospatiaux avancés.

---

# **97\. ÉVOLUTION VERS UNE INFRASTRUCTURE NATIONALE**

À terme :

                   DATA PLATFORM  
                         │  
       ┌─────────────────┼─────────────────┐  
       │                 │                 │  
       ▼                 ▼                 ▼  
 TERRITOIRES         LOCALITÉS          RÉSEAUX  
       │                 │                 │  
       ▼                 ▼                 ▼  
  COMMUNES          ADRESSES          ROUTES  
       │                 │                 │  
       └─────────────────┼─────────────────┘  
                         ▼  
                    GEO SERVICES  
                         │  
            ┌────────────┼────────────┐  
            ▼            ▼            ▼  
           API          GIS       DOWNLOAD  
            │  
            ▼  
       APPLICATIONS

---

# **98\. RELATION AVEC SEN ADDRESS**

Cette plateforme peut constituer le **socle territorial** d'un projet plus large comme Sen Address.

Architecture possible :

                   TERRITORIAL DATA PLATFORM  
                              │  
                              ▼  
                     Référentiel territorial  
                              │  
             ┌────────────────┼────────────────┐  
             ▼                ▼                ▼  
          Localités        Communes         Territoires  
             │  
             ▼  
                    SEN ADDRESS  
             │  
             ├── Habitations  
             ├── Adresses  
             ├── Coordonnées  
             ├── Numérotation  
             └── Validation

Cela permettrait de ne pas mélanger dans le même système :

**les données territoriales de référence**

et

**les données d'adressage opérationnel.**

---

# **99\. INDICATEURS DE SUCCÈS**

## **Data**

Nombre de datasets  
Nombre de territoires  
Nombre de géométries validées  
Taux de complétude  
Taux d'erreurs

## **Plateforme**

Utilisateurs  
Recherches  
Territoires consultés  
Temps de réponse

## **API**

API requests  
Développeurs  
Applications  
Taux d'erreur  
Latence

---

# **100\. CRITÈRES DE RÉUSSITE DU PROJET**

Le projet est considéré comme réussi si un utilisateur peut :

1\. ouvrir la plateforme  
2\. voir le Sénégal  
3\. rechercher une commune  
4\. cliquer sur le résultat  
5\. voir sa limite  
6\. voir son territoire parent  
7\. consulter ses informations  
8\. récupérer sa géométrie  
9\. appeler l'API  
10\. intégrer la donnée dans une autre application

Et si un administrateur peut :

1\. importer un dataset  
2\. le contrôler  
3\. corriger les erreurs  
4\. le faire valider  
5\. créer une version  
6\. publier  
7\. exposer les données via API  
8\. consulter les logs

---

# **101\. CONCLUSION**

Le projet doit être conçu comme une **infrastructure géospatiale**, avec quatre couches fondamentales :

┌───────────────────────────────┐  
│           SERVICES            │  
│          API / GIS            │  
├───────────────────────────────┤  
│          PLATFORM             │  
│       Web / Developer         │  
├───────────────────────────────┤  
│        GEO DATABASE           │  
│       PostgreSQL/PostGIS      │  
├───────────────────────────────┤  
│          DATA CORE            │  
│ Source / Version / Quality    │  
└───────────────────────────────┘

Le principe architectural fondamental est :

> **Source → Data → Quality → PostGIS → Publication → Web GIS → API → Applications**

Le projet ne doit donc pas être pensé comme une simple carte du Sénégal.

Il doit être pensé comme une **infrastructure numérique permettant de transformer les données territoriales en services géospatiaux réutilisables.**

---

# **102\. LIVRABLES DU PROJET**

À la fin du MVP, les livrables devront comprendre :

### **Documentation**

* cahier des charges ;  
* architecture technique ;  
* modèle de données ;  
* dictionnaire de données ;  
* documentation API ;  
* documentation d'administration.

### **Data**

* base PostGIS ;  
* datasets normalisés ;  
* métadonnées ;  
* versions ;  
* règles qualité.

### **Logiciel**

* frontend Web GIS ;  
* backend Laravel ;  
* API ;  
* back-office ;  
* système d'authentification.

### **Infrastructure**

* Docker ;  
* CI/CD ;  
* monitoring ;  
* backups ;  
* environnement staging ;  
* environnement production.

### **API**

* OpenAPI ;  
* endpoints ;  
* GeoJSON ;  
* API Keys ;  
* quotas.

---

# **103\. DÉCISION D'ARCHITECTURE À PRENDRE**

Avant de commencer le développement, quatre éléments doivent être officiellement validés :

**1\. Les datasets ANAT réellement disponibles et réutilisables.**

**2\. Les règles de diffusion de chaque dataset.**

**3\. Le référentiel territorial et les codes à retenir comme identifiants canoniques.**

**4\. La relation entre données ANAT, données ANSD et éventuelles autres sources.**

Ces quatre décisions conditionnent directement le MLD final et le pipeline d'import.

---

# **104\. PROCHAINE VERSION DU DOSSIER**

La prochaine étape de conception doit produire les artefacts techniques suivants :

01 — MCD détaillé  
02 — MLD PostgreSQL/PostGIS  
03 — SQL CREATE TABLE  
04 — Diagramme d'architecture  
05 — Diagramme de séquence API  
06 — Diagramme de flux ETL  
07 — Dictionnaire de données complet  
08 — Catalogue des endpoints  
09 — OpenAPI 3.1  
10 — Wireframes des écrans  
11 — Backlog détaillé  
12 — Estimation en jours/homme  
13 — Budget MVP  
14 — Plan de déploiement  
15 — Plan de tests  
16 — Plan de sécurité

**FIN DU CAHIER DES CHARGES V2**

