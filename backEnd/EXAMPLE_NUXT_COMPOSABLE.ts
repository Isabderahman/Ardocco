// ============================================
// COMPOSABLE NUXT 3 - API GÉOLOCALISATION
// ============================================
// Fichier: composables/useGeoAPI.ts
// À placer dans le dossier composables/ de votre projet Nuxt

export interface Commune {
  id: string
  name_fr: string
  name_ar: string
  type: 'urbaine' | 'rurale'
  code_postal: string
  latitude: number
  longitude: number
  province_name?: string
  province_code?: string
  distance?: number
}

export interface Province {
  id: string
  name_fr: string
  name_ar: string
  code: string
  latitude: number
  longitude: number
  region_name?: string
}

export interface Region {
  id: string
  name_fr: string
  name_ar: string
  code: string
  latitude: number
  longitude: number
}

export interface GeoStats {
  regions: number
  provinces: number
  communes: {
    total: number
    urbaines: number
    rurales: number
  }
  with_gps: {
    regions: number
    provinces: number
    communes: number
  }
}

export const useGeoAPI = () => {
  const config = useRuntimeConfig()
  const baseURL = config.public.apiBaseURL || 'http://localhost:8000/api'

  /**
   * Récupère les statistiques globales
   */
  const getStats = async () => {
    const { data, error } = await useFetch<{ success: boolean; data: GeoStats }>(
      `${baseURL}/geo/stats`
    )

    if (error.value) {
      console.error('Erreur lors de la récupération des stats:', error.value)
      return null
    }

    return data.value?.data || null
  }

  /**
   * Liste toutes les régions
   */
  const getRegions = async () => {
    const { data, error } = await useFetch<{ success: boolean; data: Region[] }>(
      `${baseURL}/geo/regions`
    )

    if (error.value) {
      console.error('Erreur lors de la récupération des régions:', error.value)
      return []
    }

    return data.value?.data || []
  }

  /**
   * Liste les provinces d'une région
   */
  const getProvinces = async (regionCode: string) => {
    const { data, error } = await useFetch<{ success: boolean; data: Province[] }>(
      `${baseURL}/geo/provinces/${regionCode}`
    )

    if (error.value) {
      console.error('Erreur lors de la récupération des provinces:', error.value)
      return []
    }

    return data.value?.data || []
  }

  /**
   * Liste les communes d'une province
   */
  const getCommunes = async (provinceCode: string) => {
    const { data, error } = await useFetch<{
      success: boolean
      data: {
        all: Commune[]
        by_type: {
          urbaines: Commune[]
          rurales: Commune[]
        }
      }
    }>(`${baseURL}/geo/communes/${provinceCode}`)

    if (error.value) {
      console.error('Erreur lors de la récupération des communes:', error.value)
      return { all: [], urbaines: [], rurales: [] }
    }

    return {
      all: data.value?.data.all || [],
      urbaines: data.value?.data.by_type.urbaines || [],
      rurales: data.value?.data.by_type.rurales || []
    }
  }

  /**
   * Recherche les communes à proximité d'un point GPS
   */
  const findNearby = async (
    latitude: number,
    longitude: number,
    radius = 10,
    type?: 'urbaine' | 'rurale',
    limit = 50
  ) => {
    const params = new URLSearchParams({
      latitude: latitude.toString(),
      longitude: longitude.toString(),
      radius: radius.toString(),
      limit: limit.toString(),
      ...(type && { type })
    })

    const { data, error } = await useFetch<{ success: boolean; data: Commune[] }>(
      `${baseURL}/geo/nearby?${params}`
    )

    if (error.value) {
      console.error('Erreur lors de la recherche à proximité:', error.value)
      return []
    }

    return data.value?.data || []
  }

  /**
   * Recherche de communes par nom (autocomplete)
   */
  const searchCommunes = async (
    query: string,
    latitude?: number,
    longitude?: number,
    limit = 10
  ) => {
    const params = new URLSearchParams({
      q: query,
      limit: limit.toString()
    })

    if (latitude !== undefined && longitude !== undefined) {
      params.append('latitude', latitude.toString())
      params.append('longitude', longitude.toString())
    }

    const { data, error } = await useFetch<{ success: boolean; data: Commune[] }>(
      `${baseURL}/geo/search?${params}`
    )

    if (error.value) {
      console.error('Erreur lors de la recherche de communes:', error.value)
      return []
    }

    return data.value?.data || []
  }

  /**
   * Récupère les détails complets d'une commune
   */
  const getCommune = async (id: string) => {
    const { data, error } = await useFetch<{ success: boolean; data: Commune }>(
      `${baseURL}/geo/commune/${id}`
    )

    if (error.value) {
      console.error('Erreur lors de la récupération de la commune:', error.value)
      return null
    }

    return data.value?.data || null
  }

  /**
   * Export complet de la région Casablanca-Settat
   */
  const exportCasablancaSettat = async () => {
    const { data, error } = await useFetch<{
      success: boolean
      data: {
        all: Commune[]
        by_province: Record<string, { total: number; communes: Commune[] }>
      }
    }>(`${baseURL}/geo/export/casablanca-settat`)

    if (error.value) {
      console.error('Erreur lors de l\'export:', error.value)
      return { all: [], by_province: {} }
    }

    return data.value?.data || { all: [], by_province: {} }
  }

  return {
    getStats,
    getRegions,
    getProvinces,
    getCommunes,
    findNearby,
    searchCommunes,
    getCommune,
    exportCasablancaSettat
  }
}

// ============================================
// EXEMPLES D'UTILISATION
// ============================================

