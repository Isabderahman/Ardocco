<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { BackendListing, PublicListingsFilters } from '~/types/models/listing'
import type { MapMarker } from '~/types/models/map'

definePageMeta({
  title: 'Rechercher des terrains',
  layout: 'search'
})

type MapBounds = {
  southWest: { lat: number, lng: number }
  northEast: { lat: number, lng: number }
}

const router = useRouter()
const route = useRoute()

function queryValue(value: unknown): string {
  if (Array.isArray(value)) return String(value[0] || '')
  if (typeof value === 'string') return value
  return ''
}

function parseCsv(value: unknown): string[] {
  const raw = queryValue(value)
  if (!raw) return []
  return raw.split(',').map(v => v.trim()).filter(Boolean)
}

function parseBounds(value: unknown): MapBounds | null {
  const raw = queryValue(value).trim()
  if (!raw) return null
  const parts = raw.split(',').map(v => Number(v.trim()))
  const swLat = parts[0] ?? Number.NaN
  const swLng = parts[1] ?? Number.NaN
  const neLat = parts[2] ?? Number.NaN
  const neLng = parts[3] ?? Number.NaN
  if (![swLat, swLng, neLat, neLng].every(Number.isFinite)) return null
  return {
    southWest: { lat: swLat, lng: swLng },
    northEast: { lat: neLat, lng: neLng }
  }
}

function serializeBounds(bounds: MapBounds): string {
  return [
    bounds.southWest.lat,
    bounds.southWest.lng,
    bounds.northEast.lat,
    bounds.northEast.lng
  ].map(v => Number(v).toFixed(6)).join(',')
}

function normalizeQuery(q: Record<string, unknown>): Record<string, string> {
  const out: Record<string, string> = {}
  Object.entries(q).forEach(([key, value]) => {
    const v = queryValue(value)
    if (v) out[key] = v
  })
  return out
}

function stableStringify(obj: Record<string, string>): string {
  const keys = Object.keys(obj).sort()
  const ordered: Record<string, string> = {}
  keys.forEach((key) => {
    ordered[key] = obj[key] ?? ''
  })
  return JSON.stringify(ordered)
}

const filters = ref<PublicListingsFilters>({
  q: queryValue(route.query.q),
  type_terrain: queryValue(route.query.type_terrain),
  prix_min: queryValue(route.query.prix_min),
  prix_max: queryValue(route.query.prix_max),
  superficie_min: queryValue(route.query.superficie_min),
  superficie_max: queryValue(route.query.superficie_max),
  rentabilite_min: queryValue(route.query.rentabilite_min),
  sort: queryValue(route.query.sort) || 'recent'
})

const selectedProvinces = ref<string[]>(parseCsv(route.query.provinces))
const boundsFilter = ref<MapBounds | null>(parseBounds(route.query.bounds))

const { data, listings, isAuthenticated, pending, error, refresh } = usePublicListings(filters)

const terrainTypes = [
  { label: 'Résidentiel', value: 'residentiel' },
  { label: 'Commercial', value: 'commercial' },
  { label: 'Industriel', value: 'industriel' },
  { label: 'Agricole', value: 'agricole' },
  { label: 'Mixte', value: 'mixte' }
]

const sortOptions = [
  { label: 'Plus récents', value: 'recent' },
  { label: 'Prix croissant', value: 'price_asc' },
  { label: 'Prix décroissant', value: 'price_desc' },
  { label: 'Surface croissante', value: 'area_asc' },
  { label: 'Surface décroissante', value: 'area_desc' }
]

const provincesLegend = [
  { name: 'Casablanca', color: '#1A7BFD' },
  { name: 'Nouaceur', color: '#60a5fa' },
  { name: 'Mohammedia', color: '#94a3b8' },
  { name: 'Mediouna', color: '#64748b' },
  { name: 'Berrechid', color: '#94a3b8' }
]

