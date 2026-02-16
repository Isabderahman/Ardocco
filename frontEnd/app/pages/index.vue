<template>
  <main>
    <HomeHero />

    <!-- Airbnb-style Search Section -->
    <section class="py-8 sm:py-12 bg-white">
      <UContainer>
        <div class="bg-white rounded-2xl shadow-lg p-4 sm:p-6 -mt-20 relative z-10">
          <div class="flex flex-col lg:flex-row gap-4">
            <!-- Location Search -->
            <div class="flex-1">
              <label class="block text-sm font-medium text-gray-700 mb-1">Localisation</label>
              <UInput
                v-model="searchFilters.q"
                placeholder="Ville, région ou quartier..."
                icon="i-lucide-map-pin"
                size="md"
                class="w-full"
              />
            </div>

            <!-- Type Terrain -->
            <div class="w-full lg:w-48">
              <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
              <USelect
                v-model="searchFilters.type_terrain"
                :items="terrainTypes"
                placeholder="Tous les types"
                size="md"
                class="w-full"
              />
            </div>

            <!-- Price Range -->
            <div class="w-full lg:w-64">
              <label class="block text-sm font-medium text-gray-700 mb-1">Budget</label>
              <div class="flex gap-2">
                <div class="w-1/2">
                  <UInput
                    v-model="searchFilters.prix_min"
                    placeholder="Min"
                    type="number"
                    size="md"
                    class="w-full"
                  />
                </div>
                <div class="w-1/2">
                  <UInput
                    v-model="searchFilters.prix_max"
                    placeholder="Max"
                    type="number"
                    size="md"
                    class="w-full"
                  />
                </div>
              </div>
            </div>

            <!-- Search Button -->
            <div class="flex items-end">
              <UButton
                label="Rechercher"
                color="primary"
                size="lg"
                icon="i-lucide-search"
                class="w-full lg:w-auto"
                @click="handleSearch"
              />
            </div>
          </div>

          <!-- Advanced Filters Toggle -->
          <div class="mt-4">
            <UButton
              :label="showAdvancedFilters ? 'Masquer les filtres' : 'Filtres avancés'"
              variant="solid"
              color="primary"
              size="md"
              :icon="showAdvancedFilters ? 'i-lucide-chevron-up' : 'i-lucide-chevron-down'"
              trailing
              @click="showAdvancedFilters = !showAdvancedFilters"
            />

            <div v-if="showAdvancedFilters" class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <!-- Surface Range -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Surface (m²)</label>
                <div class="flex gap-2">
                  <div class="w-1/2">
                    <UInput
                      v-model="searchFilters.superficie_min"
                      placeholder="Min"
                      type="number"
                      size="md"
                      class="w-full"
                    />
                  </div>
                  <div class="w-1/2">
                    <UInput
                      v-model="searchFilters.superficie_max"
                      placeholder="Max"
                      type="number"
                      size="md"
                      class="w-full"
                    />
                  </div>
                </div>
              </div>

              <!-- Rentability -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rentabilité min (%)</label>
                <UInput
                  v-model="searchFilters.rentabilite_min"
                  placeholder="Ex: 10"
                  type="number"
                  size="md"
                  class="w-full"
                />
              </div>

              <!-- Sort By -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Trier par</label>
                <USelect
                  v-model="searchFilters.sort"
                  :items="sortOptions"
                  size="md"
                  class="w-full"
                />
              </div>

              <!-- Reset Filters -->
              <div class="flex items-end">
                <UButton
                  label="Réinitialiser"
                  variant="outline"
                  color="neutral"
                  size="md"
                  icon="i-lucide-x"
                  @click="resetFilters"
                />
              </div>
            </div>
          </div>
        </div>
      </UContainer>
    </section>

    <!-- CTA Buttons (Buy / Sell) -->
    <section class=" sm:py-16">
      <UContainer>
        <div class="grid gap-6 md:grid-cols-2">
          <!-- Buy CTA -->
          <div class="group relative overflow-hidden rounded-3xl bg-default p-8 ring-1 ring-primary-200/70 transition hover:-translate-y-0.5 hover:shadow-lg focus-within:ring-2 focus-within:ring-primary-400">
            <div class="min-w-0">
              <h3 class="text-xl font-semibold text-primary-700">
                Acheter un terrain
              </h3>
              <p class="mt-2 text-sm text-muted">
                Explorez notre sélection de terrains avec analyse financière et expertise complète.
              </p>
            </div>

            <div class="mt-6">
              <UButton
                label="Rechercher des terrains"
                color="primary"
                variant="outline"
                size="lg"
                to="/terrains"
                icon="i-lucide-search"
                class="w-full sm:w-auto rounded-full font-bold shadow-sm transition-shadow group-hover:shadow-md"
              />
            </div>
          </div>

          <!-- Sell CTA -->
          <div class="group relative overflow-hidden rounded-3xl bg-default p-8 ring-1 ring-primary-200/70 transition hover:-translate-y-0.5 hover:shadow-lg focus-within:ring-2 focus-within:ring-primary-400">
            <div class="min-w-0">
              <h3 class="text-xl font-semibold text-primary-700">
                Vendre votre terrain
              </h3>
              <p class="mt-2 text-sm text-muted">
                Publiez votre annonce et bénéficiez de notre réseau d'acheteurs qualifiés.
              </p>
            </div>

            <div class="mt-6">
              <UButton
                label="Publier une annonce"
                color="primary"
                variant="outline"
                size="lg"
                to="/terrains/new"
                icon="i-lucide-plus"
                class="w-full sm:w-auto rounded-full font-bold shadow-sm transition-shadow group-hover:shadow-md"
              />
            </div>
          </div>
        </div>
      </UContainer>
    </section>

    <!-- Featured Terrains -->
    <section class="py-12 sm:py-16 bg-elevated/30">
      <UContainer>
        <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
          <div>
            <h2 class="text-2xl font-semibold text-highlighted">
              Terrains en vedette
            </h2>
            <p class="mt-2 text-sm text-muted">
              Découvrez nos terrains sélectionnés à travers Casablanca-Settat.
            </p>
          </div>

          <UButton
            label="Voir tout"
            color="neutral"
            variant="outline"
            to="/terrains"
            class="rounded-full"
          />
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <ThemeATerrainCard
            v-for="t in featuredTerrains"
            :key="t.id"
            :title="t.title"
            :description="t.description"
            :location="t.location"
            :price="t.price"
            :badge="t.badge"
            :to="t.to"
            :image-url="t.imageUrl"
            :lat="t.lat"
            :lng="t.lng"
            :geojson-polygon="t.geojsonPolygon"
          />
        </div>
      </UContainer>
    </section>

    <!-- Map Preview Section -->
    <section class="py-12 sm:py-16">
      <UContainer>
        <div class="text-center mb-8">
          <h2 class="text-2xl font-semibold text-highlighted">
            Explorez sur la carte
          </h2>
          <p class="mt-2 text-sm text-muted">
            Visualisez les terrains disponibles dans la région Casablanca-Settat.
          </p>
        </div>

        <div class="relative rounded-2xl overflow-hidden shadow-lg h-96 bg-default ring-1 ring-default">
          <ClientOnly>
            <CasablancaSettatMap
              height="100%"
              :markers="mapMarkers"
              :fit-to-markers="true"
              :show-legend="false"
              :show-controls="false"
            />
          </ClientOnly>

          <ThemeAPublicListingsLegend :items="provincesLegend" />
        </div>

        <div class="text-center mt-6">
          <UButton
            label="Voir la carte complète"
            color="primary"
            variant="outline"
            to="/terrains"
            icon="i-lucide-map"
            class="rounded-full"
          />
        </div>
      </UContainer>
    </section>
  </main>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { PublicListingsResponse } from '~/types/models/listing'
