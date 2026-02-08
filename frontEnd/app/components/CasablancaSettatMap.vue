<template>
  <div
    class="map-container"
    :style="{ height: resolvedHeight }"
  >
    <ClientOnly>
      <div
        :id="resolvedMapId"
        class="map"
      />

      <div
        v-if="loading"
        class="loading"
      >
        <div class="spinner" />
        <p>Loading province boundaries...</p>
      </div>

      <div
        v-if="error"
        class="error-message"
      >
        <p>⚠️ {{ error }}</p>
      </div>

      <div
        v-if="props.showControls"
        class="controls"
      >
        <button
          class="reload-btn"
          :disabled="loading"
          @click="handleReloadBoundaries"
        >
          {{ loading ? 'Loading...' : 'Reload Boundaries' }}
        </button>
      </div>
    </ClientOnly>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, useId, watch } from 'vue'
import type { Map as LeafletMap } from 'leaflet'
import { useProvinceMap } from '~/composables/useProvinceMap'
import type { LeafletNamespace } from '~/types/models/leaflet'
import type { GeoJSONPolygon } from '~/types/models/geojson'
import type { MapMarker } from '~/types/models/map'
import type { ProvinceConfig } from '~/types/models/province'

export type CasablancaSettatMapMarker = MapMarker

type MapBoundsPayload = {
  southWest: { lat: number, lng: number }
  northEast: { lat: number, lng: number }
}

const props = withDefaults(defineProps<{
  height?: number | string
  mapId?: string
  zoom?: number
  showLegend?: boolean
  showControls?: boolean
  showZoomControl?: boolean
  scrollWheelZoom?: boolean
  interactiveLegend?: boolean
  fitToRegion?: boolean
  markers?: CasablancaSettatMapMarker[]
  fitToMarkers?: boolean
  markerColor?: string
  markerSize?: number
  selectedMarkerId?: string | null
  selectedMarkerColor?: string
  selectedGeojsonPolygon?: GeoJSONPolygon | null
  selectedGeojsonPolygonColor?: string
  fitToSelectedGeojsonPolygon?: boolean
}>(), {
  height: 600,
  zoom: 9,
  showLegend: true,
  showControls: true,
  showZoomControl: true,
  scrollWheelZoom: true,
  interactiveLegend: false,
  fitToRegion: true,
  markers: () => [],
  fitToMarkers: false,
  markerColor: '#f59e0b',
  markerSize: 32,
  selectedMarkerId: null,
  selectedMarkerColor: '#2563eb',
  selectedGeojsonPolygon: null,
  selectedGeojsonPolygonColor: '#2563eb',
  fitToSelectedGeojsonPolygon: false
})

const emit = defineEmits<{
  (e: 'select-marker', id: string): void
  (e: 'moved', bounds: MapBoundsPayload): void
}>()

const internalMapId = useId()
const resolvedMapId = computed(() => props.mapId || `map-${internalMapId}`)

const resolvedHeight = computed(() => {
  if (typeof props.height === 'number') return `${props.height}px`
  return props.height
})

let map: LeafletMap | null = null
let L: LeafletNamespace | null = null
let markersLayer: ReturnType<LeafletNamespace['layerGroup']> | null = null
let selectedPolygonLayer: ReturnType<LeafletNamespace['geoJSON']> | null = null
let enabledProvinceCodes = new Set<string>()
let markerById = new Map<string, { marker: CasablancaSettatMapMarker, layer: unknown }>()
let moveEndHandler: (() => void) | null = null
let canEmitMoveEvents = false
let suppressMoveEvents = false

// Province configurations
const PROVINCES: ProvinceConfig[] = [
  { name: 'Casablanca', code: 'CAS', color: '#FF6B6B' },
  { name: 'Mohammedia', code: 'MOH', color: '#4ECDC4' },
  { name: 'Benslimane', code: 'BEN', color: '#B8E986' },
  { name: 'Settat', code: 'SET', color: '#95E1D3' },
  { name: 'El Jadida', code: 'JDI', color: '#F38181' },
  { name: 'Berrechid', code: 'BER', color: '#AA96DA' },
  { name: 'Mediouna', code: 'MED', color: '#FFD93D' },
  { name: 'Nouaceur', code: 'NOU', color: '#6BCB77' },
  { name: 'Sidi Bennour', code: 'SBN', color: '#4D96FF' }
]

