import type { BBox, Feature, Geometry } from 'geojson'

export type ProvinceConfig = {
  code: string
  name: string
  color: string
}

export type FitBoundsOptions = {
  fitBounds?: boolean
  padding?: [number, number]
}

export type ProvinceApiModel = {
  id: string | number
  name_fr?: string
  name_ar?: string
  code?: string
  latitude?: number
  longitude?: number
  region_name?: string
  region_code?: string
  properties?: Record<string, unknown> | null
  bbox?: BBox | null
  geometry?: Geometry | null
}

export type GeoJSONFeature = Feature<Geometry, Record<string, unknown>>
