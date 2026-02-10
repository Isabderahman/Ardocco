/**
 * Province Map Composable
 * Handles province boundary loading and map rendering logic
 */

import { ref } from 'vue'
import type { GeoJSON as LeafletGeoJSON, LatLngBounds, Layer, Map as LeafletMap } from 'leaflet'
import { provinceService } from '~/services/provinceService'
import type { LeafletNamespace } from '~/types/models/leaflet'
import type { FitBoundsOptions, GeoJSONFeature, ProvinceApiModel, ProvinceConfig } from '~/types/models/province'

/**
 * Composable for managing province map functionality
 * @param {Array} provinces - Array of province configurations with code, name, and color
 * @returns {Object} Map state and methods
 */
export function useProvinceMap(provinces: ProvinceConfig[] = []) {
  // Always call Nuxt server proxy routes (SSR/dev-friendly, no CORS). Backend host is configured via `runtimeConfig.backendBaseUrl`.
  const apiBaseUrl = '/api/backend'

  const loading = ref(false)
  const error = ref<string | null>(null)
  const loadedProvinces = ref<string[]>([])

  /**
   * Fetch boundary data for a single province
   * @param {string} provinceCode - Province code
   * @returns {Promise<Object|null>}
   */
  async function fetchProvinceBoundary(provinceCode: string): Promise<GeoJSONFeature | null> {
    try {
      // Pass apiBaseUrl to service
      const province = await provinceService.getProvinceByCode(provinceCode, apiBaseUrl)

      if (!province) {
        if (import.meta.dev) console.warn(`No boundary data found for province: ${provinceCode}`)
        return null
      }

      if (!province.geometry) {
        if (import.meta.dev) console.warn(`Province ${provinceCode} has no geometry data`)
        return null
      }

      return provinceService.toGeoJSONFeature(province)
    } catch (err) {
      console.error(`Failed to fetch boundary for ${provinceCode}:`, err)
      return null
    }
  }

  /**
   * Load all province boundaries and add them to the map
   * @param {Object} L - Leaflet instance
   * @param {Object} map - Leaflet map instance
   * @param {Array} provinceConfigs - Array of province configurations
   * @returns {Promise<void>}
   */
  async function loadProvinceBoundaries(
    L: LeafletNamespace,
    map: LeafletMap,
    provinceConfigs: ProvinceConfig[] = provinces,
    options?: FitBoundsOptions
  ): Promise<void> {
    if (!L || !map) {
      throw new Error('Leaflet and map instances are required')
    }

    loading.value = true
    error.value = null
    loadedProvinces.value = []

    let overallBounds: LatLngBounds | null = null

    try {
      for (const config of provinceConfigs) {
        const feature = await fetchProvinceBoundary(config.code)

        if (feature) {
          const layer = addProvinceToMap(L, map, feature, config)
          overallBounds = overallBounds ? overallBounds.extend(layer.getBounds()) : layer.getBounds()
          loadedProvinces.value.push(config.code)
        }

        // Small delay to avoid overwhelming the browser
        await delay(100)
      }

      if (overallBounds && (options?.fitBounds ?? true)) {
        map.fitBounds(overallBounds, { padding: options?.padding ?? [20, 20] })
      }
    } catch (err) {
      error.value = err instanceof Error ? err.message : String(err)
      console.error('Error loading province boundaries:', err)
    } finally {
      loading.value = false
    }
  }

  /**
   * Load all province boundaries for a region in a single request
   * @param {Object} L - Leaflet instance
   * @param {Object} map - Leaflet map instance
   * @param {string} regionCode - Region code (e.g., 'CS')
   * @param {Array} provinceConfigs - Array of province configurations
   * @returns {Promise<void>}
   */
  async function loadProvinceBoundariesByRegion(
    L: LeafletNamespace,
    map: LeafletMap,
    regionCode: string,
    provinceConfigs: ProvinceConfig[] = provinces,
    options?: FitBoundsOptions
  ): Promise<void> {
    if (!L || !map) {
      throw new Error('Leaflet and map instances are required')
    }

    const normalizedRegionCode = String(regionCode || '').trim()
    if (!normalizedRegionCode) {
      throw new Error('regionCode is required')
    }

    loading.value = true
    error.value = null
    loadedProvinces.value = []

    let overallBounds: LatLngBounds | null = null

    try {
      const result = await provinceService.getProvincesByRegion(normalizedRegionCode, apiBaseUrl)
      const provincesData = result?.data || []

      const provinceByCode = new Map<string, ProvinceApiModel>(
        provincesData
          .filter((p): p is ProvinceApiModel => Boolean(p && p.code))
          .map(p => [String(p.code).toUpperCase(), p])
      )

      for (const config of provinceConfigs) {
        const provinceCode = String(config?.code || '').toUpperCase()
        if (!provinceCode) continue

        const province = provinceByCode.get(provinceCode)
        if (!province) {
          if (import.meta.dev) console.warn(`No boundary data found for province: ${provinceCode}`)
          continue
        }

        if (!province.geometry) {
          if (import.meta.dev) console.warn(`Province ${provinceCode} has no geometry data`)
          continue
        }

        const feature = provinceService.toGeoJSONFeature(province)
        if (!feature) continue

        const layer = addProvinceToMap(L, map, feature, config)
        overallBounds = overallBounds ? overallBounds.extend(layer.getBounds()) : layer.getBounds()
        loadedProvinces.value.push(provinceCode)
      }

      if (overallBounds && (options?.fitBounds ?? true)) {
        map.fitBounds(overallBounds, { padding: options?.padding ?? [20, 20] })
      }
    } catch (err) {
      error.value = err instanceof Error ? err.message : String(err)
      console.error('Error loading province boundaries by region:', err)
    } finally {
      loading.value = false
    }
  }

  /**
   * Add a single province boundary to the map
   * @param {Object} L - Leaflet instance
   * @param {Object} map - Leaflet map instance
   * @param {Object} feature - GeoJSON feature
   * @param {Object} config - Province configuration (name, color)
   */
  function addProvinceToMap(
    L: LeafletNamespace,
    map: LeafletMap,
    feature: GeoJSONFeature,
    config: ProvinceConfig
  ): LeafletGeoJSON {
    const layer = L.geoJSON(feature, {
      style: {
        color: config.color,
        fillColor: config.color,
        fillOpacity: 0.5,
        weight: 2
      },
      onEachFeature: (_feature: GeoJSONFeature, layer: Layer) => {
        const popupContent = createPopupContent(config.name, feature.properties)
        layer.bindPopup(popupContent)
        layer.bindTooltip(config.name, {
          permanent: false,
          direction: 'center'
        })
      }
    })

    layer.addTo(map)
    return layer
  }

  /**
   * Create popup HTML content for a province
   * @param {string} name - Province name
   * @param {Object} properties - GeoJSON properties
   * @returns {string}
   */
  function createPopupContent(name: string, properties?: Record<string, unknown> | null): string {
    const displayName = properties?.display_name || properties?.name || name
    return `
      <div style="min-width: 150px;">
        <h3 style="margin: 0 0 8px 0; font-size: 16px;">${name}</h3>
        <p style="margin: 0; font-size: 12px; color: #666;">
          ${String(displayName)}
        </p>
      </div>
    `
  }

  /**
   * Clear all province boundaries from the map
   * @param {Object} L - Leaflet instance
   * @param {Object} map - Leaflet map instance
   */
  function clearProvinceBoundaries(L: LeafletNamespace, map: LeafletMap) {
    if (!map) return

    map.eachLayer((layer: Layer) => {
      if (layer instanceof L.Polygon || layer instanceof L.GeoJSON) {
        map.removeLayer(layer)
      }
    })

    loadedProvinces.value = []
  }

  /**
   * Utility function to add delay
   * @param {number} ms - Milliseconds to delay
   * @returns {Promise}
   */
  function delay(ms: number): Promise<void> {
    return new Promise<void>(resolve => setTimeout(resolve, ms))
  }

  /**
   * Fetch all provinces for a region
   * @param {string} regionCode - Region code
   * @returns {Promise<Array>}
   */
  async function fetchProvincesByRegion(regionCode: string): Promise<ProvinceApiModel[]> {
    try {
      // Pass apiBaseUrl to service
      const result = await provinceService.getProvincesByRegion(regionCode, apiBaseUrl)
      return result.data || []
    } catch (err) {
      console.error(`Failed to fetch provinces for region ${regionCode}:`, err)
      return []
    }
  }

  return {
    // State
    loading,
    error,
    loadedProvinces,

    // Methods
    fetchProvinceBoundary,
    loadProvinceBoundaries,
    loadProvinceBoundariesByRegion,
    addProvinceToMap,
    clearProvinceBoundaries,
    fetchProvincesByRegion
  }
}
