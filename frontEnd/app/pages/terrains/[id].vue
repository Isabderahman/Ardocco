<script setup lang="ts">
import type {BackendResponse} from '~/types/models/api'
import type {BackendListing} from '~/types/models/listing'

definePageMeta({
  title: 'Détails du terrain'
})

const route = useRoute()
const {token} = useAuth()

const listingId = computed(() => String(route.params.id || ''))

const publicListingQuery = usePublicListing(listingId)

const headers = computed(() => {
  const value = typeof token.value === 'string' ? token.value.trim() : ''
  return value ? {Authorization: `Bearer ${value}`} : undefined
})

const {
  data: privateResponse,
  pending: privatePending,
  error: privateError,
  execute: fetchPrivate
} = useFetch<BackendResponse<BackendListing>>(
  () => `/api/backend/listings/${encodeURIComponent(listingId.value)}`,
  {
    immediate: false,
    watch: false,
    headers
  }
)

watch(listingId, () => {
  privateResponse.value = undefined
  privateError.value = undefined
})

// Fetch from private endpoint when:
// 1. User is authenticated AND
// 2. Public endpoint returned an error (404 for unpublished, 403 for private, etc.)
const shouldFetchPrivate = computed(() => {
  if (!token.value) return false
  // Trigger private fetch for any public endpoint error (not just 404)
  // This handles draft listings, private listings, etc.
  return publicListingQuery.error.value !== null && !publicListingQuery.pending.value
})

watch(
  shouldFetchPrivate,
  (should) => {
    if (!should) return
    if (privatePending.value) return
    void fetchPrivate()
  },
  {immediate: true}
)

const privateListing = computed<BackendListing | null>(() => privateResponse.value?.data || null)

const listing = computed<BackendListing | null>(() => {
  return publicListingQuery.listing.value || privateListing.value || null
})

const hasFullAccess = computed(() => publicListingQuery.hasFullAccess.value || !!privateListing.value)
const pending = computed(() => publicListingQuery.pending.value || (shouldFetchPrivate.value && privatePending.value))
const error = computed(() => (listing.value ? null : (privateError.value || publicListingQuery.error.value)))

const {
  open: showContactModal,
  pending: contactPending,
  success: contactSuccess,
  form: contactForm,
  submit: submitContact
} = useContactRequestModal(listingId)

// Favorites
const {addToFavorites, removeFromFavorites, isFavorite} = useFavoriteListings()
const isInFavorites = computed(() => listing.value ? isFavorite(listing.value.id) : false)

const toggleFavorite = async () => {
  if (!token.value) {
    navigateTo('/login')
    return
  }
  if (!listing.value) return

  if (isInFavorites.value) {
    await removeFromFavorites(listing.value.id)
  } else {
    await addToFavorites(listing.value.id)
  }
}

const formatPrice = (price: number | string | null | undefined) => {
  const numPrice = Number(price) || 0
  return new Intl.NumberFormat('fr-MA', {
    style: 'currency',
    currency: 'MAD',
    maximumFractionDigits: 0,
  }).format(numPrice)
}

const terrainTypeLabels: Record<string, string> = {
  residentiel: 'Résidentiel',
  commercial: 'Commercial',
  industriel: 'Industriel',
  agricole: 'Agricole',
  mixte: 'Mixte',
}

function numeric(value: unknown): number | null {
  const n = Number(value)
  return Number.isFinite(n) ? n : null
}

const listingLat = computed(() => numeric(listing.value?.latitude))
const listingLng = computed(() => numeric(listing.value?.longitude))
const selectedPolygon = computed(() => listing.value?.geojson_polygon ?? null)

const hasPhotos = computed(() => {
  const docs = listing.value?.documents
  if (!Array.isArray(docs)) return false
  return docs.some(doc => (doc as { document_type?: unknown })?.document_type === 'photos')
})

const photoUrls = computed(() => {
  const docs = listing.value?.documents
  if (!Array.isArray(docs)) return []
  return docs
    .filter(doc => (doc as { document_type?: unknown })?.document_type === 'photos')
    .map(doc => (doc as { full_url?: string })?.full_url)
    .filter((url): url is string => !!url)
})