const REGION_CODE = 'CS'

// Use the composable
const {
  loading,
  error,
  loadProvinceBoundariesByRegion,
  clearProvinceBoundaries
} = useProvinceMap(PROVINCES)

function createMarkerPopup(leaflet: LeafletNamespace, marker: CasablancaSettatMapMarker) {
  const root = leaflet.DomUtil.create('div', 'marker-popup')

  if (marker.imageUrl) {
    const image = leaflet.DomUtil.create('img', 'marker-popup__image', root) as HTMLImageElement
    image.src = marker.imageUrl
    image.alt = marker.title || 'Listing image'
    image.loading = 'lazy'
    image.decoding = 'async'
  }

  if (marker.title) {
    const title = leaflet.DomUtil.create('p', 'marker-popup__title', root)
    title.textContent = marker.title
  }

  if (marker.subtitle) {
    const subtitle = leaflet.DomUtil.create('p', 'marker-popup__subtitle', root)
    subtitle.textContent = marker.subtitle
  }

  if (marker.href) {
    const link = leaflet.DomUtil.create('a', 'marker-popup__link', root)
    link.textContent = 'View details'
    link.href = marker.href
  }

  return root
}

function createPinIcon(leaflet: LeafletNamespace, color: string, size: number) {
  const safeColor = String(color || '#f59e0b')
  const resolvedSize = Number.isFinite(size) && size > 0 ? size : 32
  const svg = `
    <svg
      width="${resolvedSize}"
      height="${resolvedSize}"
      viewBox="0 0 24 24"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      aria-hidden="true"
      style="color: ${safeColor}"
    >
      <path
        d="M12 2C8.134 2 5 5.134 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.866-3.134-7-7-7Z"
        fill="currentColor"
      />
      <path
        d="M12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
        fill="#ffffff"
        fill-opacity="0.95"
      />
    </svg>
  `

  return leaflet.divIcon({
    className: 'gps-marker',
    html: svg,
    iconSize: [resolvedSize, resolvedSize],
    iconAnchor: [resolvedSize / 2, resolvedSize],
    popupAnchor: [0, -resolvedSize]
  })
}

function escapeHtml(value: string) {
  return value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll('\'', '&#039;')
}

function createPriceIcon(leaflet: LeafletNamespace, label: string, color: string, selected: boolean) {
  const safeColor = String(color || '#f59e0b')
  const safeLabel = escapeHtml(String(label || '').trim())
  const width = Math.min(220, Math.max(84, safeLabel.length * 8.5 + 28))
  const height = 36

  return leaflet.divIcon({
    className: 'price-marker',
    html: `<div class="price-marker__bubble" data-selected="${selected ? 'true' : 'false'}" style="--marker-color:${safeColor}">${safeLabel}</div>`,
    iconSize: [width, height],
    iconAnchor: [width / 2, height],
    popupAnchor: [0, -height]
  })
}

function isGeoJSONPolygon(value: unknown): value is GeoJSONPolygon {
  if (!value || typeof value !== 'object') return false
  const maybe = value as { type?: unknown, coordinates?: unknown }
  if (maybe.type !== 'Polygon') return false
  if (!Array.isArray(maybe.coordinates) || maybe.coordinates.length === 0) return false
  const ring = maybe.coordinates[0]
  if (!Array.isArray(ring) || ring.length < 4) return false

  return ring.every((pos) => {
    if (!Array.isArray(pos) || pos.length < 2) return false
    const lng = Number(pos[0])
    const lat = Number(pos[1])
    return Number.isFinite(lng) && Number.isFinite(lat)
  })
}

function clearSelectedPolygon() {
  if (!map || !L) {
    selectedPolygonLayer = null
    return
  }

  if (selectedPolygonLayer) {
    try {
      selectedPolygonLayer.remove()
    } catch {
      // no-op
    }
    selectedPolygonLayer = null
  }
}

