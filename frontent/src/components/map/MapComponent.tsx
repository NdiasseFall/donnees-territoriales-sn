'use client';

import { useEffect, useRef, useState } from 'react';
import {
  Map,
  NavigationControl,
  ScaleControl,
} from 'maplibre-gl';
import type { MapMouseEvent, GeoJSONSource, LayerSpecification, SourceSpecification } from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { useMapStore } from '@/lib/store';
import { territoryApi } from '@/lib/api';
import type { MapLayerId, TerritoryFeature } from '@/types/territoire';

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

export function MapComponent() {
  const mapContainer = useRef<HTMLDivElement>(null);
  const map = useRef<Map | null>(null);
  const [mapLoaded, setMapLoaded] = useState(false);

  const {
    activeLayers,
    layerConfigs,
    selectedTerritory,
    hoveredTerritory,
    bbox,
    isLoading,
    setLoading,
    setMapViewport,
    setSelectedTerritory,
    setHoveredTerritory,
    setBbox,
  } = useMapStore();

  useEffect(() => {
    if (!mapContainer.current || map.current) return;

    map.current = new Map({
      container: mapContainer.current,
      style: {
        version: 8,
        sources: {},
        layers: [],
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
    map.current.on('mouseout', () => setHoveredTerritory(null));

    return () => {
      map.current?.remove();
      map.current = null;
    };
  }, []);

  const initializeSourcesAndLayers = () => {
    if (!map.current) return;

    const sources: Record<string, SourceSpecification> = {
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
  };

  const loadInitialData = async () => {
    if (!map.current) return;
    setLoading(true);

    try {
      const [regions, departments] = await Promise.all([
        territoryApi.list({ level: 1, format: 'geojson', limit: 20 }),
        territoryApi.list({ level: 2, format: 'geojson', limit: 50 }),
      ]);

      if (map.current) {
        updateSourceData('regions-source', regions.data.data);
        updateSourceData('departments-source', departments.data.data);
      }
    } catch (error) {
      console.error('Failed to load initial data:', error);
    } finally {
      setLoading(false);
    }
  };

  const updateSourceData = (sourceId: string, features: GeoJSON.Feature[]) => {
    if (!map.current) return;
    const source = map.current.getSource(sourceId) as GeoJSONSource | undefined;
    if (source) {
      source.setData({ type: 'FeatureCollection', features });
    }
  };

  const handleClick = (e: MapMouseEvent) => {
    if (!map.current) return;

    const features = map.current.queryRenderedFeatures(e.point, {
      layers: QUERYABLE_LAYERS,
    });

    if (features.length > 0) {
      setSelectedTerritory(features[0] as unknown as TerritoryFeature);
    } else {
      setSelectedTerritory(null);
    }
  };

  const handleMouseMove = (e: MapMouseEvent) => {
    if (!map.current) return;

    const features = map.current.queryRenderedFeatures(e.point, {
      layers: QUERYABLE_LAYERS,
    });

    if (features.length > 0) {
      setHoveredTerritory(features[0] as unknown as TerritoryFeature);
      map.current.getCanvas().style.cursor = 'pointer';
    } else {
      setHoveredTerritory(null);
      map.current.getCanvas().style.cursor = '';
    }
  };

  const updateVisibleLayers = (zoom: number) => {
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
  };

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

  if (!mapLoaded) {
    return (
      <div className="w-full h-full bg-gray-100 flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-4 border-green-600 border-t-transparent" />
      </div>
    );
  }

  return <div ref={mapContainer} className="w-full h-full" />;
}