import type { MapMarker } from '~/types/models/map'
import { listingService } from '~/services/listingService'
import type { TerrainCard } from '~/types/models/terrain'

const router = useRouter()

const showAdvancedFilters = ref(false)

const searchFilters = ref({
  q: '',
  type_terrain: '',
  prix_min: '',
  prix_max: '',
  superficie_min: '',
  superficie_max: '',
  rentabilite_min: '',
  sort: 'recent',
})

const terrainTypes = [
  { label: 'Résidentiel', value: 'residentiel' },
  { label: 'Commercial', value: 'commercial' },
  { label: 'Industriel', value: 'industriel' },
  { label: 'Agricole', value: 'agricole' },
  { label: 'Mixte', value: 'mixte' },
]

const sortOptions = [
  { label: 'Plus récents', value: 'recent' },
  { label: 'Prix croissant', value: 'price_asc' },
  { label: 'Prix décroissant', value: 'price_desc' },
  { label: 'Surface croissante', value: 'area_asc' },
  { label: 'Surface décroissante', value: 'area_desc' },
]

const resetFilters = () => {
  searchFilters.value = {
    q: '',
    type_terrain: '',
    prix_min: '',
    prix_max: '',
    superficie_min: '',
    superficie_max: '',
    rentabilite_min: '',
    sort: 'recent',
  }
}

