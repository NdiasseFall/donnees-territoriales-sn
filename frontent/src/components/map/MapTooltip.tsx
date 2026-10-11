'use client';

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

interface MapTooltipProps {
  feature: TerritoryFeature | null;
  position: { x: number; y: number } | null;
}

/**
 * US-015 — Infobulle de survol (nom + type), positionnée au curseur.
 * Rendue uniquement quand une entité est survolée ; aucun `any`.
 */
export function MapTooltip({ feature, position }: MapTooltipProps) {
  if (feature === null || position === null) {
    return null;
  }

  const label = LEVEL_LABELS[feature.properties.level] ?? `Niveau ${feature.properties.level}`;

  return (
    <div
      role="tooltip"
      aria-label={`${feature.properties.name} — ${label}`}
      className="pointer-events-none absolute z-10 max-w-60 -translate-x-1/2 rounded-lg bg-gray-900/95 px-3 py-1.5 text-white shadow-lg"
      style={{ left: position.x, top: position.y - 12, transform: 'translate(-50%, -100%)' }}
    >
      <p className="truncate text-sm font-semibold">{feature.properties.name}</p>
      <p className="text-xs text-gray-300">{label}</p>
    </div>
  );
}