const provinceOptions = computed(() => provincesLegend.map(p => ({ label: p.name, value: p.name })))

const resetFilters = () => {
  filters.value = {
    q: '',
    type_terrain: '',
    prix_min: '',
    prix_max: '',
    superficie_min: '',
    superficie_max: '',
    rentabilite_min: '',
    sort: 'recent'
  }
  selectedProvinces.value = []
  boundsFilter.value = null
  pendingBounds.value = null
}

const formatPrice = (price: number | string | null | undefined) => {
  const numPrice = Number(price)
  if (!Number.isFinite(numPrice) || numPrice <= 0) return '—'
  return new Intl.NumberFormat('fr-MA', {
    style: 'currency',
    currency: 'MAD',
    maximumFractionDigits: 0
  }).format(numPrice)
}

const formatPriceMDHS = (price: number | string | null | undefined) => {
  const numPrice = Number(price)
  if (!Number.isFinite(numPrice) || numPrice <= 0) return '—'
  const millions = numPrice / 1_000_000
  if (millions >= 1) {
    return `${millions.toFixed(millions % 1 === 0 ? 0 : 1)} MDHS`
  }
  return `${(numPrice / 1000).toFixed(0)}K DHS`
}

function numeric(value: unknown): number | null {
  const n = Number(value)
  return Number.isFinite(n) ? n : null
}

function listingDate(listing: BackendListing): number {
  const raw = listing.published_at || listing.created_at || listing.updated_at
  const ms = raw ? Date.parse(raw) : NaN
  return Number.isFinite(ms) ? ms : 0
}

function withinBounds(listing: BackendListing, bounds: MapBounds): boolean {
  const lat = numeric(listing.latitude)
  const lng = numeric(listing.longitude)
  if (lat == null || lng == null) return false
  return lat >= bounds.southWest.lat
    && lat <= bounds.northEast.lat
    && lng >= bounds.southWest.lng
    && lng <= bounds.northEast.lng
}

const visibleListings = computed<BackendListing[]>(() => {
  let items = listings.value.slice()

  if (selectedProvinces.value.length) {
    items = items.filter((listing) => {
      const province = listing.commune?.province?.name_fr || ''
      return selectedProvinces.value.includes(province)
    })
  }

  if (boundsFilter.value) {
    items = items.filter(listing => withinBounds(listing, boundsFilter.value!))
  }

  const sort = filters.value.sort
  if (sort === 'price_asc') {
    items.sort((a, b) => (numeric(a.prix_demande) ?? Number.POSITIVE_INFINITY) - (numeric(b.prix_demande) ?? Number.POSITIVE_INFINITY))
  } else if (sort === 'price_desc') {
    items.sort((a, b) => (numeric(b.prix_demande) ?? Number.NEGATIVE_INFINITY) - (numeric(a.prix_demande) ?? Number.NEGATIVE_INFINITY))
  } else if (sort === 'area_asc') {
    items.sort((a, b) => (numeric(a.superficie) ?? Number.POSITIVE_INFINITY) - (numeric(b.superficie) ?? Number.POSITIVE_INFINITY))
  } else if (sort === 'area_desc') {
    items.sort((a, b) => (numeric(b.superficie) ?? Number.NEGATIVE_INFINITY) - (numeric(a.superficie) ?? Number.NEGATIVE_INFINITY))
  } else if (sort === 'recent') {
    items.sort((a, b) => listingDate(b) - listingDate(a))
  }

  return items
})

const resultsCount = computed(() => visibleListings.value.length)
const totalCount = computed(() => data.value?.data?.total ?? resultsCount.value)

const activeFilterCount = computed(() => {
  let count = 0
  if (filters.value.q.trim()) count += 1
  if (filters.value.type_terrain) count += 1
  if (filters.value.prix_min || filters.value.prix_max) count += 1
  if (filters.value.superficie_min || filters.value.superficie_max) count += 1
  if (filters.value.rentabilite_min) count += 1
  if (selectedProvinces.value.length) count += 1
  if (boundsFilter.value) count += 1
  return count
})

