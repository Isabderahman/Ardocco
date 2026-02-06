<script setup lang="ts">
import type { BackendResponse, LaravelPage } from '~/types/models/api'
import type { BackendListing, ListingRow } from '~/types/models/listing'
import type { MapMarker } from '~/types/models/map'
import type { TerrainType } from '~/types/enums/terrain'
import type { SellFilters } from '~/types/models/sell'

const route = useRoute()
const router = useRouter()

const perPage = 6
const { isFavorite, toggleFavorite } = useFavoriteListings()

const page = computed<number>({
  get: () => {
    const raw = typeof route.query.page === 'string' ? Number(route.query.page) : 1
    return Number.isFinite(raw) && raw > 0 ? raw : 1
  },
  set: (value) => {
    router.replace({
      query: {
        ...route.query,
        page: value > 1 ? String(value) : undefined
      }
    })
  }
})

function asNumber(value: unknown): number | undefined {
  if (typeof value === 'number' && Number.isFinite(value)) return value
  if (typeof value === 'string' && value.trim().length) {
    const parsed = Number(value)
    if (Number.isFinite(parsed)) return parsed
  }
  return undefined
}

const TERRAIN_TYPES: Array<{ value: TerrainType, label: string }> = [
  { value: 'residentiel', label: 'Residential' },
  { value: 'commercial', label: 'Commercial' },
  { value: 'industriel', label: 'Industrial' },
  { value: 'agricole', label: 'Agricultural' },
  { value: 'mixte', label: 'Mixed-use' }
]

function normalizeTerrainType(value: unknown): TerrainType | undefined {
  const raw = typeof value === 'string' ? value.trim() : ''
  if (!raw) return undefined
  if (TERRAIN_TYPES.some(t => t.value === raw)) return raw as TerrainType
  return undefined
}

function terrainTypeLabel(value: string | null | undefined): string | undefined {
  if (!value) return undefined
  return TERRAIN_TYPES.find(t => t.value === value)?.label
}

function formatMAD(value: unknown): string | undefined {
  const numeric = asNumber(value)
  if (numeric === undefined) return undefined

  return new Intl.NumberFormat('fr-MA', {
    style: 'currency',
    currency: 'MAD',
    maximumFractionDigits: 0
  }).format(numeric)
}

function formatMADCompact(value: unknown): string | undefined {
  const numeric = asNumber(value)
  if (numeric === undefined) return undefined

  return new Intl.NumberFormat('fr-MA', {
    style: 'currency',
    currency: 'MAD',
    notation: 'compact',
    compactDisplay: 'short',
    maximumFractionDigits: 1
  }).format(numeric)
}

function formatArea(value: unknown): string | undefined {
  const numeric = asNumber(value)
  if (numeric === undefined) return undefined

  const formatted = new Intl.NumberFormat('fr-MA', { maximumFractionDigits: 0 }).format(numeric)
  return `${formatted} m²`
}

const routeFilters = computed(() => ({
  q: (typeof route.query.q === 'string' ? route.query.q : '').trim(),
  typeTerrain: normalizeTerrainType(route.query.type_terrain),
  priceMin: asNumber(route.query.prix_min) ?? null,
  priceMax: asNumber(route.query.prix_max) ?? null,
  areaMin: asNumber(route.query.superficie_min) ?? null,
  areaMax: asNumber(route.query.superficie_max) ?? null
}))

const filters = reactive<SellFilters>({
  q: routeFilters.value.q,
  typeTerrain: routeFilters.value.typeTerrain,
  priceMin: routeFilters.value.priceMin,
  priceMax: routeFilters.value.priceMax,
  areaMin: routeFilters.value.areaMin,
  areaMax: routeFilters.value.areaMax
})

watch(
  routeFilters,
  (next) => {
    filters.q = next.q
    filters.typeTerrain = next.typeTerrain
    filters.priceMin = next.priceMin
    filters.priceMax = next.priceMax
    filters.areaMin = next.areaMin
    filters.areaMax = next.areaMax
  },
  { deep: true }
)

const activeFiltersCount = computed(() => {
  const f = routeFilters.value
  return [
    f.q,
    f.typeTerrain,
    f.priceMin,
    f.priceMax,
    f.areaMin,
    f.areaMax
  ].filter(v => v !== '' && v !== null && v !== undefined).length
})

const apiQuery = computed(() => ({
  page: page.value,
  per_page: perPage,
  q: routeFilters.value.q || undefined,
  type_terrain: routeFilters.value.typeTerrain || undefined,
  prix_min: routeFilters.value.priceMin ?? undefined,
  prix_max: routeFilters.value.priceMax ?? undefined,
  superficie_min: routeFilters.value.areaMin ?? undefined,
  superficie_max: routeFilters.value.areaMax ?? undefined
}))