const handleSearch = () => {
  const query: Record<string, string> = {}

  Object.entries(searchFilters.value).forEach(([key, value]) => {
    if (value) query[key] = value
  })

  router.push({ path: '/terrains', query })
}

const provincesLegend = [
  { name: 'Casablanca', color: '#1A7BFD' },
  { name: 'Nouaceur', color: '#60a5fa' },
  { name: 'Mohammedia', color: '#94a3b8' },
  { name: 'Mediouna', color: '#64748b' },
  { name: 'Berrechid', color: '#94a3b8' }
]

function numeric(value: unknown): number | null {
  const n = Number(value)
  return Number.isFinite(n) ? n : null
}

function coverPhotoUrl(listing: { documents?: unknown } | null | undefined): string | null {
  const docs = listing && (listing as { documents?: unknown }).documents
  if (!Array.isArray(docs)) return null

  const photo = docs.find((doc) => {
    const obj = doc as { document_type?: unknown, file_path?: unknown }
    return obj?.document_type === 'photos' && typeof obj.file_path === 'string'
  }) as { file_path?: string } | undefined

  const path = String(photo?.file_path || '').replace(/^\/+/, '')
  return path ? `/storage/${path}` : null
}

function formatPrice(price: number | string | null | undefined) {
  const numPrice = Number(price)
  if (!Number.isFinite(numPrice) || numPrice <= 0) return '—'
  return new Intl.NumberFormat('fr-MA', {
    style: 'currency',
    currency: 'MAD',
    maximumFractionDigits: 0
  }).format(numPrice)
}

function formatPriceMDHS(price: number | string | null | undefined) {
  const numPrice = Number(price)
  if (!Number.isFinite(numPrice) || numPrice <= 0) return '—'
  const millions = numPrice / 1_000_000
  if (millions >= 1) {
    return `${millions.toFixed(millions % 1 === 0 ? 0 : 1)} MDHS`
  }
  return `${(numPrice / 1000).toFixed(0)}K DHS`
}

const previewQuery = computed(() => ({
  sort: 'recent',
  per_page: 40
}))

const { data: listingsData } = useFetch<PublicListingsResponse>(listingService.publicListingsUrl(), {
  query: previewQuery,
  server: false
})

const mapMarkers = computed<MapMarker[]>(() => {
  const items = listingsData.value?.data?.data || []
  return items
    .map<MapMarker | null>((listing) => {
      const lat = numeric(listing.latitude)
      const lng = numeric(listing.longitude)
      if (lat == null || lng == null) return null

      const subtitle = listing.quartier || listing.commune?.name_fr || listing.commune?.province?.name_fr || 'Non spécifié'

      return {
        id: listing.id,
        lat,
        lng,
        title: listing.title,
        subtitle,
        label: formatPriceMDHS(listing.prix_demande),
        href: `/terrains/${listing.id}`
      }
    })
    .filter((m): m is MapMarker => m !== null)
})

const featuredTerrains = computed<TerrainCard[]>(() => {
  const items = listingsData.value?.data?.data || []

  return items.slice(0, 6).map((listing) => {
    const communeName = listing.commune?.name_fr || ''
    const provinceName = listing.commune?.province?.name_fr || ''
    const location = listing.quartier
      || (communeName && provinceName ? `${communeName}, ${provinceName}` : (communeName || provinceName))
      || 'Non spécifié'

    const superficie = numeric(listing.superficie)
    const superficieLabel = superficie != null ? `${superficie.toLocaleString('fr-MA', { maximumFractionDigits: 0 })} m²` : null
    const typeLabel = listing.type_terrain ? String(listing.type_terrain) : null

    const lat = numeric(listing.latitude)
    const lng = numeric(listing.longitude)

    return {
      id: listing.id,
      title: listing.title,
      description: [typeLabel, superficieLabel].filter(Boolean).join(' · '),
      location,
      price: formatPrice(listing.prix_demande),
      badge: 'Vedette',
      to: `/terrains/${listing.id}`,
      imageUrl: coverPhotoUrl(listing),
      lat,
      lng,
      geojsonPolygon: listing.geojson_polygon ?? null
    }
  })
})
</script>