function syncSelectedPolygon() {
  if (!map || !L) return

  clearSelectedPolygon()

  const polygon = props.selectedGeojsonPolygon
  if (!polygon) return
  if (!isGeoJSONPolygon(polygon)) return

  const color = props.selectedGeojsonPolygonColor || props.selectedMarkerColor || '#2563eb'

  selectedPolygonLayer = L.geoJSON(polygon, {
    style: {
      color,
      weight: 3,
      opacity: 0.9,
      fillColor: color,
      fillOpacity: 0.18
    }
  }).addTo(map)

  if (!props.fitToSelectedGeojsonPolygon) return

  const bounds = (selectedPolygonLayer as unknown as { getBounds?: () => { isValid?: () => boolean } }).getBounds?.()
  const isValid = bounds && typeof bounds.isValid === 'function' ? bounds.isValid() : !!bounds
  if (!isValid) return

  suppressMoveEvents = true
  map.once('moveend', () => {
    suppressMoveEvents = false
  })

  map.fitBounds(bounds as unknown as never, {
    padding: [30, 30],
    maxZoom: 16
  })
}

function mapBoundsPayload() {
  if (!map) return null
  const bounds = map.getBounds()
  const sw = bounds.getSouthWest()
  const ne = bounds.getNorthEast()
  return {
    southWest: { lat: sw.lat, lng: sw.lng },
    northEast: { lat: ne.lat, lng: ne.lng }
  } satisfies MapBoundsPayload
}

function syncMarkers() {
  if (!map || !L) return

  if (!markersLayer) {
    markersLayer = L.layerGroup().addTo(map)
  } else {
    markersLayer.clearLayers()
  }

  markerById.clear()

  const valid = (props.markers || []).filter(m => Number.isFinite(m.lat) && Number.isFinite(m.lng))

  valid.forEach((marker) => {
    const isSelected = !!props.selectedMarkerId && marker.id === props.selectedMarkerId
    const color = isSelected ? (props.selectedMarkerColor || props.markerColor) : props.markerColor
    const size = isSelected ? (Number(props.markerSize) + 4) : props.markerSize

    const icon = marker.label
      ? createPriceIcon(L!, marker.label, color, isSelected)
      : createPinIcon(L!, color, size)

    const layer = L!.marker([marker.lat, marker.lng], {
      icon,
      title: marker.title || undefined,
      zIndexOffset: isSelected ? 1000 : 0
    })

    if (marker.title || marker.subtitle || marker.href) {
      layer.bindPopup(createMarkerPopup(L!, marker))
    }

    layer.on('click', () => {
      emit('select-marker', marker.id)
    })

    layer.addTo(markersLayer!)
    markerById.set(marker.id, { marker, layer })
  })

  if (props.fitToMarkers && valid.length) {
    const bounds = L.latLngBounds(valid.map(m => [m.lat, m.lng] as [number, number]))
    map.fitBounds(bounds, {
      padding: [40, 40],
      maxZoom: 14
    })
  }
}

function focusMarker(id: string) {
  if (!map || !L) return
  const entry = markerById.get(id)
  if (!entry) return

  const marker = entry.marker
  const layer = entry.layer as { openPopup?: () => void } | undefined

  suppressMoveEvents = true
  map.once('moveend', () => {
    suppressMoveEvents = false
  })

  map.flyTo([marker.lat, marker.lng], Math.max(map.getZoom(), 13), {
    animate: true,
    duration: 0.55
  })

  layer?.openPopup?.()
}

function resolveLeafletModule(mod: unknown): LeafletNamespace {
  if (mod && typeof mod === 'object' && 'default' in mod) {
    return (mod as { default: LeafletNamespace }).default
  }

  return mod as LeafletNamespace
}

/**
 * Initialize the Leaflet map
 */
function initializeMap(leaflet: LeafletNamespace): LeafletMap {
  L = leaflet
  enabledProvinceCodes = new Set(PROVINCES.map(p => p.code))

  // Create map centered on Casablanca-Settat region
  map = leaflet
    .map(resolvedMapId.value, { zoomControl: props.showZoomControl, scrollWheelZoom: props.scrollWheelZoom })
    .setView([33.2316, -7.5389], props.zoom)

  // Add OpenStreetMap tiles
  leaflet.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 20
  }).addTo(map)

  // Add legend
  if (props.showLegend) {
    addLegend(leaflet, map)
  }

  moveEndHandler = () => {
    if (!canEmitMoveEvents) return
    if (suppressMoveEvents) return
    const payload = mapBoundsPayload()
    if (payload) emit('moved', payload)
  }
  map.on('moveend', moveEndHandler)

  return map
}

