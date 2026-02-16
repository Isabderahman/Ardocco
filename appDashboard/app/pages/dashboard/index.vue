<script setup lang="ts">
import type { BackendListing } from '~/types/models/listing'
import { listingService } from '~/services/listingService'

definePageMeta({
  layout: 'dashboard',
  title: 'Dashboard',
  middleware: 'auth'
})

const { token } = useAuth()
const { isAdmin, isAgent, isVendeur } = useAccess()

const listings = ref<BackendListing[]>([])
const loading = ref(true)
const stats = reactive({
  total: 0,
  pending: 0,
  published: 0,
  draft: 0,
  refused: 0,
  validated: 0
})

// Role-based labels
const dashboardTitle = computed(() => {
  if (isAdmin.value) return 'Tous les terrains'
  if (isAgent.value) return 'Tous les terrains'
  return 'Mes terrains'
})

const statsLabel = computed(() => {
  if (isAdmin.value || isAgent.value) return 'Total annonces'
  return 'Mes annonces'
})

const emptyMessage = computed(() => {
  if (isAdmin.value || isAgent.value) return 'Aucun terrain dans le systeme.'
  return 'Vous n\'avez pas encore ajoute de terrain. Commencez par en creer un.'
})

const canAddTerrain = computed(() => isAdmin.value || isAgent.value || isVendeur.value)

async function fetchListings() {
  if (!token.value) {
    loading.value = false
    return
  }

  loading.value = true
  try {
    const res = await listingService.fetchMyListings({}, token.value)
    if (res.success && res.data) {
      listings.value = res.data.data || []
      stats.total = res.data.total || listings.value.length

      // Calculate stats from listings
      stats.pending = listings.value.filter(l => ['soumis', 'en_revision'].includes(String(l.status || ''))).length
      stats.published = listings.value.filter(l => String(l.status || '') === 'publie').length
      stats.draft = listings.value.filter(l => String(l.status || '') === 'brouillon').length
      stats.refused = listings.value.filter(l => String(l.status || '') === 'refuse').length
      stats.validated = listings.value.filter(l => String(l.status || '') === 'valide').length
    }
  } catch (err) {
    console.error('Failed to fetch listings:', err)
  } finally {
    loading.value = false
  }
}

watch(token, (newToken) => {
  if (newToken) {
    fetchListings()
  }
}, { immediate: true })

function formatPrice(price: number | string | null | undefined): string {
  if (price == null) return 'Prix non defini'
  const num = typeof price === 'string' ? parseFloat(price) : price
  if (isNaN(num)) return 'Prix non defini'
  return new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD', maximumFractionDigits: 0 }).format(num)
}

function formatArea(area: number | string | null | undefined): string {
  if (area == null) return ''
  const num = typeof area === 'string' ? parseFloat(area) : area
  if (isNaN(num)) return ''
  return `${num.toLocaleString('fr-MA')} m²`
}

function getLocation(listing: BackendListing): string {
  const parts: string[] = []
  if (listing.quartier) parts.push(listing.quartier)
  if (listing.commune?.name_fr) parts.push(listing.commune.name_fr)
  if (listing.commune?.province?.name_fr) parts.push(listing.commune.province.name_fr)
  return parts.slice(0, 2).join(', ') || 'Emplacement non defini'
}

function getStatusBadge(status: string | null | undefined): string {
  const statusMap: Record<string, string> = {
    brouillon: 'Brouillon',
    soumis: 'Soumis',
    en_revision: 'En revision',
    valide: 'Valide',
    publie: 'Publie',
    vendu: 'Vendu',
    refuse: 'Refuse'
  }
  return statusMap[status || ''] || status || ''
}

function coverPhotoUrl(listing: BackendListing): string | null {
  const docs = listing.documents
  if (!Array.isArray(docs)) return null

  const photo = docs.find((doc) => {
    const obj = doc as { document_type?: unknown, file_path?: unknown }
    return obj?.document_type === 'photos' && typeof obj.file_path === 'string'
  }) as { file_path?: string } | undefined

  const path = String(photo?.file_path || '').replace(/^\/+/, '')
  return path ? `/storage/${path}` : null
}
</script>

