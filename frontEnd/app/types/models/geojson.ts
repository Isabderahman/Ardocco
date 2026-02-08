export type GeoJSONPosition = [number, number] | [number, number, number]

export type GeoJSONPolygon = {
  type: 'Polygon'
  coordinates: GeoJSONPosition[][]
}
