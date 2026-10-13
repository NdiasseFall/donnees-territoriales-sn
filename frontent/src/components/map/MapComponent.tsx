'use client';

import { useCallback, useEffect, useRef, useState } from 'react';
import {
  AttributionControl,
  Map,
  NavigationControl,
  ScaleControl,
} from 'maplibre-gl';
import type {
  GeoJSONSource,
  GeoJSONSourceSpecification,
  LayerSpecification,
  MapMouseEvent,
} from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { useMapStore } from '@/lib/store';
import { territoryApi } from '@/lib/api';
import type { MapLayerId, TerritoryFeature } from '@/types/territoire';
import { MapTooltip } from './MapTooltip';

const QUERYABLE_LAYERS = [
  'regions-layer',
  'departments-layer',
  'arrondissements-layer',
  'communes-layer',
];

const LAYER_STYLES: Record<MapLayerId, LayerSpecification> = {
  regions: {
    id: 'regions-layer',
    type: 'fill',
    source: 'regions-source',
    paint: {
      'fill-color': '#006837',
      'fill-opacity': 0.3,
      'fill-outline-color': '#004d26',
    },
    layout: { visibility: 'visible' },
  },
  departments: {
    id: 'departments-layer',
    type: 'fill',
    source: 'departments-source',
    paint: {
      'fill-color': '#1a9850',
      'fill-opacity': 0.25,
      'fill-outline-color': '#006837',
    },
    layout: { visibility: 'visible' },
  },
  arrondissements: {
    id: 'arrondissements-layer',
    type: 'fill',
    source: 'arrondissements-source',
    paint: {
      'fill-color': '#66bd63',
      'fill-opacity': 0.2,
      'fill-outline-color': '#1a9850',
    },
    layout: { visibility: 'none' },
  },
  communes: {
    id: 'communes-layer',
    type: 'fill',
    source: 'communes-source',
    paint: {
      'fill-color': '#a6d96a',
      'fill-opacity': 0.15,
      'fill-outline-color': '#66bd63',
    },
    layout: { visibility: 'none' },
  },
};

const ZOOM_THRESHOLDS: Record<MapLayerId, [number, number]> = {
  regions: [0, 8],
  departments: [8, 11],
  arrondissements: [11, 13],
  communes: [13, 22],
};

/**
 * US-015 — fond de carte OpenStreetMap.
 *
 * Tuiles raster 256 px servies par le service public d'OSM (z max 19).
 * La source et sa couche sont déclarées dans le style initial de la carte
 * (et non via `addSource`) : elles sont donc dans la pile AVANT les couches
 * territoriales ajoutées ensuite, ce qui garantit que les polygones
 * territoriaux restent superposés au fond.
 *
 * La tile usage policy d'OSM impose l'affichage de l'attribution
 * (`AttributionControl`) et un usage raisonné — en production, prévoir un
 * tile server interne ou un fournisseur (MapTiler, NextGIS…).
 * Cf. https://operations.osmfoundation.org/policies/tiles/
 */
const OSM_BASEMAP_ID = 'osm-basemap';

/**
 * US-015 — extrait les features d'une réponse GeoJSON de l'API.
 *
 * Les endpoints territoriaux renvoient un `FeatureCollection` RFC 7946 brut
 * (`{type, features}`), mais certaines routes enveloppent le résultat dans
 * `{success, data}`. Ce helper absorbe les deux formes pour qu'une évolution
 * du contrat d'un côté ne vide pas silencieusement la carte.
 */
function extractFeatures(payload: unknown): GeoJSON.Feature[] {
  if (payload === null || typeof payload !== 'object') return [];
  const record = payload as { features?: unknown; data?: unknown };
  if (Array.isArray(record.features)) return record.features as GeoJSON.Feature[];
  if (Array.isArray(record.data)) return record.data as GeoJSON.Feature[];
  return [];
}