const selectedListingId = ref<string | null>(null)
watch(visibleListings, () => {
  if (!selectedListingId.value) return
  const exists = visibleListings.value.some(l => l.id === selectedListingId.value)
  if (!exists) selectedListingId.value = null
})

const selectedListingPolygon = computed(() => {
  const id = selectedListingId.value
  if (!id) return null
  const listing = visibleListings.value.find(l => l.id === id)
  return listing?.geojson_polygon ?? null
})

function listingLocation(listing: BackendListing) {
  return listing.quartier
    || listing.commune?.name_fr
    || listing.commune?.province?.name_fr
    || 'Non spécifié'
}

const mapMarkers = computed<MapMarker[]>(() => {
  return visibleListings.value
    .map<MapMarker | null>((listing) => {
      const lat = numeric(listing.latitude)
      const lng = numeric(listing.longitude)
      if (lat == null || lng == null) return null
      return {
        id: listing.id,
        lat,
        lng,
        title: listing.title,
        subtitle: listingLocation(listing),
        label: formatPriceMDHS(listing.prix_demande),
        href: `/terrains/${listing.id}`
      }
    })
    .filter((m): m is MapMarker => m !== null)
})

const pendingBounds = ref<MapBounds | null>(null)

const showSearchInArea = computed(() => {
  if (!pendingBounds.value) return false
  if (!boundsFilter.value) return true
  return serializeBounds(pendingBounds.value) !== serializeBounds(boundsFilter.value)
})

function onMapMoved(bounds: MapBounds) {
  pendingBounds.value = bounds
}

function applySearchInArea() {
  if (!pendingBounds.value) return
  boundsFilter.value = pendingBounds.value
}

function resetArea() {
  boundsFilter.value = null
  pendingBounds.value = null
}

function buildQuery() {
  const query: Record<string, string> = {}

  const q = filters.value.q.trim()
  if (q) query.q = q
  if (filters.value.type_terrain) query.type_terrain = filters.value.type_terrain
  if (filters.value.prix_min) query.prix_min = String(filters.value.prix_min)
  if (filters.value.prix_max) query.prix_max = String(filters.value.prix_max)
  if (filters.value.superficie_min) query.superficie_min = String(filters.value.superficie_min)
  if (filters.value.superficie_max) query.superficie_max = String(filters.value.superficie_max)
  if (filters.value.rentabilite_min) query.rentabilite_min = String(filters.value.rentabilite_min)
  if (filters.value.sort && filters.value.sort !== 'recent') query.sort = filters.value.sort

  if (selectedProvinces.value.length) query.provinces = selectedProvinces.value.join(',')
  if (boundsFilter.value) query.bounds = serializeBounds(boundsFilter.value)

  return query
}

const isSyncingFromRoute = ref(false)
const MANAGED_QUERY_KEYS = new Set([
  'view',
  'q',
  'type_terrain',
  'prix_min',
  'prix_max',
  'superficie_min',
  'superficie_max',
  'rentabilite_min',
  'sort',
  'provinces',
  'bounds'
])

function mergeManagedQuery(current: Record<string, string>, managed: Record<string, string>) {
  const merged: Record<string, string> = { ...current }
  MANAGED_QUERY_KEYS.forEach((key) => {
    delete merged[key]
  })
  Object.entries(managed).forEach(([key, value]) => {
    if (value) merged[key] = value
  })
  return merged
}

