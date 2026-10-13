/**
 * Mock MapLibre GL utilisé par Jest via `moduleNameMapper`.
 *
 * Raison : `maplibre-gl` v6 n'expose qu'une condition `"import"` (ESM) dans son
 * champ `exports`, donc le résolveur CommonJS de Jest ne peut pas le charger.
 * Ce module de substitution rend l'API utilisée par `MapComponent` testable.
 *
 * La carte simulée :
 * - n'effectue aucun rendu WebGL et aucune requête de tuiles ;
 * - conserve les gestionnaires d'événements (`load`, `moveend`, ...) et permet
 *   de les déclencher via `emit()` ;
 * - trace les sources et couches ajoutées pour permettre les assertions.
 *
 * Aide au pilotage depuis les tests :
 * - `mockMapInstances` : instances créées (la dernière est la carte courante) ;
 * - `setMockZoom(z)`   : fixe la valeur renvoyée par `getZoom()` ;
 * - `resetMockZoom()`  : restaure le zoom national par défaut (6).
 *
 * Ce module est substitué via `moduleNameMapper` (et non via `jest.mock()`),
 * ce qui évite tout auto-mocking récursif de Jest.
 */

type MapEventHandler = (...args: unknown[]) => void;

interface MockRect {
  left: number;
  top: number;
  right: number;
  bottom: number;
  width: number;
  height: number;
}

/** Source GeoJSON simulée : ne conserve que `setData`. */
function createMockSource(): { setData: jest.Mock } {
  return { setData: jest.fn() };
}

/** Zoom simulé renvoyé par `getZoom()`. */
let mockZoom = 6;

/** Instance de carte simulée renvoyée par le constructeur `Map`. */
class MockMapInstance {
  /** Gestionnaires enregistrés, indexés par nom d'événement. */
  readonly handlers: Record<string, MapEventHandler[]> = {};

  private readonly sources = new Map<string, { setData: jest.Mock }>();
  private readonly layers = new Set<string>();

  on: jest.Mock;
  off: jest.Mock;
  addControl: jest.Mock;
  removeControl: jest.Mock;
  addSource: jest.Mock;
  addLayer: jest.Mock;
  removeLayer: jest.Mock;
  removeSource: jest.Mock;
  getSource: jest.Mock;
  getLayer: jest.Mock;
  setLayoutProperty: jest.Mock;
  setPaintProperty: jest.Mock;
  getCanvas: jest.Mock;
  getBounds: jest.Mock;
  getCenter: jest.Mock;
  getZoom: jest.Mock;
  getBearing: jest.Mock;
  getPitch: jest.Mock;
  queryRenderedFeatures: jest.Mock;
  remove: jest.Mock;

  /** Options passées au constructeur `Map` (permet d'assertionner le style). */
  initialOptions: unknown;

  constructor(options?: unknown) {
    this.initialOptions = options;

    this.on = jest.fn((event: string, handler: MapEventHandler) => {
      const existing = this.handlers[event] ?? [];
      existing.push(handler);
      this.handlers[event] = existing;
      return this;
    });

    this.off = jest.fn((event: string, handler: MapEventHandler) => {
      this.handlers[event] = (this.handlers[event] ?? []).filter((h) => h !== handler);
      return this;
    });

    this.addControl = jest.fn(() => this);
    this.removeControl = jest.fn(() => this);

    this.addSource = jest.fn((id: string) => {
      this.sources.set(id, createMockSource());
      return this;
    });

    this.addLayer = jest.fn((layer: { id: string }) => {
      this.layers.add(layer.id);
      return this;
    });

    this.removeLayer = jest.fn(() => this);
    this.removeSource = jest.fn(() => this);

    this.getSource = jest.fn((id: string) => this.sources.get(id) ?? null);
    this.getLayer = jest.fn((id: string) => (this.layers.has(id) ? { id } : null));

    this.setLayoutProperty = jest.fn(() => this);
    this.setPaintProperty = jest.fn(() => this);

    this.getCanvas = jest.fn(() => ({
      style: { cursor: '' },
      getBoundingClientRect: (): MockRect => ({
        left: 0,
        top: 0,
        right: 800,
        bottom: 600,
        width: 800,
        height: 600,
      }),
    }));

    this.getBounds = jest.fn(() => ({
      getWest: () => -18.0,
      getSouth: () => 13.0,
      getEast: () => -15.0,
      getNorth: () => 16.0,
    }));

    this.getCenter = jest.fn(() => ({ lng: -17.0, lat: 14.5 }));
    this.getZoom = jest.fn(() => mockZoom);
    this.getBearing = jest.fn(() => 0);
    this.getPitch = jest.fn(() => 0);

    this.queryRenderedFeatures = jest.fn(() => []);

    this.remove = jest.fn();
  }

  /** Déclenche manuellement un événement enregistré sur cette instance. */
  emit(event: string, ...args: unknown[]): void {
    (this.handlers[event] ?? []).slice().forEach((handler) => handler(...args));
  }
}

/** Instances de cartes créées ; la dernière est la carte courante. */
export const mockMapInstances: MockMapInstance[] = [];

/**
 * Constructeur simulé : enregistre chaque instance pour les assertions.
 *
 * Nommé `MockMap` puis exporté sous l'alias `Map` : cela garantit que le `Map`
 * global (utilisé par les champs de classe ci-dessus) n'est pas masqué par une
 * liaison de module de même nom, ce qui provoquerait une récursion infinie.
 */
const MockMap = jest.fn((_options?: unknown) => {
  const instance = new MockMapInstance(_options);
  mockMapInstances.push(instance);
  return instance;
});

export { MockMap as Map };

/** Fixe le zoom renvoyé par `getZoom()`. */
export function setMockZoom(zoom: number): void {
  mockZoom = zoom;
}

/** Restaure le zoom national par défaut. */
export function resetMockZoom(): void {
  mockZoom = 6;
}

export const NavigationControl = jest.fn((_options?: unknown) => ({}));

export const ScaleControl = jest.fn((_options?: unknown) => ({}));

export const AttributionControl = jest.fn((_options?: unknown) => ({}));

export const Marker = jest.fn((_options?: unknown) => ({
  setLngLat: jest.fn(),
  addTo: jest.fn(),
  remove: jest.fn(),
}));

export default Map;