/**
 * Add legend control to the map
 */
function addLegend(leaflet: LeafletNamespace, mapInstance: LeafletMap) {
  const legend = new leaflet.Control({ position: 'bottomright' })

  legend.onAdd = function (_map: LeafletMap) {
    const div = leaflet.DomUtil.create('div', 'legend')
    div.innerHTML = '<h4>Provinces</h4>'

    if (!props.interactiveLegend) {
      PROVINCES.forEach((province) => {
        div.innerHTML += `
          <div class="legend-item">
            <span class="legend-color" style="background-color: ${province.color}"></span>
            <span>${province.name}</span>
          </div>
        `
      })

      return div
    }

    const list = leaflet.DomUtil.create('div', 'legend-list', div)

    PROVINCES.forEach((province) => {
      const item = leaflet.DomUtil.create('button', 'legend-item legend-item--interactive', list) as HTMLButtonElement
      item.type = 'button'
      item.setAttribute('aria-pressed', enabledProvinceCodes.has(province.code) ? 'true' : 'false')
      item.dataset.provinceCode = province.code
      item.innerHTML = `
        <span class="legend-color" style="background-color: ${province.color}"></span>
        <span>${province.name}</span>
      `

      leaflet.DomEvent.disableClickPropagation(item)
      leaflet.DomEvent.on(item, 'click', () => {
        const code = String(item.dataset.provinceCode || '').trim()
        if (!code) return

        if (enabledProvinceCodes.has(code)) {
          enabledProvinceCodes.delete(code)
        } else {
          enabledProvinceCodes.add(code)
        }

        item.setAttribute('aria-pressed', enabledProvinceCodes.has(code) ? 'true' : 'false')
        void reloadActiveProvinces({ fitBounds: false })
      })
    })

    return div
  }

  legend.addTo(mapInstance)
}

function activeProvinceConfigs() {
  if (!props.interactiveLegend) return PROVINCES
  return PROVINCES.filter(p => enabledProvinceCodes.has(p.code))
}

async function reloadActiveProvinces(options?: { fitBounds?: boolean }) {
  if (!map || !L) return

  clearProvinceBoundaries(L, map)
  const active = activeProvinceConfigs()
  if (!active.length) return

  await loadProvinceBoundariesByRegion(L, map, REGION_CODE, active, {
    fitBounds: options?.fitBounds ?? props.fitToRegion
  })
  syncMarkers()
  syncSelectedPolygon()
}

/**
 * Handle reload boundaries button click
 */
async function handleReloadBoundaries() {
  if (!map || !L) return

  await reloadActiveProvinces({ fitBounds: props.fitToRegion })
}

/**
 * Component mounted hook
 */
onMounted(() => {
  if (import.meta.client) {
    import('leaflet').then((LeafletModule) => {
      const leaflet = resolveLeafletModule(LeafletModule)
      const mapInstance = initializeMap(leaflet)
      requestAnimationFrame(() => {
        mapInstance.invalidateSize()
      })
      syncMarkers()
      loadProvinceBoundariesByRegion(leaflet, mapInstance, REGION_CODE, activeProvinceConfigs(), { fitBounds: props.fitToRegion })
        .finally(() => {
          canEmitMoveEvents = true
          syncMarkers()
          syncSelectedPolygon()
          requestAnimationFrame(() => {
            map?.invalidateSize()
          })
          if (props.selectedMarkerId) focusMarker(props.selectedMarkerId)
        })
    })
  }
})

watch(
  () => [props.markers, props.fitToMarkers, props.markerColor, props.markerSize, props.selectedMarkerId, props.selectedMarkerColor],
  () => {
    syncMarkers()
  },
  { deep: true }
)

watch(
  () => [props.selectedGeojsonPolygon, props.selectedGeojsonPolygonColor, props.fitToSelectedGeojsonPolygon],
  () => {
    syncSelectedPolygon()
  }
)

watch(
  () => props.selectedMarkerId,
  (id) => {
    if (!id) return
    focusMarker(id)
  }
)

