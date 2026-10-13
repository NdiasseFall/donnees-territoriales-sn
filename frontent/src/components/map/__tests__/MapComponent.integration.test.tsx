/**
 * Tests d'intégration US-015 pour MapComponent.
 *
 * Mocks (fabriques inline, donc jamais auto-mockées par Jest) :
 * - `maplibre-gl` : carte simulée (aucun rendu WebGL, aucune tuile réseau) ;
 * - `@/lib/api`   : `territoryApi` (list / bbox) ;
 * - `@/lib/store` : `useMapStore`.
 *
 * Vérifie le chargement initial (niveaux 1 et 2), la configuration des sources
 * et couches, puis le comportement paresseux : niveau 3 (arrondissements) au
 * zoom >= 11, niveau 4 (communes) au zoom >= 13, sans rappel réseau inutile.
 */

import React from 'react';
import { render, screen, act } from '@testing-library/react';
import {
  setMockZoom,
  resetMockZoom,
  mockMapInstances,
} from '@/__mocks__/maplibre-gl';
import { MapComponent } from '../MapComponent';
import { territoryApi } from '@/lib/api';
import { useMapStore } from '@/lib/store';
import { mockStoreState } from '@/__mocks__/store';

interface MockMapInstance {
  emit: (event: string, ...args: unknown[]) => void;
  initialOptions: unknown;
  addControl: jest.Mock;
  addSource: jest.Mock;
  addLayer: jest.Mock;
}

jest.mock('@/lib/api', () => ({
  territoryApi: {
    list: jest.fn(async () => ({ data: { data: [] } })),
    bbox: jest.fn(async () => ({ data: { data: [] } })),
  },
}));

// Le mock du store est déclaré sans fabrique référençant d'import hissé :
// `jest.mock` est remonté au-dessus des `import`, donc `useMapStore` est
// auto-mocké puis ré-implémenté dans `beforeEach` via `mockImplementation`.
jest.mock('@/lib/store');

/** Dernière instance de carte simulée créée par le composant. */
function currentMap(): MockMapInstance {
  const instance = mockMapInstances[mockMapInstances.length - 1];
  if (!instance) {
    throw new Error('Aucune instance de carte simulee : le composant a-t-il ete rendu ?');
  }
  return instance;
}

/** Déclenche l'événement `load` et laisse les promesses se résoudre. */
async function loadMap(instance: MockMapInstance): Promise<void> {
  await act(async () => {
    instance.emit('load');
  });
}

/** Simule un `moveend` au zoom demandé. */
async function moveEnd(instance: MockMapInstance, zoom: number): Promise<void> {
  setMockZoom(zoom);
  await act(async () => {
    instance.emit('moveend');
  });
}

