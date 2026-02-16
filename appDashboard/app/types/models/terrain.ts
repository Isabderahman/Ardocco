import type { GeoJSONPolygon } from '~/types/models/geojson'

export type TerrainCard = {
  id: string
  title: string
  description: string
  location: string
  price: string
  badge?: string
  to: string
  imageUrl?: string | null
  lat?: number | null
  lng?: number | null
  geojsonPolygon?: GeoJSONPolygon | null
}

export type TerrainFormState = {
  title: string
  province: string
  city: string
  area: string
  price: string
  description: string
  videoUrl: string
  agentName: string
  agentEmail: string
}

export type TerrainTableRow = {
  name: string
  status: string
  updated: string
}
