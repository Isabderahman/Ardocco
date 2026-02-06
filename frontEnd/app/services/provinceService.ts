/**
 * Province API Service
 * Handles all API calls related to provinces and boundaries
 */

import type { ApiResponse } from '~/types/models/api'
import type { GeoJSONFeature, ProvinceApiModel } from '~/types/models/province'

// Default to Nuxt server proxy routes under `/api/backend` (see `server/api/backend/*`)
const DEFAULT_API_URL = '/api/backend'

export const provinceService = {
  /**
   * Get all provinces for a specific region
   * @param {string} regionCode - Region code (e.g., 'CS')
   * @param {string} apiBaseUrl - Base URL for API (optional, defaults to DEFAULT_API_URL)
   * @returns {Promise<Object>}
   */
  async getProvincesByRegion(
    regionCode: string,
    apiBaseUrl: string = DEFAULT_API_URL
  ): Promise<ApiResponse<ProvinceApiModel[]>> {
    try {
      const response = await fetch(`${apiBaseUrl}/geo/provinces/${encodeURIComponent(regionCode)}`)

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json() as ApiResponse<ProvinceApiModel[]>

      if (!data.success) {
        throw new Error(data.message || 'Failed to fetch provinces')
      }

      return data
    } catch (error) {
      console.error('Error fetching provinces by region:', error)
      throw error
    }
  },

  /**
   * Get a specific province by its code with boundary data
   * @param {string} provinceCode - Province code (e.g., 'CAS')
   * @param {string} apiBaseUrl - Base URL for API (optional, defaults to DEFAULT_API_URL)
   * @returns {Promise<Object|null>}
   */
  async getProvinceByCode(
    provinceCode: string,
    apiBaseUrl: string = DEFAULT_API_URL
  ): Promise<ProvinceApiModel | null> {
    try {
      const response = await fetch(`${apiBaseUrl}/geo/province/${encodeURIComponent(provinceCode)}`)

      if (!response.ok) {
        if (response.status === 404) {
          console.warn(`Province not found: ${provinceCode}`)
          return null
        }
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json() as ApiResponse<ProvinceApiModel>

      if (!data.success || !data.data) {
        console.warn(`No data found for province: ${provinceCode}`)
        return null
      }

      return data.data
    } catch (error) {
      console.error(`Error fetching province ${provinceCode}:`, error)
      return null
    }
  },

  /**
   * Convert province data to GeoJSON feature format
   * @param {Object} province - Province data from API
   * @param {string} displayName - Optional display name
   * @returns {Object|null}
   */
  toGeoJSONFeature(province: ProvinceApiModel, displayName: string | null = null): GeoJSONFeature | null {
    if (!province || !province.geometry) {
      return null
    }

    const fallbackName = province.name_fr || String(province.code || '')
    const properties = (province.properties && typeof province.properties === 'object' && !Array.isArray(province.properties))
      ? province.properties
      : {
          name: fallbackName,
          display_name: displayName || `${fallbackName}, ${province.region_name || 'Morocco'}`
        }

    return {
      type: 'Feature',
      properties,
      geometry: province.geometry
    }
  }
}