const {
  data: response,
  pending,
  error
} = await useFetch<BackendResponse<LaravelPage<BackendListing>>>('/api/backend/listings', {
  query: apiQuery
})

const rows = computed<ListingRow[]>(() => {
  const items = response.value?.data?.data || []
  return items.map((l) => {
    const communeName = l.commune?.name_fr || undefined
    const provinceName = l.commune?.province?.name_fr || undefined
    const location = communeName && provinceName ? `${communeName}, ${provinceName}` : (communeName || provinceName)

    return {
      id: l.id,
      title: l.title,
      description: l.description || undefined,
      location,
      price: formatMAD(l.prix_demande),
      badge: terrainTypeLabel(l.type_terrain),
      status: l.status || undefined,
      typeLabel: terrainTypeLabel(l.type_terrain),
      area: formatArea(l.superficie),
      isExclusive: Boolean(l.is_exclusive),
      isUrgent: Boolean(l.is_urgent),
      lat: asNumber(l.latitude),
      lng: asNumber(l.longitude)
    }
  })
})

const markers = computed<MapMarker[]>(() => rows.value
  .filter(r => typeof r.lat === 'number' && typeof r.lng === 'number')
  .map(r => ({
    id: r.id,
    lat: r.lat as number,
    lng: r.lng as number,
    title: r.title,
    subtitle: [r.price, r.location].filter(Boolean).join(' · '),
    label: formatMADCompact(itemsById.value.get(r.id)?.prix_demande),
    href: `/sell/${r.id}`
  })))

const itemsById = computed(() => new Map(
  (response.value?.data?.data || [])
    .filter((l): l is BackendListing => Boolean(l?.id))
    .map(l => [l.id, l])
))

const searchSuggestions = computed(() => {
  const set = new Set<string>()

  ;[
    'Casablanca',
    'Settat',
    'El Jadida',
    'Berrechid',
    'Nouaceur'
  ].forEach(s => set.add(s))

  rows.value.forEach((row) => {
    if (row.location) set.add(row.location)
    set.add(row.title)
  })

  return Array.from(set).slice(0, 12)
})

const filtersOpen = ref(false)
const mobileSheetExpanded = ref(true)

function syncFiltersToRoute() {
  const f = routeFilters.value
  filters.q = f.q
  filters.typeTerrain = f.typeTerrain
  filters.priceMin = f.priceMin
  filters.priceMax = f.priceMax
  filters.areaMin = f.areaMin
  filters.areaMax = f.areaMax
}

async function applyFilters() {
  const q = filters.q.trim()

  await navigateTo(
    {
      path: '/sell',
      query: {
        ...route.query,
        q: q || undefined,
        type_terrain: filters.typeTerrain || undefined,
        prix_min: filters.priceMin ?? undefined,
        prix_max: filters.priceMax ?? undefined,
        superficie_min: filters.areaMin ?? undefined,
        superficie_max: filters.areaMax ?? undefined,
        page: undefined
      }
    },
    { replace: true }
  )
}

async function clearAllFilters() {
  syncFiltersToRoute()
  filters.q = ''
  filters.typeTerrain = undefined
  filters.priceMin = null
  filters.priceMax = null
  filters.areaMin = null
  filters.areaMax = null

  await applyFilters()
}
</script>