onBeforeUnmount(() => {
  clearSelectedPolygon()
  markersLayer?.clearLayers()
  markersLayer = null
  markerById.clear()
  if (map && moveEndHandler) {
    map.off('moveend', moveEndHandler)
  }
  moveEndHandler = null
  map?.remove()
  map = null
  L = null
})
</script>

<style scoped>
.map-container {
  width: 100%;
  position: relative;
}

.map {
  width: 100%;
  height: 100%;
  z-index: 0;
}

.loading {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: white;
  padding: 30px 40px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  text-align: center;
  z-index: 1000;
}

.spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto 15px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.controls {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 1000;
}

.reload-btn {
  background: white;
  border: 2px solid rgba(0, 0, 0, 0.2);
  border-radius: 4px;
  padding: 8px 16px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.reload-btn:hover:not(:disabled) {
  background: #f0f0f0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.reload-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error-message {
  position: absolute;
  top: 60px;
  right: 10px;
  background: #fee;
  border: 1px solid #fcc;
  border-radius: 4px;
  padding: 12px 16px;
  z-index: 1000;
  max-width: 300px;
}

.error-message p {
  margin: 0;
  color: #c33;
  font-size: 14px;
}

:deep(.legend) {
  background: white;
  padding: 10px;
  border-radius: 5px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

:deep(.legend h4) {
  margin: 0 0 10px 0;
  font-size: 14px;
  font-weight: bold;
}

:deep(.legend-item) {
  display: flex;
  align-items: center;
  margin-bottom: 5px;
  font-size: 12px;
}

:deep(.legend-item--interactive) {
  width: 100%;
  cursor: pointer;
  background: transparent;
  border: 0;
  padding: 0;
  text-align: left;
  opacity: 1;
  transition: opacity 150ms ease;
}

:deep(.legend-item--interactive[aria-pressed="false"]) {
  opacity: 0.45;
}

:deep(.legend-item--interactive:hover) {
  opacity: 1;
}

:deep(.legend-color) {
  width: 20px;
  height: 20px;
  margin-right: 8px;
  border: 1px solid #333;
  border-radius: 3px;
  flex-shrink: 0;
}

:deep(.marker-popup) {
  min-width: 160px;
  font-family: inherit;
}

:deep(.marker-popup__image) {
  display: block;
  width: 100%;
  height: 96px;
  object-fit: cover;
  border-radius: 10px;
  margin-bottom: 10px;
}

:deep(.marker-popup__title) {
  margin: 0;
  font-size: 14px;
  font-weight: 700;
}

:deep(.marker-popup__subtitle) {
  margin: 6px 0 0;
  font-size: 12px;
  color: #64748b;
}

:deep(.marker-popup__link) {
  display: inline-block;
  margin-top: 10px;
  font-size: 12px;
  font-weight: 600;
  color: #0f172a;
  text-decoration: underline;
}

:deep(.gps-marker) {
  background: transparent;
  border: 0;
}

:deep(.gps-marker svg) {
  display: block;
  filter: drop-shadow(0 6px 10px rgba(0, 0, 0, 0.25));
}

:deep(.price-marker) {
  background: transparent;
  border: 0;
}

:deep(.price-marker__bubble) {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  max-width: 220px;
  overflow: hidden;
  text-overflow: ellipsis;
  padding: 6px 12px;
  border-radius: 9999px;
  background: var(--marker-color, #0f172a);
  color: rgba(255, 255, 255, 0.98);
  font-weight: 800;
  font-size: 12px;
  letter-spacing: 0.01em;
  white-space: nowrap;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.25);
  position: relative;
}

:deep(.price-marker__bubble[data-selected="true"]) {
  transform: translateY(-1px) scale(1.06);
  box-shadow: 0 14px 30px rgba(15, 23, 42, 0.35);
  outline: 2px solid rgba(255, 255, 255, 0.75);
  outline-offset: -2px;
}

:deep(.price-marker__bubble::after) {
  content: '';
  position: absolute;
  left: 50%;
  bottom: -7px;
  transform: translateX(-50%);
  width: 0;
  height: 0;
  border-left: 7px solid transparent;
  border-right: 7px solid transparent;
  border-top: 8px solid var(--marker-color, #0f172a);
  filter: drop-shadow(0 6px 10px rgba(15, 23, 42, 0.25));
}
</style>

<style>
@import 'leaflet/dist/leaflet.css';
</style>
