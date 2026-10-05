'use client';

import { useState } from 'react';
import { useMapStore } from '@/lib/store';
import type { TerritoryFeature } from '@/types/territoire';

const LEVEL_LABELS: Record<number, string> = {
  0: 'Pays',
  1: 'Région',
  2: 'Département',
  3: 'Arrondissement',
  4: 'Commune',
  5: 'District/Village',
  6: 'Hameau/Quartier',
};

export function SidePanel() {
  const { selectedTerritory, hoveredTerritory, isPanelOpen, setPanelOpen } = useMapStore();
  const [activeTab, setActiveTab] = useState<'details' | 'hierarchy' | 'actions'>('details');

  const territory = selectedTerritory || hoveredTerritory;

  if (!isPanelOpen) {
    return (
      <button
        onClick={() => setPanelOpen(true)}
        className="fixed right-4 top-1/2 -translate-y-1/2 z-20 bg-green-700 text-white p-3 rounded-l-lg shadow-lg hover:bg-green-800 transition-colors"
        aria-label="Ouvrir le panneau"
      >
        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
        </svg>
      </button>
    );
  }

  return (
    <aside className="w-80 bg-white border-l border-gray-200 flex flex-col overflow-hidden transition-transform duration-300">
      <div className="flex items-center justify-between p-4 border-b border-gray-200 bg-gray-50">
        <h2 className="text-lg font-semibold text-gray-900">
          {selectedTerritory ? 'Fiche Territoriale' : 'Survol'}
        </h2>
        <button
          onClick={() => setPanelOpen(false)}
          className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
          aria-label="Fermer le panneau"
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      {territory ? (
        <div className="flex-1 overflow-y-auto p-4">
          <div className="mb-4">
            <div className="flex items-center gap-2 mb-2">
              <span
                className="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800"
              >
                {LEVEL_LABELS[territory.properties.level] || `Niveau ${territory.properties.level}`}
              </span>
              {territory.properties.status && (
                <span
                  className={`px-2 py-1 text-xs font-medium rounded-full ${
                    territory.properties.status === 'active'
                      ? 'bg-blue-100 text-blue-800'
                      : territory.properties.status === 'pending'
                      ? 'bg-yellow-100 text-yellow-800'
                      : 'bg-gray-100 text-gray-800'
                  }`}
                >
                  {territory.properties.status}
                </span>
              )}
            </div>
            <h3 className="text-xl font-bold text-gray-900">{territory.properties.name}</h3>
            <p className="text-sm text-gray-500">Code: {territory.properties.code}</p>
          </div>

          <div className="border-t border-gray-100 pt-4 space-y-4">
            {activeTab === 'details' && <DetailsTab territory={territory} />}
            {activeTab === 'hierarchy' && <HierarchyTab territory={territory} />}
            {activeTab === 'actions' && <ActionsTab territory={territory} />}
          </div>

          <div className="mt-4 border-t border-gray-100 pt-4">
            <div className="flex gap-2">
              <button
                onClick={() => setActiveTab('details')}
                className={`flex-1 py-2 px-3 text-sm font-medium rounded-lg transition-colors ${
                  activeTab === 'details'
                    ? 'bg-green-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                }`}
              >
                Détails
              </button>
              <button
                onClick={() => setActiveTab('hierarchy')}
                className={`flex-1 py-2 px-3 text-sm font-medium rounded-lg transition-colors ${
                  activeTab === 'hierarchy'
                    ? 'bg-green-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                }`}
              >
                Hiérarchie
              </button>
              <button
                onClick={() => setActiveTab('actions')}
                className={`flex-1 py-2 px-3 text-sm font-medium rounded-lg transition-colors ${
                  activeTab === 'actions'
                    ? 'bg-green-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                }`}
              >
                Actions
              </button>
            </div>
          </div>
        </div>
      ) : (
        <div className="flex-1 flex items-center justify-center text-gray-500">
          <p className="text-center px-4">
            Cliquez sur une entité territoriale pour afficher ses détails
          </p>
        </div>
      )}
    </aside>
  );
}

function DetailsTab({ territory }: { territory: TerritoryFeature }) {
  const props = territory.properties;

  return (
    <div className="space-y-4">
      <dl className="space-y-3">
        <div>
          <dt className="text-xs font-medium text-gray-500 uppercase tracking-wider">Code ANSD</dt>
          <dd className="text-sm text-gray-900 font-mono">{props.ansdCode || '—'}</dd>
        </div>
        <div>
          <dt className="text-xs font-medium text-gray-500 uppercase tracking-wider">Code Parent</dt>
          <dd className="text-sm text-gray-900 font-mono">{props.parentCode || '—'}</dd>
        </div>
        <div>
          <dt className="text-xs font-medium text-gray-500 uppercase tracking-wider">Superficie</dt>
          <dd className="text-sm text-gray-900">
            {props.areaKm2 ? `${props.areaKm2.toLocaleString()} km²` : '—'}
          </dd>
        </div>
        <div>
          <dt className="text-xs font-medium text-gray-500 uppercase tracking-wider">Population</dt>
          <dd className="text-sm text-gray-900">
            {props.population ? props.population.toLocaleString() : '—'}
          </dd>
        </div>
        <div>
          <dt className="text-xs font-medium text-gray-500 uppercase tracking-wider">Chef-lieu</dt>
          <dd className="text-sm text-gray-900">{props.capital || '—'}</dd>
        </div>
        <div>
          <dt className="text-xs font-medium text-gray-500 uppercase tracking-wider">Qualité</dt>
          <dd className="text-sm text-gray-900 capitalize">{props.qualityStatus || '—'}</dd>
        </div>
      </dl>

      {territory.geometry && (
        <details className="group">
          <summary className="flex items-center justify-between cursor-pointer text-sm font-medium text-gray-700">
            <span>Géométrie</span>
            <svg className="w-4 h-4 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
            </svg>
          </summary>
          <div className="mt-2 p-3 bg-gray-50 rounded-lg text-xs font-mono text-gray-600 overflow-auto max-h-32">
            <pre>{JSON.stringify(territory.geometry, null, 2)}</pre>
          </div>
        </details>
      )}
    </div>
  );
}

function HierarchyTab({ territory }: { territory: TerritoryFeature }) {
  return (
    <div className="space-y-2">
      <p className="text-sm text-gray-500">L'hiérarchie sera chargée via l'API</p>
      <button className="w-full text-left px-3 py-2 text-sm text-green-600 hover:bg-green-50 rounded-lg transition-colors">
        Charger la hiérarchie complète
      </button>
    </div>
  );
}

function ActionsTab({ territory }: { territory: TerritoryFeature }) {
  return (
    <div className="space-y-2">
      <button className="w-full flex items-center gap-2 px-3 py-2 text-sm text-green-600 hover:bg-green-50 rounded-lg transition-colors">
        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
        </svg>
        Télécharger GeoJSON
      </button>
      <button className="w-full flex items-center gap-2 px-3 py-2 text-sm text-green-600 hover:bg-green-50 rounded-lg transition-colors">
        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
        Voir sur la carte
      </button>
      <button className="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-lg transition-colors">
        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        Signaler un problème
      </button>
    </div>
  );
}