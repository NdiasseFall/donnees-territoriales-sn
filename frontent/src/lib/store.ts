'use client';

import { create } from 'zustand';
import type { MapLayerConfig, MapLayerId, TerritoryFeature } from '@/types/territoire';

interface MapState {
  selectedTerritory: TerritoryFeature | null;
  hoveredTerritory: TerritoryFeature | null;
  searchQuery: string;
  searchResults: TerritoryFeature[];
  activeLayers: MapLayerId[];
  layerConfigs: MapLayerConfig[];
  bbox: [number, number, number, number] | null;
  isPanelOpen: boolean;
  isLoading: boolean;
  mapViewport: {
    longitude: number;
    latitude: number;
    zoom: number;
    bearing: number;
    pitch: number;
  };
}

interface MapActions {
  setSelectedTerritory: (feature: TerritoryFeature | null) => void;
  setHoveredTerritory: (feature: TerritoryFeature | null) => void;
  setSearchQuery: (query: string) => void;
  setSearchResults: (results: TerritoryFeature[]) => void;
  toggleLayer: (layerId: MapLayerId) => void;
  setLayerVisibility: (layerId: MapLayerId, visible: boolean) => void;
  setBbox: (bbox: [number, number, number, number] | null) => void;
  setPanelOpen: (open: boolean) => void;
  setLoading: (loading: boolean) => void;
  setMapViewport: (viewport: Partial<MapState['mapViewport']>) => void;
  resetFilters: () => void;
}

const DEFAULT_LAYERS: MapLayerConfig[] = [
  { id: 'regions', label: 'Régions', level: 1, visible: true, color: '#006837' },
  { id: 'departments', label: 'Départements', level: 2, visible: true, color: '#1a9850' },
  { id: 'communes', label: 'Communes', level: 4, visible: false, color: '#66bd63' },
  { id: 'arrondissements', label: 'Arrondissements', level: 3, visible: false, color: '#a6d96a' },
];

export const useMapStore = create<MapState & MapActions>((set) => ({
  selectedTerritory: null,
  hoveredTerritory: null,
  searchQuery: '',
  searchResults: [],
  activeLayers: DEFAULT_LAYERS.map((l) => l.id),
  layerConfigs: DEFAULT_LAYERS,
  bbox: null,
  isPanelOpen: true,
  isLoading: false,
  mapViewport: {
    longitude: -17.0,
    latitude: 14.5,
    zoom: 6,
    bearing: 0,
    pitch: 0,
  },

  setSelectedTerritory: (feature) => set({ selectedTerritory: feature }),
  setHoveredTerritory: (feature) => set({ hoveredTerritory: feature }),
  setSearchQuery: (query) => set({ searchQuery: query }),
  setSearchResults: (results) => set({ searchResults: results }),
  setBbox: (bbox) => set({ bbox }),
  setPanelOpen: (open) => set({ isPanelOpen: open }),
  setLoading: (loading) => set({ isLoading: loading }),
  setMapViewport: (viewport) =>
    set((state) => ({
      mapViewport: { ...state.mapViewport, ...viewport },
    })),

  toggleLayer: (layerId) =>
    set((state) => {
      const layer = state.layerConfigs.find((l) => l.id === layerId);
      if (!layer) return state;
      const visible = !state.activeLayers.includes(layerId);
      return {
        activeLayers: visible
          ? [...state.activeLayers, layerId]
          : state.activeLayers.filter((id) => id !== layerId),
        layerConfigs: state.layerConfigs.map((l) =>
          l.id === layerId ? { ...l, visible } : l
        ),
      };
    }),

  setLayerVisibility: (layerId, visible) =>
    set((state) => ({
      activeLayers: visible
        ? [...new Set([...state.activeLayers, layerId])]
        : state.activeLayers.filter((id) => id !== layerId),
      layerConfigs: state.layerConfigs.map((l) =>
        l.id === layerId ? { ...l, visible } : l
      ),
    })),

  resetFilters: () =>
    set({
      selectedTerritory: null,
      hoveredTerritory: null,
      searchQuery: '',
      searchResults: [],
      bbox: null,
      activeLayers: DEFAULT_LAYERS.map((l) => l.id),
      layerConfigs: DEFAULT_LAYERS.map((l) => ({ ...l, visible: l.level <= 2 })),
    }),
}));