/*

// ────────────────────────────────────────────
// 1. Dans un composant Vue - Sélecteur hiérarchique
// ────────────────────────────────────────────

<script setup lang="ts">
const { getRegions, getProvinces, getCommunes } = useGeoAPI()

const regions = ref<Region[]>([])
const provinces = ref<Province[]>([])
const communes = ref<Commune[]>([])

const selectedRegion = ref('')
const selectedProvince = ref('')
const selectedCommune = ref('')

// Charger les régions au montage
onMounted(async () => {
  regions.value = await getRegions()
})

// Charger les provinces quand une région est sélectionnée
watch(selectedRegion, async (newRegion) => {
  if (newRegion) {
    provinces.value = await getProvinces(newRegion)
    selectedProvince.value = ''
    communes.value = []
  }
})

// Charger les communes quand une province est sélectionnée
watch(selectedProvince, async (newProvince) => {
  if (newProvince) {
    const result = await getCommunes(newProvince)
    communes.value = result.all
    selectedCommune.value = ''
  }
})
</script>

<template>
  <div class="space-y-4">
    <select v-model="selectedRegion">
      <option value="">Sélectionner une région</option>
      <option v-for="region in regions" :key="region.id" :value="region.code">
        {{ region.name_fr }}
      </option>
    </select>

    <select v-model="selectedProvince" :disabled="!selectedRegion">
      <option value="">Sélectionner une province</option>
      <option v-for="province in provinces" :key="province.id" :value="province.code">
        {{ province.name_fr }}
      </option>
    </select>

    <select v-model="selectedCommune" :disabled="!selectedProvince">
      <option value="">Sélectionner une commune</option>
      <option v-for="commune in communes" :key="commune.id" :value="commune.id">
        {{ commune.name_fr }} ({{ commune.type }})
      </option>
    </select>
  </div>
</template>


// ────────────────────────────────────────────
// 2. Autocomplete avec recherche
// ────────────────────────────────────────────

<script setup lang="ts">
const { searchCommunes } = useGeoAPI()

const searchQuery = ref('')
const searchResults = ref<Commune[]>([])
const isSearching = ref(false)

// Recherche avec debounce
const debouncedSearch = useDebounceFn(async () => {
  if (searchQuery.value.length < 2) {
    searchResults.value = []
    return
  }

  isSearching.value = true
  searchResults.value = await searchCommunes(searchQuery.value)
  isSearching.value = false
}, 300)

watch(searchQuery, () => {
  debouncedSearch()
})
</script>

<template>
  <div>
    <input
      v-model="searchQuery"
      type="text"
      placeholder="Rechercher une commune..."
    />

    <div v-if="isSearching">Recherche...</div>

    <ul v-if="searchResults.length > 0">
      <li v-for="commune in searchResults" :key="commune.id">
        {{ commune.name_fr }} - {{ commune.province_name }}
      </li>
    </ul>
  </div>
</template>


// ────────────────────────────────────────────
// 3. Recherche par proximité GPS
// ────────────────────────────────────────────

<script setup lang="ts">
const { findNearby } = useGeoAPI()

const userLocation = ref({ lat: 33.5731, lng: -7.5898 })
const nearbyCommunes = ref<Commune[]>([])

const searchNearby = async () => {
  nearbyCommunes.value = await findNearby(
    userLocation.value.lat,
    userLocation.value.lng,
    10 // rayon en km
  )
}

// Obtenir la position de l'utilisateur
const getUserLocation = () => {
  if ('geolocation' in navigator) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        userLocation.value = {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        }
        searchNearby()
      },
      (error) => {
        console.error('Erreur de géolocalisation:', error)
      }
    )
  }
}

onMounted(() => {
  searchNearby()
})
</script>

<template>
  <div>
    <button @click="getUserLocation">
      📍 Utiliser ma position
    </button>

    <h3>Communes à proximité</h3>
    <ul>
      <li v-for="commune in nearbyCommunes" :key="commune.id">
        {{ commune.name_fr }}
        <span v-if="commune.distance">
          ({{ commune.distance.toFixed(2) }} km)
        </span>
      </li>
    </ul>
  </div>
</template>


// ────────────────────────────────────────────
// 4. Afficher les statistiques
// ────────────────────────────────────────────

<script setup lang="ts">
const { getStats } = useGeoAPI()

const stats = ref<GeoStats | null>(null)

onMounted(async () => {
  stats.value = await getStats()
})
</script>

<template>
  <div v-if="stats" class="grid grid-cols-3 gap-4">
    <div class="stat-card">
      <h3>Régions</h3>
      <p>{{ stats.regions }}</p>
    </div>

    <div class="stat-card">
      <h3>Provinces</h3>
      <p>{{ stats.provinces }}</p>
    </div>

    <div class="stat-card">
      <h3>Communes</h3>
      <p>{{ stats.communes.total }}</p>
      <small>
        {{ stats.communes.urbaines }} urbaines,
        {{ stats.communes.rurales }} rurales
      </small>
    </div>
  </div>
</template>


// ────────────────────────────────────────────
// 5. Configuration Nuxt (nuxt.config.ts)
// ────────────────────────────────────────────

export default defineNuxtConfig({
  runtimeConfig: {
    public: {
      apiBaseURL: process.env.NUXT_PUBLIC_API_BASE_URL || 'http://localhost:8000/api'
    }
  }
})


// ────────────────────────────────────────────
// 6. Variables d'environnement (.env)
// ────────────────────────────────────────────

NUXT_PUBLIC_API_BASE_URL=http://localhost:8000/api

*/
