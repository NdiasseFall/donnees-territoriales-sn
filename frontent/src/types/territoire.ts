export interface TerritoryFeatureProperties {
  uuid: string;
  code: string;
  name: string;
  ansdCode: string | null;
  level: number;
  levelLabel: string;
  parentCode: string | null;
  areaKm2: number | null;
  population: number | null;
  capital: string | null;
  status: string;
  qualityStatus: string;
}

export interface TerritoryFeature extends GeoJSON.Feature {
  properties: TerritoryFeatureProperties;
  geometry: GeoJSON.Geometry;
}

export interface TerritoryFeatureCollection extends GeoJSON.FeatureCollection {
  features: TerritoryFeature[];
}

export type MapLayerId = 'regions' | 'departments' | 'communes' | 'arrondissements';

export interface MapLayerConfig {
  id: MapLayerId;
  label: string;
  level: number;
  visible: boolean;
  color: string;
}