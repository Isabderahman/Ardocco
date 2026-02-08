/**
 * Province API Service
 * Handles all API calls related to provinces and boundaries
 */

import type { ApiResponse } from '~/types/models/api'
import type { GeoJSONFeature, ProvinceApiModel } from '~/types/models/province'

// Default to Nuxt server proxy routes under `/api/backend` (see `server/api/backend/*`)
const DEFAULT_API_URL = '/api/backend'

function normalizeApiBaseUrl(apiBaseUrl: string): string {
  const normalized = String(apiBaseUrl || '').trim().replace(/\/+$/, '')
  if (!normalized) return DEFAULT_API_URL

  // If the caller is using Nuxt server proxy routes, keep the proxy prefix as-is.
  if (normalized === DEFAULT_API_URL || normalized.endsWith(DEFAULT_API_URL)) return normalized

  // If the caller provided a host without a scheme (ex: "api.ardocco.com"), make it absolute.
  // Keep relative URLs (starting with "/") unchanged.
  let base = normalized
  if (!base.startsWith('/') && !/^https?:\/\//.test(base)) {
    const hostPort = base.split('/')[0] || base
    const portMatch = hostPort.match(/:(\d+)$/)
    const port = portMatch ? Number(portMatch[1]) : null
    const host = hostPort.replace(/:(\d+)$/, '')
    const isLocal = /^(localhost|127\.|0\.0\.0\.0|::1|backend)\b/.test(host)

    const scheme = isLocal
      ? 'http'
      : port === 80
        ? 'http'
        : port === 443
          ? 'https'
          : port
            ? 'http'
            : 'https'

    base = `${scheme}://${base}`
  }

  // If the caller points directly at the backend host/root, ensure we hit Laravel's `/api/*` routes.
  if (base.endsWith('/api')) return base

  return `${base}/api`
}

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
      const baseUrl = normalizeApiBaseUrl(apiBaseUrl)
      const response = await fetch(`${baseUrl}/geo/provinces/${encodeURIComponent(regionCode)}`)

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
      const baseUrl = normalizeApiBaseUrl(apiBaseUrl)
      const response = await fetch(`${baseUrl}/geo/province/${encodeURIComponent(provinceCode)}`)

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
