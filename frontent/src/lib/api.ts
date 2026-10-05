import axios from 'axios';

export const apiClient = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  timeout: 30_000,
});

export interface GeoJsonResponse {
  type: 'FeatureCollection';
  crs?: { type: string; properties: { name: string } };
  features: GeoJSON.Feature[];
  total_features?: number;
}

export interface TerritoryListResponse {
  success: boolean;
  count: number;
  data: GeoJSON.Feature[];
}

export interface TerritoryDetailResponse {
  success: boolean;
  data: GeoJSON.Feature;
}

export interface HierarchyNodeResponse {
  success: boolean;
  data: {
    territory: GeoJSON.Feature;
    ancestors: GeoJSON.Feature[];
    children: GeoJSON.Feature[];
  };
}

export interface SearchResponse {
  success: boolean;
  query: string;
  count: number;
  data: GeoJSON.Feature[];
}

export interface ReverseGeocodeResponse {
  success: boolean;
  query: { longitude: number; latitude: number };
  hierarchy: {
    country: GeoJSON.Feature | null;
    region: GeoJSON.Feature | null;
    department: GeoJSON.Feature | null;
    arrondissement: GeoJSON.Feature | null;
    commune: GeoJSON.Feature | null;
    district_or_village: GeoJSON.Feature | null;
    hamlet: GeoJSON.Feature | null;
  };
}

export const territoryApi = {
  list: (params?: {
    level?: number;
    parentCode?: string;
    limit?: number;
    offset?: number;
    format?: 'json' | 'geojson';
  }) => apiClient.get<TerritoryListResponse>('/territories', { params }),

  getByCode: (code: string, params?: { format?: 'json' | 'geojson'; simplified?: boolean }) =>
    apiClient.get<TerritoryDetailResponse>(`/territories/${code}`, { params }),

  hierarchy: (code: string) =>
    apiClient.get<HierarchyNodeResponse>(`/territories/${code}/hierarchy`),

  search: (query: string, params?: { level?: number; parentCode?: string; limit?: number }) =>
    apiClient.get<SearchResponse>('/search', { params: { q: query, ...params } }),

  reverseGeocode: (longitude: number, latitude: number, level?: number) =>
    apiClient.get<ReverseGeocodeResponse>('/spatial/reverse-geocode', {
      params: { lon: longitude, lat: latitude, level },
    }),

  bbox: (minLon: number, minLat: number, maxLon: number, maxLat: number, params?: { level?: number; format?: 'json' | 'geojson'; limit?: number }) =>
    apiClient.get<GeoJsonResponse | TerritoryListResponse>('/spatial/bbox', {
      params: { bbox: `${minLon},${minLat},${maxLon},${maxLat}`, ...params },
    }),
};