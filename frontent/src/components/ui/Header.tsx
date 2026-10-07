'use client';

import { useCallback, useRef, useState } from 'react';
import { useMapStore } from '@/lib/store';
import { territoryApi } from '@/lib/api';
import type { MapLayerId, TerritoryFeature } from '@/types/territoire';

const LAYER_LABELS: Record<MapLayerId, string> = {
  regions: 'Régions',
  departments: 'Départements',
  communes: 'Communes',
  arrondissements: 'Arrondissements',
};

export function Header() {
  const {
    setSearchQuery,
    searchResults,
    setSearchResults,
    activeLayers,
    layerConfigs,
    toggleLayer,
    setSelectedTerritory,
    setLoading,
    isLoading,
  } = useMapStore();

  const [searchInput, setSearchInput] = useState('');
  const [showSearchResults, setShowSearchResults] = useState(false);
  const [showLayerPanel, setShowLayerPanel] = useState(false);
  const searchTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const handleSearch = useCallback(async (query: string) => {
    if (!query.trim() || query.length < 2) {
      setSearchQuery(query);
      return;
    }

    setSearchInput(query);
    setSearchQuery(query);

    if (searchTimeoutRef.current) {
      clearTimeout(searchTimeoutRef.current);
    }

    searchTimeoutRef.current = setTimeout(async () => {
      setLoading(true);
      try {
        const response = await territoryApi.search(query, { limit: 10 });
        if (response.data.success) {
          setSearchResults(response.data.data as unknown as TerritoryFeature[]);
        }
      } catch (error) {
        console.error('Search failed:', error);
      } finally {
        setLoading(false);
      }
    }, 300);
  }, [setSearchQuery, setLoading, setSearchResults]);

  const handleSelectResult = (feature: TerritoryFeature) => {
    setSelectedTerritory(feature);
    setShowSearchResults(false);
    setSearchInput('');
    setSearchQuery('');
  };

  const handleLayerToggle = (layerId: MapLayerId) => {
    toggleLayer(layerId);
  };

  return (
    <header className="bg-white border-b border-gray-200 z-10">
      <div className="max-w-full mx-auto px-4">
        <div className="flex items-center justify-between h-16">
          <div className="flex items-center gap-4 flex-1 max-w-3xl">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 bg-green-600 rounded-lg flex items-center justify-center">
                <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <div>
                <h1 className="text-xl font-bold text-gray-900">Données Territoriales SN</h1>
                <p className="text-xs text-gray-500">Plateforme Nationale</p>
              </div>
            </div>

            <div className="relative flex-1 max-w-xl hidden md:block">
              <div className="relative">
                <input
                  type="text"
                  value={searchInput}
                  onChange={(e) => handleSearch(e.target.value)}
                  onFocus={() => setShowSearchResults(searchInput.length >= 2)}
                  onBlur={() => setTimeout(() => setShowSearchResults(false), 200)}
                  placeholder="Rechercher un territoire (région, département, commune)..."
                  className="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all"
                  aria-label="Recherche territoriale"
                />
                <svg className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                {isLoading && (
                  <svg className="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-green-600 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                  </svg>
                )}
              </div>

              {showSearchResults && searchInput.length >= 2 && (
                <div className="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden z-50 max-h-96 overflow-y-auto">
                  <div className="px-3 py-2 border-b border-gray-100 text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Résultats
                  </div>
                  {searchResults.length === 0 && !isLoading && (
                    <div className="px-4 py-3 text-sm text-gray-500 text-center">
                      Aucun résultat pour "{searchInput}"
                    </div>
                  )}
                  {searchResults.map((feature, index) => (
                    <button
                      key={`${feature.properties.uuid}-${index}`}
                      onClick={() => handleSelectResult(feature)}
                      className="w-full px-4 py-3 text-left hover:bg-gray-50 border-b border-gray-100 last:border-0 transition-colors"
                    >
                      <div className="flex items-center gap-2">
                        <span className="px-2 py-0.5 text-xs font-medium rounded bg-green-100 text-green-700">
                          {feature.properties.levelLabel}
                        </span>
                        <span className="font-medium text-gray-900">{feature.properties.name}</span>
                      </div>
                      <div className="text-xs text-gray-500 mt-0.5">
                        {feature.properties.code} • {feature.properties.parentCode || 'Niveau supérieur'}
                      </div>
                    </button>
                  ))}
                  {isLoading && (
                    <div className="px-4 py-3 text-center text-sm text-gray-500">
                      <div className="flex items-center justify-center gap-2">
                        <svg className="w-4 h-4 animate-spin text-green-600" fill="none" viewBox="0 0 24 24">
                          <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                          <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Recherche en cours...
                      </div>
                    </div>
                  )}
                </div>
              )}
            </div>
          </div>

          <div className="flex items-center gap-2">
            <div className="relative hidden sm:block">
              <button
                onClick={() => setShowLayerPanel(!showLayerPanel)}
                className="flex items-center gap-2 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors"
                aria-label="Couches cartographiques"
              >
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <span>Couches</span>
              </button>

              {showLayerPanel && (
                <div className="absolute right-0 top-full mt-1 w-56 bg-white border border-gray-200 rounded-lg shadow-lg py-2 z-50">
                  <div className="px-3 py-2 border-b border-gray-100">
                    <h3 className="text-sm font-medium text-gray-900">Couches actives</h3>
                  </div>
                  <div className="space-y-1 p-2">
                    {layerConfigs
                      .sort((a, b) => a.level - b.level)
                      .map((layer) => (
                        <label
                          key={layer.id}
                          className="flex items-center gap-2 px-2 py-2 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
                        >
                          <input
                            type="checkbox"
                            checked={activeLayers.includes(layer.id)}
                            onChange={() => handleLayerToggle(layer.id)}
                            className="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500 focus:ring-2"
                          />
                          <span
                            className="w-3 h-3 rounded"
                            style={{ backgroundColor: layer.color }}
                          />
                          <span className="text-sm text-gray-700">{LAYER_LABELS[layer.id]}</span>
                        </label>
                      ))}
                  </div>
                </div>
              )}
            </div>

            <button className="md:hidden p-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors" aria-label="Menu">
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </header>
  );
}