const activePhotoIndex = ref(0)

// Etude d'investissement (approved only for display)
const approvedEtude = computed(() => {
  const etudes = listing.value?.etudesInvestissement
  if (!Array.isArray(etudes)) return null
  return etudes.find(e => e.status === 'approved') || null
})

const listingMarkers = computed(() => {
  if (!listing.value) return []
  const lat = listingLat.value
  const lng = listingLng.value
  if (lat == null || lng == null) return []

  return [{
    id: listing.value.id,
    lat,
    lng,
    title: listing.value.title
  }]
})
</script>

<template>
  <main class="min-h-screen bg-gray-50">
    <!-- Loading -->
    <div v-if="pending" class="flex items-center justify-center py-20">
      <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin text-primary-500"/>
    </div>

    <!-- Error -->
    <div v-else-if="error || !listing" class="text-center py-20">
      <UIcon name="i-lucide-alert-circle" class="w-12 h-12 mx-auto text-red-500 mb-4"/>
      <p class="text-gray-600">Terrain introuvable.</p>
      <UButton label="Retour à la liste" variant="outline" class="mt-4" to="/terrains"/>
    </div>

    <!-- Content -->
    <template v-else>
      <!-- Header -->
      <div class="bg-white border-b">
        <UContainer class="py-4">
          <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <NuxtLink to="/terrains" class="hover:text-primary-600">Terrains</NuxtLink>
            <UIcon name="i-lucide-chevron-right" class="w-4 h-4"/>
            <span>{{ listing.reference }}</span>
          </div>

          <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">{{ listing.title }}</h1>
              <p class="text-gray-600 mt-1 flex items-center gap-2">
                <UIcon name="i-lucide-map-pin" class="w-4 h-4"/>
                {{ listing.quartier || listing.commune?.name_fr || 'Localisation non spécifiée' }}
              </p>
            </div>

            <div class="flex items-center gap-3">
              <UButton
                :icon="isInFavorites ? 'i-lucide-heart' : 'i-lucide-heart'"
                :color="isInFavorites ? 'error' : 'neutral'"
                :variant="isInFavorites ? 'solid' : 'outline'"
                @click="toggleFavorite"
              />
              <UButton
                icon="i-lucide-share-2"
                variant="outline"
                color="neutral"
              />
            </div>
          </div>
        </UContainer>
      </div>

      <!-- Main content -->
      <UContainer class="py-8">
        <div class="grid lg:grid-cols-3 gap-8">
          <!-- Left column (main info) -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Media (photos or fallback map) -->
            <div class="aspect-video overflow-hidden rounded-xl bg-gray-200 relative">
              <template v-if="photoUrls.length > 0">
                <img
                  :src="photoUrls[activePhotoIndex]"
                  :alt="`Photo ${activePhotoIndex + 1} du terrain`"
                  class="h-full w-full object-cover"
                />
                <!-- Photo navigation controls -->
                <div v-if="photoUrls.length > 1" class="absolute inset-0 flex items-center justify-between px-4">
                  <UButton
                    icon="i-lucide-chevron-left"
                    color="neutral"
                    variant="solid"
                    size="sm"
                    class="opacity-80 hover:opacity-100 bg-white text-gray-900 hover:bg-white"
                    :disabled="activePhotoIndex === 0"
                    @click="activePhotoIndex--"
                  />
                  <UButton
                    icon="i-lucide-chevron-right"
                    color="neutral"
                    variant="solid"
                    size="sm"
                    class="opacity-80 hover:opacity-100 bg-white text-gray-900 hover:bg-white"
                    :disabled="activePhotoIndex === photoUrls.length - 1"
                    @click="activePhotoIndex++"
                  />
                </div>
                <!-- Photo indicators -->
                <div v-if="photoUrls.length > 1" class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
                  <button
                    v-for="(_, index) in photoUrls"
                    :key="index"
                    class="w-2 h-2 rounded-full transition-colors"
                    :class="index === activePhotoIndex ? 'bg-white' : 'bg-white/50'"
                    @click="activePhotoIndex = index"
                  />
                </div>
              </template>

              <template v-else-if="selectedPolygon || (listingLat != null && listingLng != null)">
                <ClientOnly>
                  <CasablancaSettatMap
                    height="100%"
                    :show-legend="false"
                    :show-controls="false"
                    :fit-to-region="false"
                    :markers="listingMarkers"
                    :fit-to-markers="!selectedPolygon"
                    :selected-geojson-polygon="selectedPolygon"
                    :fit-to-selected-geojson-polygon="true"
                  />
                </ClientOnly>
              </template>

              <template v-else>
                <div class="flex h-full w-full items-center justify-center">
                  <UIcon name="i-lucide-image" class="w-16 h-16 text-gray-400"/>
                </div>
              </template>
            </div>

            <!-- Key info cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <div class="bg-white rounded-lg p-4 text-center shadow-sm">
                <p class="text-sm text-gray-500">Prix</p>
                <p class="text-lg font-bold text-primary-600">{{ formatPrice(listing.prix_demande) }}</p>
              </div>
              <div class="bg-white rounded-lg p-4 text-center shadow-sm">
                <p class="text-sm text-gray-500">Surface</p>
                <p class="text-lg font-bold text-gray-900">{{ listing.superficie }} m²</p>
              </div>
              <div class="bg-white rounded-lg p-4 text-center shadow-sm">
                <p class="text-sm text-gray-500">Prix/m²</p>
                <p class="text-lg font-bold text-gray-900">
                  {{ hasFullAccess && listing.prix_par_m2 ? formatPrice(listing.prix_par_m2) : '—' }}
                </p>
              </div>
              <div class="bg-white rounded-lg p-4 text-center shadow-sm">
                <p class="text-sm text-gray-500">Type</p>
                <p class="text-lg font-bold text-gray-900">
                  {{ terrainTypeLabels[listing.type_terrain || ''] || listing.type_terrain }}
                </p>
              </div>
            </div>

            <!-- Description (full access only) -->
            <div v-if="hasFullAccess && listing.description" class="bg-white rounded-xl p-6 shadow-sm">
              <h2 class="text-lg font-semibold text-gray-900 mb-4">Description</h2>
              <p class="text-gray-600 whitespace-pre-line">{{ listing.description }}</p>
            </div>

            <!-- Technical details (full access only) -->
            <div v-if="hasFullAccess" class="bg-white rounded-xl shadow-sm overflow-hidden">
              <!-- Header -->
              <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 text-white">
                <div class="flex items-center gap-3">
                  <UIcon name="i-lucide-clipboard-list" class="w-6 h-6" />
                  <div>
                    <h2 class="text-lg font-semibold">Caracteristiques Techniques</h2>
                    <p class="text-blue-100 text-sm">Informations detaillees du terrain</p>
                  </div>
                </div>
              </div>

              <div class="p-6 space-y-6">
                <!-- Terrain Info Table -->
                <div>
                  <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <UIcon name="i-lucide-map" class="w-4 h-4 text-blue-500" />
                    Terrain
                  </h3>
                  <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                      <tr>
                        <td class="py-2 text-gray-500">Superficie</td>
                        <td class="py-2 text-right font-medium text-gray-900">{{ listing.superficie }} m²</td>
                      </tr>
                      <tr>
                        <td class="py-2 text-gray-500">Type de terrain</td>
                        <td class="py-2 text-right font-medium text-gray-900 capitalize">{{ terrainTypeLabels[listing.type_terrain || ''] || listing.type_terrain || '-' }}</td>
                      </tr>
                      <tr v-if="listing.forme_terrain">
                        <td class="py-2 text-gray-500">Forme du terrain</td>
                        <td class="py-2 text-right font-medium text-gray-900 capitalize">{{ listing.forme_terrain }}</td>
                      </tr>
                      <tr v-if="listing.topographie">
                        <td class="py-2 text-gray-500">Topographie</td>
                        <td class="py-2 text-right font-medium text-gray-900 capitalize">{{ listing.topographie }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Legal/Administrative Table -->
                <div class="border-t pt-4">
                  <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <UIcon name="i-lucide-file-text" class="w-4 h-4 text-blue-500" />
                    Situation Administrative
                  </h3>
                  <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                      <tr>
                        <td class="py-2 text-gray-500">Titre foncier</td>
                        <td class="py-2 text-right font-medium text-gray-900">{{ listing.titre_foncier || 'Non renseigne' }}</td>
                      </tr>
                      <tr>
                        <td class="py-2 text-gray-500">Zonage / Perimetre</td>
                        <td class="py-2 text-right font-medium text-gray-900">{{ listing.zonage || '-' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Construction Parameters Table -->
                <div v-if="listing.coefficient_occupation || listing.hauteur_max" class="border-t pt-4">
                  <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <UIcon name="i-lucide-building" class="w-4 h-4 text-blue-500" />
                    Parametres de Construction
                  </h3>
                  <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                      <tr v-if="listing.coefficient_occupation">
                        <td class="py-2 text-gray-500">Coefficient d'Occupation (COS)</td>
                        <td class="py-2 text-right font-medium text-gray-900">{{ listing.coefficient_occupation }}</td>
                      </tr>
                      <tr v-if="listing.hauteur_max">
                        <td class="py-2 text-gray-500">Hauteur maximale</td>
                        <td class="py-2 text-right font-medium text-gray-900">{{ listing.hauteur_max }} m</td>
                      </tr>
                    </tbody>
                  </table>

                  <!-- Constructibility indicator -->
                  <div v-if="listing.coefficient_occupation && listing.superficie" class="mt-4 p-4 bg-blue-50 rounded-lg">
                    <div class="flex items-center justify-between">
                      <span class="text-sm text-blue-700">Surface constructible estimee</span>
                      <span class="text-lg font-bold text-blue-900">
                        {{ Math.round(Number(listing.superficie) * Number(listing.coefficient_occupation)) }} m²
                      </span>
                    </div>
                    <p class="text-xs text-blue-600 mt-1">Base: {{ listing.superficie }} m² × COS {{ listing.coefficient_occupation }}</p>
                  </div>
                </div>

                <!-- Viabilisation -->
                <div v-if="listing.viabilisation?.length" class="border-t pt-4">
                  <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <UIcon name="i-lucide-plug" class="w-4 h-4 text-blue-500" />
                    Viabilisation
                  </h3>
                  <div class="flex flex-wrap gap-2">
                    <span
                      v-for="v in listing.viabilisation"
                      :key="v"
                      class="bg-green-100 text-green-700 text-sm px-3 py-1 rounded-full flex items-center gap-1"
                    >
                      <UIcon name="i-lucide-check" class="w-3 h-3" />
                      {{ v }}
                    </span>
                  </div>
                </div>

                <!-- Price Analysis -->
                <div class="border-t pt-4">
                  <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <UIcon name="i-lucide-calculator" class="w-4 h-4 text-blue-500" />
                    Analyse du Prix
                  </h3>
                  <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                      <tr>
                        <td class="py-2 text-gray-500">Prix demande</td>
                        <td class="py-2 text-right font-bold text-primary-600">{{ formatPrice(listing.prix_demande) }}</td>
                      </tr>
                      <tr>
                        <td class="py-2 text-gray-500">Prix au m²</td>
                        <td class="py-2 text-right font-medium text-gray-900">{{ listing.prix_par_m2 ? formatPrice(listing.prix_par_m2) : '-' }}</td>
                      </tr>
                      <tr v-if="listing.prix_estime">
                        <td class="py-2 text-gray-500">Prix estime (marche)</td>
                        <td class="py-2 text-right font-medium text-green-600">{{ formatPrice(listing.prix_estime) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Construction Costs Estimate -->
                <div v-if="listing.superficie" class="border-t pt-4">
                  <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <UIcon name="i-lucide-hard-hat" class="w-4 h-4 text-blue-500" />
                    Couts de Construction (Estimation)
                  </h3>
                  <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                      <tr>
                        <td class="py-2 text-gray-500">Surface constructible</td>
                        <td class="py-2 text-right font-medium text-gray-900">
                          {{ Math.round(Number(listing.superficie) * Number(listing.coefficient_occupation || 4)) }} m²
                          <span v-if="!listing.coefficient_occupation" class="text-xs text-gray-400">(COS 4 par defaut)</span>
                        </td>
                      </tr>
                      <tr>
                        <td class="py-2 text-gray-500">Gros oeuvres (2,500 MAD/m²)</td>
                        <td class="py-2 text-right font-medium text-gray-900">
                          {{ formatPrice(Math.round(Number(listing.superficie) * Number(listing.coefficient_occupation || 4) * 2500)) }}
                        </td>
                      </tr>
                      <tr>
                        <td class="py-2 text-gray-500">Second oeuvre + Finitions (1,500 MAD/m²)</td>
                        <td class="py-2 text-right font-medium text-gray-900">
                          {{ formatPrice(Math.round(Number(listing.superficie) * Number(listing.coefficient_occupation || 4) * 1500)) }}
                        </td>
                      </tr>
                      <tr>
                        <td class="py-2 text-gray-500">Amenagements divers</td>
                        <td class="py-2 text-right font-medium text-gray-900">{{ formatPrice(100000) }}</td>
                      </tr>
                      <tr class="bg-gray-50">
                        <td class="py-2 px-2 font-semibold text-gray-700">Total travaux</td>
                        <td class="py-2 px-2 text-right font-bold text-gray-900">
                          {{ formatPrice(Math.round(Number(listing.superficie) * Number(listing.coefficient_occupation || 4) * 4000) + 100000) }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                  <p class="text-xs text-gray-400 mt-2">* Estimation basee sur les couts moyens de construction au Maroc (R+4)</p>
                </div>

                <!-- Additional Fees -->
                <div v-if="listing.superficie" class="border-t pt-4">
                  <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <UIcon name="i-lucide-receipt" class="w-4 h-4 text-blue-500" />
                    Frais Additionnels (Estimation)
                  </h3>
                  <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                      <tr>
                        <td class="py-2 text-gray-500">Frais d'immatriculation (5%)</td>
                        <td class="py-2 text-right font-medium text-gray-900">
                          {{ formatPrice(Math.round(Number(listing.prix_demande || 0) * 0.05)) }}
                        </td>
                      </tr>
                      <tr>
                        <td class="py-2 text-gray-500">Groupement d'etudes (2.5%)</td>
                        <td class="py-2 text-right font-medium text-gray-900">
                          {{ formatPrice(Math.round((Number(listing.superficie) * Number(listing.coefficient_occupation || 4) * 4000 + 100000) * 0.025)) }}
                        </td>
                      </tr>
                      <tr>
                        <td class="py-2 text-gray-500">Autorisation + Eclatement</td>
                        <td class="py-2 text-right font-medium text-gray-900">{{ formatPrice(50000) }}</td>
                      </tr>
                      <tr>
                        <td class="py-2 text-gray-500">LYDEC / Branchements</td>
                        <td class="py-2 text-right font-medium text-gray-900">{{ formatPrice(100000) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Investment Summary -->
                <div v-if="listing.superficie || listing.cout_investissement" class="border-t pt-4">
                  <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <UIcon name="i-lucide-piggy-bank" class="w-4 h-4 text-blue-500" />
                    Resume Investissement
                  </h3>
                  <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg">
                    <table class="w-full text-sm">
                      <tbody>
                        <tr>
                          <td class="py-1 text-gray-600">Prix terrain</td>
                          <td class="py-1 text-right font-medium text-gray-900">{{ formatPrice(listing.prix_demande) }}</td>
                        </tr>
                        <tr v-if="listing.superficie">
                          <td class="py-1 text-gray-600">Travaux + Frais</td>
                          <td class="py-1 text-right font-medium text-gray-900">
                            {{ formatPrice(Math.round(Number(listing.superficie) * Number(listing.coefficient_occupation || 4) * 4000) + 350000) }}
                          </td>
                        </tr>
                        <tr class="border-t border-blue-200">
                          <td class="py-2 font-bold text-gray-900">Investissement Total</td>
                          <td class="py-2 text-right text-xl font-bold text-blue-700">
                            {{ listing.cout_investissement
                              ? formatPrice(listing.cout_investissement)
                              : formatPrice(Number(listing.prix_demande || 0) + Math.round(Number(listing.superficie || 0) * Number(listing.coefficient_occupation || 4) * 4000) + 350000)
                            }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                    <div v-if="listing.ratio" class="mt-3 pt-3 border-t border-blue-200 flex items-center justify-between">
                      <span class="text-sm text-gray-600">Ratio de rentabilite</span>
                      <span
                        class="text-lg font-bold"
                        :class="Number(listing.ratio) >= 0 ? 'text-green-600' : 'text-red-600'"
                      >
                        {{ Number(listing.ratio).toFixed(1) }}%
                      </span>
                    </div>
                  </div>
                  <p class="text-xs text-gray-400 mt-2">* Estimations indicatives - Consultez l'etude d'investissement pour des calculs detailles</p>
                </div>
              </div>
            </div>

            <!-- Financial Analysis (full access only) -->
            <FicheFinanciere
              v-if="hasFullAccess && listing.ficheFinanciere"
              :fiche="listing.ficheFinanciere"
            />

            <!-- Legal analysis (full access only) -->
            <div v-if="hasFullAccess && listing.ficheJuridique" class="bg-white rounded-xl p-6 shadow-sm">
              <h2 class="text-lg font-semibold text-gray-900 mb-4">Analyse juridique</h2>
              <div class="grid sm:grid-cols-2 gap-4">
                <div v-if="listing.ficheJuridique.land_status" class="flex justify-between">
                  <span class="text-gray-500">Statut foncier</span>
                  <span class="font-medium">{{ listing.ficheJuridique.land_status }}</span>
                </div>
                <div v-if="listing.ficheJuridique.compliance_status" class="flex justify-between">
                  <span class="text-gray-500">Conformité</span>
                  <span
                    class="font-medium"
                    :class="{
                      'text-green-600': listing.ficheJuridique.compliance_status === 'conforme',
                      'text-red-600': listing.ficheJuridique.compliance_status === 'non_conforme',
                      'text-yellow-600': listing.ficheJuridique.compliance_status === 'en_cours',
                    }"
                  >
                    {{ listing.ficheJuridique.compliance_status }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Investment Study / Business Plan (full access only) -->
            <EtudeInvestissement
              v-if="hasFullAccess && approvedEtude"
              :etude="approvedEtude"
              :show-actions="true"
            />

            <!-- Limited access notice -->
            <div
              v-if="!hasFullAccess"
              class="bg-white border border-primary-200 rounded-2xl p-5 shadow-sm"
            >
              <div class="flex items-start justify-between gap-4">
                <div>
                  <h3 class="text-lg font-semibold text-gray-900">Accès limité</h3>
                  <p class="mt-1 text-sm text-gray-600">
                    Connectez-vous pour débloquer les informations complètes.
                  </p>
                </div>

                <span
                  class="shrink-0 inline-flex items-center rounded-full bg-primary-50 text-primary-700 px-3 py-1 text-xs font-medium"
                >
      Verrouillé
    </span>
              </div>

              <ul class="mt-4 space-y-2 text-sm text-gray-700">
                <li class="flex gap-2">
                  <span class="mt-1 h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                  <span>Description complète</span>
                </li>
                <li class="flex gap-2">
                  <span class="mt-1 h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                  <span>Plans cadastraux</span>
                </li>
                <li class="flex gap-2">
                  <span class="mt-1 h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                  <span>Analyse financière et rentabilité</span>
                </li>
                <li class="flex gap-2">
                  <span class="mt-1 h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                  <span>Coordonnées du vendeur ou de l’agent</span>
                </li>
              </ul>

              <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:items-center">
                <UButton
                  label="Se connecter"
                  color="primary"
                  size="lg"
                  to="/login"
                  class="sm:w-auto w-full"
                />
                <p class="text-xs text-gray-500">
                  Ça prend moins d’une minute.
                </p>
              </div>
            </div>

          </div>

          <!-- Right column (sidebar) -->
          <div class="space-y-6">
            <!-- Contact card -->
            <div class="bg-white rounded-xl p-6 shadow-sm sticky top-24">
              <div class="text-center mb-6">
                <p class="text-2xl font-bold text-primary-600">{{ formatPrice(listing.prix_demande) }}</p>
                <p class="text-sm text-gray-500">{{ listing.superficie }} m²</p>
              </div>

              <template v-if="hasFullAccess">
                <!-- Agent/Owner info -->
                <div v-if="listing.agent || listing.owner" class="mb-6 pb-6 border-b">
                  <p class="text-sm text-gray-500 mb-2">Contact</p>
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                      <UIcon name="i-lucide-user" class="w-5 h-5 text-gray-500"/>
                    </div>
                    <div>
                      <p class="font-medium text-gray-900">
                        {{ (listing.agent || listing.owner)?.first_name }}
                        {{ (listing.agent || listing.owner)?.last_name }}
                      </p>
                      <p class="text-sm text-gray-500">
                        {{ listing.agent ? 'Agent' : 'Vendeur' }}
                      </p>
                    </div>
                  </div>
                </div>

                <UButton
                  label="Contacter"
                  color="primary"
                  size="lg"
                  block
                  icon="i-lucide-message-circle"
                  @click="showContactModal = true"
                />

                <div v-if="listing.agent?.phone || listing.owner?.phone" class="mt-4">
                  <UButton
                    :label="listing.agent?.phone || listing.owner?.phone || undefined"
                    variant="outline"
                    color="neutral"
                    size="lg"
                    block
                    icon="i-lucide-phone"
                  />
                </div>
              </template>

              <template v-else>
                <p class="text-center text-gray-500 mb-4">
                  Connectez-vous pour contacter le vendeur
                </p>
                <UButton
                  label="Se connecter"
                  color="primary"
                  size="lg"
                  block
                  to="/login"
                />
              </template>
            </div>

            <!-- Map -->
            <div v-if="hasPhotos && (selectedPolygon || (listingLat != null && listingLng != null))"
                 class="bg-white rounded-xl overflow-hidden shadow-sm">
              <div class="h-48 bg-gray-200 flex items-center justify-center">
                <ClientOnly>
                  <CasablancaSettatMap
                    :zoom="14"
                    height="100%"
                    :show-legend="false"
                    :show-controls="false"
                    :fit-to-region="false"
                    :markers="listingMarkers"
                    :fit-to-markers="!selectedPolygon"
                    :selected-geojson-polygon="selectedPolygon"
                    :fit-to-selected-geojson-polygon="true"
                  />
                </ClientOnly>
              </div>
            </div>
          </div>
        </div>
      </UContainer>
    </template>

    <!-- Contact Modal -->
    <UModal v-model:open="showContactModal">
      <template #content>
        <div class="p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Contacter le vendeur</h3>

          <div v-if="contactSuccess" class="text-center py-8">
            <UIcon name="i-lucide-check-circle" class="w-16 h-16 mx-auto text-green-500 mb-4"/>
            <p class="text-gray-600">Votre message a été envoyé avec succès !</p>
          </div>

          <ThemeAForm v-else :state="contactForm" @submit="submitContact">
            <div class="space-y-4">
              <UFormField label="Nom" name="name">
                <UInput v-model="contactForm.name" required size="md" class="w-full"/>
              </UFormField>

              <UFormField label="Email" name="email">
                <UInput v-model="contactForm.email" type="email" required size="md" class="w-full"/>
              </UFormField>

              <UFormField label="Téléphone" name="phone">
                <UInput v-model="contactForm.phone" type="tel" size="md" class="w-full"/>
              </UFormField>

              <UFormField label="Message" name="message">
                <UTextarea
                  v-model="contactForm.message"
                  :rows="4"
                  placeholder="Bonjour, je suis intéressé par ce terrain..."
                  required
                  class="w-full"
                />
              </UFormField>

              <div class="flex gap-3 justify-end pt-4">
                <UButton
                  label="Annuler"
                  variant="ghost"
                  color="neutral"
                  @click="showContactModal = false"
                />
                <UButton
                  label="Envoyer"
                  color="primary"
                  type="submit"
                  :loading="contactPending"
                />
              </div>
            </div>
          </ThemeAForm>
        </div>
      </template>
    </UModal>
  </main>
</template>
