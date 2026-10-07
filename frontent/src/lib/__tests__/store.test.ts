import { useMapStore } from '@/lib/store';

describe('useMapStore (US-015 — état carte)', () => {
  beforeEach(() => {
    useMapStore.getState().resetFilters();
    useMapStore.getState().setPanelOpen(true);
  });

  it('initialise la vue sur le Sénégal (Dakar, zoom national)', () => {
    const { mapViewport } = useMapStore.getState();
    expect(mapViewport.longitude).toBeCloseTo(-17.0);
    expect(mapViewport.latitude).toBeCloseTo(14.5);
    expect(mapViewport.zoom).toBe(6);
  });

  it('expose les couches nationales par défaut (régions + départements)', () => {
    const { activeLayers } = useMapStore.getState();
    expect(activeLayers).toContain('regions');
    expect(activeLayers).toContain('departments');
    expect(activeLayers).not.toContain('communes');
  });

  it('bascule la visibilité des couches sans perdre les autres', () => {
    const { toggleLayer } = useMapStore.getState();
    toggleLayer('communes');
    expect(useMapStore.getState().activeLayers).toContain('communes');
    expect(useMapStore.getState().activeLayers).toContain('regions');
    toggleLayer('communes');
    expect(useMapStore.getState().activeLayers).not.toContain('communes');
  });

  it('resetFilters restaure les couches nationales et vide la sélection', () => {
    const state = useMapStore.getState();
    state.setSearchQuery('Dakar');
    state.setBbox([-17.6, 14.6, -17.0, 15.0]);
    state.resetFilters();
    const fresh = useMapStore.getState();
    expect(fresh.searchQuery).toBe('');
    expect(fresh.bbox).toBeNull();
    expect(fresh.selectedTerritory).toBeNull();
  });
});