describe('MapComponent - US-015', () => {
  beforeEach(() => {
    mockMapInstances.length = 0;
    resetMockZoom();
    jest.clearAllMocks();
    // `useMapStore` est auto-mocké : on le rebranche sur l'état stable.
    (useMapStore as unknown as jest.Mock).mockReturnValue(mockStoreState);
    (territoryApi.list as jest.Mock).mockResolvedValue({ data: { data: [] } });
    (territoryApi.bbox as jest.Mock).mockResolvedValue({ data: { data: [] } });
  });

  it('affiche le spinner de chargement puis le conteneur de carte', () => {
    render(<MapComponent />);
    expect(screen.getByTestId('loading-spinner')).toBeInTheDocument();
    expect(currentMap()).toBeDefined();
  });

  it('instancie la carte sur le Senegal et attache 3 controles', () => {
    render(<MapComponent />);
    const instance = currentMap();
    // NavigationControl + ScaleControl + AttributionControl (crédit OSM).
    expect(instance.addControl).toHaveBeenCalledTimes(3);
  });

  it('declare le fond de carte OpenStreetMap dans le style initial', () => {
    render(<MapComponent />);
    const instance = currentMap();
    const options = instance.initialOptions as {
      style?: {
        sources?: Record<string, { type?: string }>;
        layers?: Array<{ id: string; source: string; type?: string }>;
      };
    };
    const style = options.style;

    // La source raster OSM est présente et la couche la référence en premier
    // (donc sous les couches territoriales ajoutées ensuite).
    expect(style?.sources?.['osm-basemap']).toEqual(
      expect.objectContaining({ type: 'raster' })
    );
    expect(style?.layers?.[0]).toEqual(
      expect.objectContaining({ id: 'osm-basemap', source: 'osm-basemap' })
    );
  });

  it('configure les 4 sources et les 4 couches au chargement', async () => {
    render(<MapComponent />);
    const instance = currentMap();
    await loadMap(instance);

    expect(instance.addSource).toHaveBeenCalledWith(
      'regions-source',
      expect.objectContaining({ type: 'geojson' })
    );
    expect(instance.addSource).toHaveBeenCalledWith(
      'departments-source',
      expect.objectContaining({ type: 'geojson' })
    );
    expect(instance.addSource).toHaveBeenCalledWith(
      'arrondissements-source',
      expect.objectContaining({ type: 'geojson' })
    );
    expect(instance.addSource).toHaveBeenCalledWith(
      'communes-source',
      expect.objectContaining({ type: 'geojson' })
    );
    expect(instance.addLayer).toHaveBeenCalledTimes(4);
  });

  it('charge les niveaux 1 (regions) et 2 (departements) a l initialisation', async () => {
    render(<MapComponent />);
    await loadMap(currentMap());

    expect(territoryApi.list).toHaveBeenCalledWith(
      expect.objectContaining({ level: 1, format: 'geojson' })
    );
    expect(territoryApi.list).toHaveBeenCalledWith(
      expect.objectContaining({ level: 2, format: 'geojson' })
    );
    expect(territoryApi.bbox).not.toHaveBeenCalled();
  });


  it('ne demande aucun niveau 3/4 au zoom 6 (< 11)', async () => {
    render(<MapComponent />);
    const instance = currentMap();
    await loadMap(instance);
    await moveEnd(instance, 6);

    expect(territoryApi.bbox).not.toHaveBeenCalled();
  });

  it('charge le niveau 3 (arrondissements) au zoom 12 (>= 11)', async () => {
    render(<MapComponent />);
    const instance = currentMap();
    await loadMap(instance);
    await moveEnd(instance, 12);

    expect(territoryApi.bbox).toHaveBeenCalledWith(
      -18.0,
      13.0,
      -15.0,
      16.0,
      expect.objectContaining({ level: 3, format: 'geojson' })
    );
  });

  it('ne charge pas le niveau 4 (communes) au zoom 12 (< 13)', async () => {
    render(<MapComponent />);
    const instance = currentMap();
    await loadMap(instance);
    await moveEnd(instance, 12);

    const level4Calls = (territoryApi.bbox as jest.Mock)
      .mock.calls.filter((call) => (call[4] as { level?: number })?.level === 4);
    expect(level4Calls).toHaveLength(0);
  });

  it('charge le niveau 4 (communes) au zoom 14 (>= 13)', async () => {
    render(<MapComponent />);
    const instance = currentMap();
    await loadMap(instance);
    await moveEnd(instance, 14);

    expect(territoryApi.bbox).toHaveBeenCalledWith(
      -18.0,
      13.0,
      -15.0,
      16.0,
      expect.objectContaining({ level: 4, format: 'geojson' })
    );
  });

  it('ne redemande pas un niveau deja charge (garde anti-rebond)', async () => {
    render(<MapComponent />);
    const instance = currentMap();
    await loadMap(instance);
    await moveEnd(instance, 14);
    const callsAfterFirst = (territoryApi.bbox as jest.Mock).mock.calls.length;

    await moveEnd(instance, 14);

    expect((territoryApi.bbox as jest.Mock).mock.calls).toHaveLength(callsAfterFirst);
  });

  it('met a jour le viewport du store lors d un moveend', async () => {
    render(<MapComponent />);
    const instance = currentMap();
    await loadMap(instance);
    await moveEnd(instance, 8);

    expect(mockStoreState.setMapViewport).toHaveBeenCalledWith(
      expect.objectContaining({ longitude: -17.0, latitude: 14.5, zoom: 8 })
    );
  });
});