<template>
  <div class="relative lg:grid lg:grid-cols-[minmax(0,1fr)_480px]">
    <div class="h-[calc(100svh-var(--ui-header-height))] lg:sticky lg:top-[var(--ui-header-height)] lg:h-[calc(100svh-var(--ui-header-height))] lg:self-start">
      <CasablancaSettatMap
        map-id="sell-map"
        height="100%"
        :zoom="9"
        :show-controls="false"
        :show-legend="true"
        :interactive-legend="true"
        :fit-to-region="false"
        :markers="markers"
        :fit-to-markers="true"
        marker-color="var(--ui-color-primary-500)"
        :marker-size="44"
      />
    </div>

    <div class="hidden border-t border-default bg-default lg:block lg:border-s lg:border-t-0">
      <div class="sticky top-[var(--ui-header-height)] z-20 border-b border-default bg-default/90 p-4 backdrop-blur">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h1 class="text-lg font-semibold text-highlighted">
              Sell
            </h1>
            <p class="mt-1 text-xs text-muted">
              {{ response?.data?.total || 0 }} results
            </p>
          </div>

          <UButton
            label="Add Terrain"
            color="primary"
            size="sm"
            to="/terrains/new"
            class="rounded-full"
          />
        </div>

        <form
          class="mt-4 space-y-3"
          @submit.prevent="applyFilters"
        >
          <div class="flex items-center gap-2">
            <UInputMenu
              v-model="filters.q"
              :items="searchSuggestions"
              size="lg"
              variant="outline"
              placeholder="Search available terrains ..."
              leading-icon="i-lucide-search"
              create-item="always"
              class="flex-1"
            />

            <UButton
              type="submit"
              color="primary"
              size="lg"
              class="rounded-full"
              :loading="pending"
            >
              Search
            </UButton>

            <UButton
              type="button"
              color="neutral"
              variant="outline"
              size="lg"
              class="rounded-full"
              @click="filtersOpen = !filtersOpen"
            >
              <UIcon name="i-lucide-sliders-horizontal" class="size-5" />
              <span class="hidden sm:inline">Filters</span>
              <UBadge
                v-if="activeFiltersCount"
                color="primary"
                variant="solid"
                class="ml-1"
              >
                {{ activeFiltersCount }}
              </UBadge>
            </UButton>
          </div>

          <UCollapsible v-model:open="filtersOpen">
            <div class="grid gap-3 rounded-xl border border-default bg-default p-3 sm:grid-cols-2">
              <UFormField label="Type">
                <USelectMenu
                  v-model="filters.typeTerrain"
                  :items="TERRAIN_TYPES"
                  value-key="value"
                  label-key="label"
                  :search-input="false"
                  placeholder="Any type"
                />
              </UFormField>

              <div class="grid gap-3 sm:grid-cols-2 sm:col-span-2">
                <UFormField label="Price min (MAD)">
                  <UInputNumber
                    v-model="filters.priceMin"
                    :min="0"
                    placeholder="0"
                    :format-options="{ maximumFractionDigits: 0 }"
                  />
                </UFormField>
                <UFormField label="Price max (MAD)">
                  <UInputNumber
                    v-model="filters.priceMax"
                    :min="0"
                    placeholder="Any"
                    :format-options="{ maximumFractionDigits: 0 }"
                  />
                </UFormField>
              </div>

              <div class="grid gap-3 sm:grid-cols-2 sm:col-span-2">
                <UFormField label="Area min (m²)">
                  <UInputNumber
                    v-model="filters.areaMin"
                    :min="0"
                    placeholder="0"
                    :format-options="{ maximumFractionDigits: 0 }"
                  />
                </UFormField>
                <UFormField label="Area max (m²)">
                  <UInputNumber
                    v-model="filters.areaMax"
                    :min="0"
                    placeholder="Any"
                    :format-options="{ maximumFractionDigits: 0 }"
                  />
                </UFormField>
              </div>

              <div class="flex flex-wrap items-center justify-between gap-2 sm:col-span-2">
                <UButton
                  type="button"
                  color="neutral"
                  variant="ghost"
                  size="sm"
                  class="rounded-full"
                  @click="clearAllFilters"
                >
                  Clear all
                </UButton>
                <UButton
                  type="submit"
                  color="primary"
                  size="sm"
                  class="rounded-full"
                  :loading="pending"
                >
                  Apply filters
                </UButton>
              </div>
            </div>
          </UCollapsible>
        </form>
      </div>

      <div class="space-y-4 p-4">
        <UAlert
          v-if="error"
          color="error"
          title="Unable to load listings"
          variant="soft"
          :description="error.message"
        />

        <UAlert
          v-else-if="pending"
          color="neutral"
          title="Loading"
          variant="soft"
          description="Fetching listings..."
        />

        <ThemeAListingRow
          v-for="l in rows"
          :key="l.id"
          :title="l.title"
          :description="l.description"
          :location="l.location"
          :price="l.price"
          :status="l.status"
          :type-label="l.typeLabel"
          :area="l.area"
          :is-exclusive="l.isExclusive"
          :is-urgent="l.isUrgent"
          :is-favorite="isFavorite(l.id)"
          @toggle-favorite="toggleFavorite(l.id)"
          :to="`/sell/${l.id}`"
        />
      </div>

      <div class="border-t border-default p-4">
        <UPagination
          v-model:page="page"
          :total="response?.data?.total || 0"
          :page-count="perPage"
        />
      </div>
    </div>

    <!-- Mobile bottom sheet -->
    <div class="lg:hidden fixed inset-x-0 bottom-0 z-40 px-2 pb-[calc(env(safe-area-inset-bottom)+0.5rem)]">
      <div
        class="mx-auto flex w-full max-w-2xl flex-col overflow-hidden rounded-t-3xl border border-default bg-default/95 shadow-2xl backdrop-blur"
        :class="mobileSheetExpanded ? 'h-[72svh]' : 'h-20'"
      >
        <button
          type="button"
          class="flex w-full flex-col items-center gap-2 border-b border-default py-2"
          @click="mobileSheetExpanded = !mobileSheetExpanded"
        >
          <span class="h-1.5 w-12 rounded-full bg-muted/70" />
          <div class="flex w-full items-center justify-between px-4">
            <span class="text-sm font-semibold text-highlighted">Listings</span>
            <span class="text-xs text-muted">{{ response?.data?.total || 0 }} results</span>
          </div>
        </button>

        <div
          v-show="mobileSheetExpanded"
          class="flex min-h-0 flex-1 flex-col"
        >
          <div class="border-b border-default bg-default/90 p-4 backdrop-blur">
            <form
              class="space-y-3"
              @submit.prevent="applyFilters"
            >
              <div class="flex items-center gap-2">
                <UInputMenu
                  v-model="filters.q"
                  :items="searchSuggestions"
                  size="lg"
                  variant="outline"
                  placeholder="Search available terrains ..."
                  leading-icon="i-lucide-search"
                  create-item="always"
                  class="flex-1"
                />

                <UButton
                  type="submit"
                  color="primary"
                  size="lg"
                  class="rounded-full"
                  :loading="pending"
                >
                  Search
                </UButton>

                <UButton
                  type="button"
                  color="neutral"
                  variant="outline"
                  size="lg"
                  class="rounded-full"
                  @click="filtersOpen = !filtersOpen"
                >
                  <UIcon name="i-lucide-sliders-horizontal" class="size-5" />
                  <UBadge
                    v-if="activeFiltersCount"
                    color="primary"
                    variant="solid"
                    class="ml-1"
                  >
                    {{ activeFiltersCount }}
                  </UBadge>
                </UButton>
              </div>

              <UCollapsible v-model:open="filtersOpen">
                <div class="grid gap-3 rounded-xl border border-default bg-default p-3">
                  <UFormField label="Type">
                    <USelectMenu
                      v-model="filters.typeTerrain"
                      :items="TERRAIN_TYPES"
                      value-key="value"
                      label-key="label"
                      :search-input="false"
                      placeholder="Any type"
                    />
                  </UFormField>

                  <div class="grid gap-3 sm:grid-cols-2">
                    <UFormField label="Price min (MAD)">
                      <UInputNumber
                        v-model="filters.priceMin"
                        :min="0"
                        placeholder="0"
                        :format-options="{ maximumFractionDigits: 0 }"
                      />
                    </UFormField>
                    <UFormField label="Price max (MAD)">
                      <UInputNumber
                        v-model="filters.priceMax"
                        :min="0"
                        placeholder="Any"
                        :format-options="{ maximumFractionDigits: 0 }"
                      />
                    </UFormField>
                  </div>

                  <div class="grid gap-3 sm:grid-cols-2">
                    <UFormField label="Area min (m²)">
                      <UInputNumber
                        v-model="filters.areaMin"
                        :min="0"
                        placeholder="0"
                        :format-options="{ maximumFractionDigits: 0 }"
                      />
                    </UFormField>
                    <UFormField label="Area max (m²)">
                      <UInputNumber
                        v-model="filters.areaMax"
                        :min="0"
                        placeholder="Any"
                        :format-options="{ maximumFractionDigits: 0 }"
                      />
                    </UFormField>
                  </div>

                  <div class="flex flex-wrap items-center justify-between gap-2">
                    <UButton
                      type="button"
                      color="neutral"
                      variant="ghost"
                      size="sm"
                      class="rounded-full"
                      @click="clearAllFilters"
                    >
                      Clear all
                    </UButton>
                    <UButton
                      type="submit"
                      color="primary"
                      size="sm"
                      class="rounded-full"
                      :loading="pending"
                    >
                      Apply filters
                    </UButton>
                  </div>
                </div>
              </UCollapsible>
            </form>
          </div>

          <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-4">
            <UAlert
              v-if="error"
              color="error"
              title="Unable to load listings"
              variant="soft"
              :description="error.message"
            />

            <UAlert
              v-else-if="pending"
              color="neutral"
              title="Loading"
              variant="soft"
              description="Fetching listings..."
            />

            <ThemeAListingRow
              v-for="l in rows"
              :key="l.id"
              :title="l.title"
              :description="l.description"
              :location="l.location"
              :price="l.price"
              :status="l.status"
              :type-label="l.typeLabel"
              :area="l.area"
              :is-exclusive="l.isExclusive"
              :is-urgent="l.isUrgent"
              :is-favorite="isFavorite(l.id)"
              @toggle-favorite="toggleFavorite(l.id)"
              :to="`/sell/${l.id}`"
            />
          </div>

          <div class="border-t border-default bg-default p-4">
            <UPagination
              v-model:page="page"
              :total="response?.data?.total || 0"
              :page-count="perPage"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