export function MapComponent() {
  const mapContainer = useRef<HTMLDivElement>(null);
  const map = useRef<Map | null>(null);

  // US-015 — niveaux de territoires déjà chargés (garde pour l'implémentation
  // paresseuse et la tolérance réseau).
  const loadedLevels = useRef<Set<number>>(new Set());

  // US-015 — position de l'infobulle pour le tooltip (coordonnées du canvas).
  const [tooltipPosition, setTooltipPosition] = useState<{ x: number; y: number } | null>(null);
  const [mapLoaded, setMapLoaded] = useState(false);

  const {
    activeLayers,
    layerConfigs,
    setLoading,
    setMapViewport,
    setSelectedTerritory,
    setHoveredTerritory,
    hoveredTerritory,
  } = useMapStore();



  const initializeSourcesAndLayers = useCallback(() => {
    if (!map.current) return;

    const sources: Record<string, GeoJSONSourceSpecification> = {
      'regions-source': { type: 'geojson', data: { type: 'FeatureCollection', features: [] } },
      'departments-source': { type: 'geojson', data: { type: 'FeatureCollection', features: [] } },
      'arrondissements-source': { type: 'geojson', data: { type: 'FeatureCollection', features: [] } },
      'communes-source': { type: 'geojson', data: { type: 'FeatureCollection', features: [] } },
    };

    Object.entries(sources).forEach(([id, source]) => {
      if (!map.current!.getSource(id)) {
        map.current!.addSource(id, source);
      }
    });

    Object.values(LAYER_STYLES).forEach((layer) => {
      if (!map.current!.getLayer(layer.id)) {
        map.current!.addLayer(layer);
      }
    });
  }, []);

  const updateSourceData = useCallback((sourceId: string, features: GeoJSON.Feature[]) => {
    if (!map.current) return;
    const source = map.current.getSource(sourceId) as GeoJSONSource | undefined;
    if (source) {
      source.setData({
        type: 'FeatureCollection',
        features,
      });
    }
  }, []);

  const handleClick = useCallback((e: MapMouseEvent) => {
    if (!map.current) return;

    const features = map.current.queryRenderedFeatures(e.point, {
      layers: QUERYABLE_LAYERS,
    });

    if (features.length > 0) {
      setSelectedTerritory(features[0] as unknown as TerritoryFeature);
    } else {
      setSelectedTerritory(null);
    }
  }, [setSelectedTerritory]);

  const handleMouseMove = useCallback((e: MapMouseEvent) => {
    if (!map.current) return;

    const features = map.current.queryRenderedFeatures(e.point, {
      layers: QUERYABLE_LAYERS,
    });

    if (features.length > 0) {
      setHoveredTerritory(features[0] as unknown as TerritoryFeature);
      map.current.getCanvas().style.cursor = 'pointer';
      // US-015 — position de l'infobulle (coordonnées du canvas).
      const rect = map.current.getCanvas().getBoundingClientRect();
      const containerRect = mapContainer.current?.getBoundingClientRect();
      setTooltipPosition({
        x: e.point.x - (containerRect ? rect.left - containerRect.left : 0),
        y: e.point.y - (containerRect ? rect.top - containerRect.top : 0),
      });
    } else {
      setHoveredTerritory(null);
      setTooltipPosition(null);
      map.current.getCanvas().style.cursor = '';
    }
  }, [setHoveredTerritory]);

  const loadLevelData = useCallback(
    async (level: number, sourceId: string) => {
      if (!map.current || loadedLevels.current.has(level)) return;
      loadedLevels.current.add(level);
      try {
        const bounds = map.current!.getBounds();
        const response = await territoryApi.bbox(
          bounds.getWest(),
          bounds.getSouth(),
          bounds.getEast(),
          bounds.getNorth(),
          { level, format: 'geojson', limit: 500 }
        );
        const features = extractFeatures(response.data);
        updateSourceData(sourceId, features);
      } catch (error) {
        // Tolérance : on retire la garde pour réessayer au prochain passage.
        loadedLevels.current.delete(level);
        console.error(`[US-015] Échec du chargement du niveau ${level} :`, error);
      }
    },
    [updateSourceData]
  );

  const updateVisibleLayers = useCallback(
    (zoom: number) => {
      if (!map.current) return;

      Object.entries(ZOOM_THRESHOLDS).forEach(([layerId, [minZoom, maxZoom]]) => {
        const config = layerConfigs.find((l) => l.id === layerId);
        const shouldShow = zoom >= minZoom && zoom < maxZoom && config?.visible;

        if (map.current!.getLayer(LAYER_STYLES[layerId as MapLayerId].id)) {
          map.current!.setLayoutProperty(
            LAYER_STYLES[layerId as MapLayerId].id,
            'visibility',
            shouldShow ? 'visible' : 'none'
          );
        }
      });

      // US-015 — chargement paresseux (§83) : arrondissements (niveau 3) au
      // zoom ≥ 11, communes (niveau 4) au zoom ≥ 13 (une seule fois chacun).
      if (zoom >= 11) {
        void loadLevelData(3, 'arrondissements-source');
      }
      if (zoom >= 13) {
        void loadLevelData(4, 'communes-source');
      }
    },
    [layerConfigs, loadLevelData]
  );

  const loadInitialData = useCallback(async () => {
    if (!map.current) return;
    setLoading(true);

    try {
      const [regions, departments] = await Promise.all([
        territoryApi.list({ level: 1, format: 'geojson', limit: 20 }),
        territoryApi.list({ level: 2, format: 'geojson', limit: 50 }),
      ]);

      // US-015 — `/territories?format=geojson` renvoie un FeatureCollection RFC
      // 7946 brut (`{type, features}`), et non une enveloppe `{success, data}`.
      // Lire `.data.data` ici produisait `undefined`, donc `setData()` recevait
      // `{features: undefined}` et la carte restait vide — sans aucune erreur.
      if (map.current) {
        updateSourceData('regions-source', extractFeatures(regions.data));
        updateSourceData('departments-source', extractFeatures(departments.data));
        loadedLevels.current.add(1);
        loadedLevels.current.add(2);
      }
    } catch (error) {
      // US-015 — tolérance coupure réseau : journaliser mais conserver les
      // couches déjà chargées ; ne jamais casser l'explorateur.
      console.error('[US-015] Échec du chargement initial des territoires :', error);
    } finally {
      setLoading(false);
    }
  }, [setLoading, updateSourceData]);

  useEffect(() => {
    if (!mapContainer.current || map.current) return;

    map.current = new Map({
      container: mapContainer.current,
      style: {
        version: 8,
        sources: {
          [OSM_BASEMAP_ID]: {
            type: 'raster',
            tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
            tileSize: 256,
            maxzoom: 19,
            attribution:
              '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
          },
        },
        layers: [
          {
            id: OSM_BASEMAP_ID,
            type: 'raster',
            source: OSM_BASEMAP_ID,
          },
        ],
      },
      center: [-17.0, 14.5],
      zoom: 6,
      pitch: 0,
      bearing: 0,
      hash: true,
      attributionControl: false,
    });

    map.current.addControl(new NavigationControl({ showCompass: false }), 'top-right');
    map.current.addControl(new ScaleControl({ unit: 'metric' }), 'bottom-left');
    // Affiche l'attribution collectée automatiquement dans les sources du
    // style (crédit OSM, obligatoire selon la tile usage policy).
    map.current.addControl(new AttributionControl(), 'bottom-right');

    map.current.on('load', () => {
      setMapLoaded(true);
      initializeSourcesAndLayers();
      loadInitialData();
    });

    map.current.on('moveend', () => {
      const viewport = {
        longitude: map.current!.getCenter().lng,
        latitude: map.current!.getCenter().lat,
        zoom: map.current!.getZoom(),
        bearing: map.current!.getBearing(),
        pitch: map.current!.getPitch(),
      };
      setMapViewport(viewport);
      updateVisibleLayers(map.current!.getZoom());
    });

    map.current.on('click', handleClick);
    map.current.on('mousemove', handleMouseMove);
    map.current.on('mouseout', () => {
      setHoveredTerritory(null);
      setTooltipPosition(null);
    });

    return () => {
      map.current?.remove();
      map.current = null;
    };
  }, [handleClick, handleMouseMove, initializeSourcesAndLayers, loadInitialData, setHoveredTerritory, setMapViewport, updateVisibleLayers]);

  useEffect(() => {
    if (!map.current) return;
    activeLayers.forEach((layerId) => {
      const config = layerConfigs.find((l) => l.id === layerId);
      const layer = LAYER_STYLES[layerId];
      if (map.current!.getLayer(layer.id)) {
        map.current!.setLayoutProperty(layer.id, 'visibility', config?.visible ? 'visible' : 'none');
      }
    });
  }, [activeLayers, layerConfigs]);

  // US-015 — le conteneur de la carte doit rester monté en permanence : le
  // `useEffect` d'initialisation s'y attache via `mapContainer.current`. Le
  // spinner de chargement est une surcouche, ce qui évite un blocage circulaire
  // (pas de conteneur => pas de carte => jamais d'événement `load`).
  return (
    <div className="relative w-full h-full">
      <div ref={mapContainer} className="absolute inset-0 h-full w-full" />

      {!mapLoaded && (
        <div
          data-testid="loading-spinner"
          className="absolute inset-0 z-10 flex h-full w-full items-center justify-center bg-gray-100"
        >
          <div className="h-12 w-12 animate-spin rounded-full border-4 border-green-600 border-t-transparent" />
        </div>
      )}

      <MapTooltip feature={hoveredTerritory} position={tooltipPosition} />
    </div>
  );
}