watch(
  () => route.query,
  (query) => {
    isSyncingFromRoute.value = true
    filters.value = {
      q: queryValue(query.q),
      type_terrain: queryValue(query.type_terrain),
      prix_min: queryValue(query.prix_min),
      prix_max: queryValue(query.prix_max),
      superficie_min: queryValue(query.superficie_min),
      superficie_max: queryValue(query.superficie_max),
      rentabilite_min: queryValue(query.rentabilite_min),
      sort: queryValue(query.sort) || 'recent'
    }
    selectedProvinces.value = parseCsv(query.provinces)
    boundsFilter.value = parseBounds(query.bounds)
    isSyncingFromRoute.value = false
  }
)

function syncQueryToUrl() {
  if (isSyncingFromRoute.value) return
  const managedQuery = buildQuery()
  const currentQuery = normalizeQuery(route.query as Record<string, unknown>)
  const nextQuery = mergeManagedQuery(currentQuery, managedQuery)
  if (stableStringify(currentQuery) === stableStringify(nextQuery)) return
  router.replace({ query: nextQuery })
}

watch(filters, syncQueryToUrl, { deep: true })
watch(selectedProvinces, syncQueryToUrl, { deep: true })
watch(boundsFilter, syncQueryToUrl)
</script>

<template>
  <main class="flex h-full min-h-0 flex-col bg-gray-50">
    <!-- Sub-header -->
    <div class="shrink-0 border-b border-default bg-default">
      <UContainer class="max-w-none py-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="min-w-0">
            <h1 class="text-xl font-semibold text-highlighted">
              Terrains disponibles
            </h1>
            <p class="mt-1 text-sm text-muted">
              {{ resultsCount }} résultat<span v-if="resultsCount > 1">s</span>
              <span v-if="totalCount > resultsCount">sur {{ totalCount }}</span>
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <div class="w-full sm:w-56">
              <label class="sr-only" for="terrain-sort">
                Trier par
              </label>
              <USelect
                id="terrain-sort"
                v-model="filters.sort"
                :items="sortOptions"
                size="md"
                class="w-full"
              />
            </div>
          </div>
        </div>

        <div class="mt-4">
          <ThemeAPublicListingsFiltersBar
            v-model="filters"
            v-model:provinces="selectedProvinces"
            :terrain-types="terrainTypes"
            :province-options="provinceOptions"
            :active-count="activeFilterCount"
            :disabled="pending"
            @reset="resetFilters"
          />
        </div>
      </UContainer>
    </div>

    <!-- Content -->
    <div class="flex-1 min-h-0">
      <UContainer class="max-w-none h-full min-h-0 py-4">
        <div class="flex h-full min-h-0 flex-col gap-4 lg:flex-row">
          <!-- Map -->
          <div class="relative h-[44vh] shrink-0 overflow-hidden rounded-2xl bg-default ring-1 ring-default lg:h-full lg:flex-1 lg:shrink lg:min-h-0">
            <ClientOnly>
              <CasablancaSettatMap
                height="100%"
                :markers="mapMarkers"
                :show-legend="false"
                :show-controls="false"
                :selected-marker-id="selectedListingId"
                :selected-geojson-polygon="selectedListingPolygon"
                :fit-to-selected-geojson-polygon="true"
                @select-marker="selectedListingId = $event"
                @moved="onMapMoved"
              />
            </ClientOnly>

            <ThemeAPublicListingsMapControls
              :show-search-in-area="showSearchInArea"
              :has-area-filter="!!boundsFilter"
              @search="applySearchInArea"
              @reset="resetArea"
            />

            <ThemeAPublicListingsLegend :items="provincesLegend" />
          </div>

          <!-- Results panel -->
          <ThemeAPublicListingsResultsPanel
            class="flex-1 min-h-0 lg:flex-none lg:w-[440px]"
            :listings="visibleListings"
            :pending="pending"
            :error="error"
            :selected-id="selectedListingId"
            :is-authenticated="isAuthenticated"
            :terrain-types="terrainTypes"
            @select="selectedListingId = $event"
            @retry="refresh()"
            @reset="resetFilters"
          />
        </div>
      </UContainer>
    </div>
  </main>
</template>
