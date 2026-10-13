/**
 * État simulé du store cartographique, partagé par les tests d'intégration.
 *
 * Extrait dans un module dédié (plutôt que défini à l'intérieur d'une fabrique
 * `jest.mock()`) pour deux raisons :
 * - le typage est vérifié par `tsc` (une fabrique de mock est opaque) ;
 * - les `jest.fn()` sont stables : ils sont partagés par tous les rendus, ce qui
 *   permet d'asserter les appels (`setMapViewport`, `setLoading`, ...) après
 *   coup, contrairement à un objet recréé à chaque rendu.
 */

import type { MapLayerConfig, TerritoryFeature } from '@/types/territoire';

/** Configuration des couches par défaut (Régions + Départements visibles). */
export const mockLayerConfigs: MapLayerConfig[] = [
  { id: 'regions', label: 'Regions', level: 1, visible: true, color: '#006837' },
  { id: 'departments', label: 'Departements', level: 2, visible: true, color: '#1a9850' },
  { id: 'communes', label: 'Communes', level: 4, visible: false, color: '#a6d96a' },
  {
    id: 'arrondissements',
    label: 'Arrondissements',
    level: 3,
    visible: false,
    color: '#66bd63',
  },
];

/** État et actions simulés renvoyés par `useMapStore`. */
export const mockStoreState = {
  selectedTerritory: null as TerritoryFeature | null,
  hoveredTerritory: null as TerritoryFeature | null,
  searchQuery: '',
  searchResults: [] as TerritoryFeature[],
  activeLayers: mockLayerConfigs.filter((layer) => layer.visible).map((layer) => layer.id),
  layerConfigs: mockLayerConfigs,
  bbox: null,
  isPanelOpen: true,
  isLoading: false,
  setLoading: jest.fn(),
  setMapViewport: jest.fn(),
  setSelectedTerritory: jest.fn(),
  setHoveredTerritory: jest.fn(),
};
