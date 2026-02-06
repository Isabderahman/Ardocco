import type { TerrainType } from '~/types/enums/terrain'

export type SellFilters = {
  q: string
  typeTerrain?: TerrainType
  priceMin: number | null
  priceMax: number | null
  areaMin: number | null
  areaMax: number | null
}