<template>
  <div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
      <ThemeAStatCard
        :label="statsLabel"
        :value="String(stats.total)"
        icon="i-lucide-layout-grid"
      />
      <ThemeAStatCard
        label="En attente"
        :value="String(stats.pending).padStart(2, '0')"
        icon="i-lucide-clock-3"
      />
      <ThemeAStatCard
        label="Publiees"
        :value="String(stats.published).padStart(2, '0')"
        icon="i-lucide-check-circle"
      />
      <ThemeAStatCard
        label="Brouillons"
        :value="String(stats.draft).padStart(2, '0')"
        icon="i-lucide-file-edit"
      />
      <ThemeAStatCard
        v-if="stats.refused > 0"
        label="Refusees"
        :value="String(stats.refused).padStart(2, '0')"
        icon="i-lucide-x-circle"
      />
      <ThemeAStatCard
        v-if="(isAdmin || isAgent) && stats.validated > 0"
        label="Validees"
        :value="String(stats.validated).padStart(2, '0')"
        icon="i-lucide-badge-check"
      />
    </div>

    <!-- Refused Listings Alert (only for vendeur) -->
    <div v-if="isVendeur && stats.refused > 0" class="border-2 border-error rounded-xl p-4 boxshadow-sm">
      <div class="flex items-start gap-3">
        <UIcon name="i-lucide-alert-triangle" class="size-10 text-error shrink-0 mt-0.5" />
        <div>
          <p class="font-medium text-highlighted">{{ stats.refused }} annonce(s) refusee(s)</p>
          <p class="text-sm text-muted mt-1">
            Certaines de vos annonces ont ete refusees par l'administrateur. Consultez les details et corrigez les problemes pour les soumettre a nouveau.
          </p>
        </div>
      </div>
    </div>

    <!-- Quick Actions for Admin/Agent -->
    <div v-if="isAdmin || isAgent" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <NuxtLink v-if="isAdmin" to="/admin/users" class="block">
        <div class="bg-elevated rounded-xl p-5 border border-default hover:border-primary/30 transition-colors">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-primary/10 rounded-lg">
              <UIcon name="i-lucide-users" class="size-5 text-primary" />
            </div>
            <div>
              <p class="font-medium text-highlighted">Gerer les utilisateurs</p>
              <p class="text-sm text-muted">Voir et modifier les comptes</p>
            </div>
          </div>
        </div>
      </NuxtLink>
      <NuxtLink to="/agent" class="block">
        <div class="bg-elevated rounded-xl p-5 border border-default hover:border-primary/30 transition-colors">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-warning/10 rounded-lg">
              <UIcon name="i-lucide-clipboard-check" class="size-5 text-warning" />
            </div>
            <div>
              <p class="font-medium text-highlighted">Valider les annonces</p>
              <p class="text-sm text-muted">{{ stats.pending }} en attente</p>
            </div>
          </div>
        </div>
      </NuxtLink>
      <NuxtLink v-if="isAdmin" to="/admin/listings" class="block">
        <div class="bg-elevated rounded-xl p-5 border border-default hover:border-primary/30 transition-colors">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-success/10 rounded-lg">
              <UIcon name="i-lucide-layout-list" class="size-5 text-success" />
            </div>
            <div>
              <p class="font-medium text-highlighted">Annonces soumises</p>
              <p class="text-sm text-muted">Examiner et approuver</p>
            </div>
          </div>
        </div>
      </NuxtLink>
    </div>

    <!-- Featured Terrains Section -->
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-highlighted">
          {{ dashboardTitle }}
        </h2>
        <UButton
          v-if="canAddTerrain"
          to="/terrains/new"
          label="Ajouter un terrain"
          color="primary"
          size="sm"
          icon="i-lucide-plus"
        />
      </div>

      <!-- Loading State -->
      <div
        v-if="loading"
        class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
      >
        <div
          v-for="i in 3"
          :key="i"
          class="bg-elevated rounded-xl animate-pulse"
        >
          <div class="aspect-[3/3] bg-muted" />
          <div class="p-4 space-y-3">
            <div class="h-4 bg-muted rounded w-3/4" />
            <div class="h-3 bg-muted rounded w-1/2" />
            <div class="h-4 bg-muted rounded w-1/3" />
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="listings.length === 0"
        class="bg-elevated rounded-xl p-8 text-center"
      >
        <UIcon
          name="i-lucide-map"
          class="size-12 text-muted mx-auto mb-4"
        />
        <h3 class="text-lg font-medium text-highlighted mb-2">
          Aucun terrain
        </h3>
        <p class="text-muted mb-4">
          {{ emptyMessage }}
        </p>
        <UButton
          v-if="canAddTerrain && isVendeur"
          to="/terrains/new"
          label="Ajouter mon premier terrain"
          color="primary"
          icon="i-lucide-plus"
        />
      </div>

      <!-- Listings Grid -->
      <div
        v-else
        class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
      >
        <ThemeATerrainCard
          v-for="listing in listings.slice(0, 6)"
          :key="listing.id"
          :title="listing.title"
          :to="`/terrains/${listing.id}`"
          :price="formatPrice(listing.prix_demande)"
          :location="getLocation(listing)"
          :badge="getStatusBadge(listing.status)"
          :area="formatArea(listing.superficie)"
          :image-url="coverPhotoUrl(listing)"
          :lat="typeof listing.latitude === 'number' ? listing.latitude : (listing.latitude ? parseFloat(listing.latitude) : null)"
          :lng="typeof listing.longitude === 'number' ? listing.longitude : (listing.longitude ? parseFloat(listing.longitude) : null)"
          :geojson-polygon="listing.geojson_polygon"
        />
      </div>

      <!-- View All Button -->
      <div
        v-if="listings.length > 6"
        class="text-center pt-2"
      >
        <UButton
          to="/dashboard/terrains"
          label="Voir tous les terrains"
          variant="outline"
          color="neutral"
        />
      </div>
    </div>
  </div>
</template>
