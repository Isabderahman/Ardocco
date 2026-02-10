<script setup lang="ts">
import type { BackendListing } from '~/types/models/listing'
import { listingService } from '~/services/listingService'

definePageMeta({
  layout: 'dashboard',
  title: 'Mes terrains',
  middleware: 'auth'
})

const { token } = useAuth()

const listings = ref<BackendListing[]>([])
const loading = ref(true)
const activeTab = ref<'all' | 'brouillon' | 'soumis' | 'publie' | 'refuse'>('all')

const tabs = [
  { key: 'all', label: 'Tous', icon: 'i-lucide-layout-grid' },
  { key: 'brouillon', label: 'Brouillons', icon: 'i-lucide-file-edit' },
  { key: 'soumis', label: 'En attente', icon: 'i-lucide-clock' },
  { key: 'publie', label: 'Publies', icon: 'i-lucide-check-circle' },
  { key: 'refuse', label: 'Refuses', icon: 'i-lucide-x-circle' }
] as const

async function fetchListings() {
  loading.value = true
  try {
    const res = await listingService.fetchMyListings({}, token.value)
    if (res.success && res.data) {
      listings.value = res.data.data || []
    }
  } catch (err) {
    console.error('Failed to fetch listings:', err)
  } finally {
    loading.value = false
  }
}

const filteredListings = computed(() => {
  if (activeTab.value === 'all') return listings.value
  if (activeTab.value === 'soumis') {
    return listings.value.filter(l => ['soumis', 'en_revision'].includes(String(l.status || '')))
  }
  return listings.value.filter(l => String(l.status || '') === activeTab.value)
})

const tabCounts = computed(() => ({
  all: listings.value.length,
  brouillon: listings.value.filter(l => String(l.status || '') === 'brouillon').length,
  soumis: listings.value.filter(l => ['soumis', 'en_revision'].includes(String(l.status || ''))).length,
  publie: listings.value.filter(l => String(l.status || '') === 'publie').length,
  refuse: listings.value.filter(l => String(l.status || '') === 'refuse').length
}))

function formatPrice(price: number | string | null | undefined): string {
  if (price == null) return 'Prix non defini'
  const num = typeof price === 'string' ? parseFloat(price) : price
  if (isNaN(num)) return 'Prix non defini'
  return new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD', maximumFractionDigits: 0 }).format(num)
}

function formatArea(area: number | string | null | undefined): string {
  if (area == null) return '-'
  const num = typeof area === 'string' ? parseFloat(area) : area
  if (isNaN(num)) return '-'
  return `${num.toLocaleString('fr-MA')} m²`
}

function getLocation(listing: BackendListing): string {
  const parts: string[] = []
  if (listing.quartier) parts.push(listing.quartier)
  if (listing.commune?.name_fr) parts.push(listing.commune.name_fr)
  if (listing.commune?.province?.name_fr) parts.push(listing.commune.province.name_fr)
  return parts.slice(0, 2).join(', ') || 'Emplacement non defini'
}

function getStatusInfo(status: string | null | undefined): { label: string; color: string; description: string } {
  const statusMap: Record<string, { label: string; color: string; description: string }> = {
    brouillon: { label: 'Brouillon', color: 'neutral', description: 'Non soumis pour validation' },
    soumis: { label: 'En attente', color: 'warning', description: 'En attente de validation par l\'admin' },
    en_revision: { label: 'En revision', color: 'info', description: 'Des modifications sont demandees' },
    valide: { label: 'Valide', color: 'success', description: 'Valide, en attente de publication' },
    publie: { label: 'Publie', color: 'success', description: 'Visible par tous les visiteurs' },
    vendu: { label: 'Vendu', color: 'neutral', description: 'Transaction finalisee' },
    refuse: { label: 'Refuse', color: 'error', description: 'Refuse par l\'administrateur' }
  }
  return statusMap[status || ''] || { label: status || '', color: 'neutral', description: '' }
}

function formatDate(date: string | null | undefined): string {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

function canSubmit(listing: BackendListing): boolean {
  return ['brouillon', 'refuse', 'en_revision'].includes(String(listing.status || ''))
}

function canEdit(listing: BackendListing): boolean {
  return ['brouillon', 'refuse', 'en_revision'].includes(String(listing.status || ''))
}

const submitLoading = ref<string | null>(null)

async function submitListing(listing: BackendListing) {
  submitLoading.value = listing.id
  try {
    const res = await listingService.submitListing(listing.id, token.value)
    if (res.success) {
      // Update the listing status locally
      const index = listings.value.findIndex(l => l.id === listing.id)
      if (index !== -1) {
        listings.value[index] = { ...listings.value[index], status: 'soumis' }
      }
    }
  } catch (err) {
    console.error('Failed to submit listing:', err)
  } finally {
    submitLoading.value = null
  }
}

onMounted(() => {
  fetchListings()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-highlighted">Mes terrains</h1>
        <p class="text-muted mt-1">Gerez toutes vos annonces de terrains</p>
      </div>
      <UButton
        to="/terrains/new"
        label="Ajouter un terrain"
        color="primary"
        icon="i-lucide-plus"
      />
    </div>

    <!-- Tabs -->
    <div class="flex flex-wrap gap-2 border-b border-default pb-4">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
        :class="activeTab === tab.key
          ? 'bg-primary text-white'
          : 'text-muted hover:text-highlighted hover:bg-elevated'"
        @click="activeTab = tab.key"
      >
        <UIcon :name="tab.icon" class="size-4" />
        <span>{{ tab.label }}</span>
        <span
          v-if="tabCounts[tab.key] > 0"
          class="px-1.5 py-0.5 text-xs rounded-full"
          :class="activeTab === tab.key ? 'bg-white/20' : 'bg-muted/50'"
        >
          {{ tabCounts[tab.key] }}
        </span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="bg-elevated rounded-xl p-6 animate-pulse">
        <div class="flex gap-4">
          <div class="w-24 h-24 bg-muted rounded-lg" />
          <div class="flex-1 space-y-3">
            <div class="h-5 bg-muted rounded w-1/3" />
            <div class="h-4 bg-muted rounded w-1/2" />
            <div class="h-4 bg-muted rounded w-1/4" />
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredListings.length === 0" class="bg-elevated rounded-xl p-12 text-center">
      <UIcon name="i-lucide-map" class="size-16 text-muted mx-auto mb-4" />
      <h3 class="text-xl font-semibold text-highlighted mb-2">
        {{ activeTab === 'all' ? 'Aucun terrain' : 'Aucun terrain dans cette categorie' }}
      </h3>
      <p class="text-muted mb-4">
        {{ activeTab === 'all'
          ? 'Vous n\'avez pas encore ajoute de terrain. Commencez par en creer un.'
          : 'Aucun terrain ne correspond a ce filtre.'
        }}
      </p>
      <UButton
        v-if="activeTab === 'all'"
        to="/terrains/new"
        label="Ajouter mon premier terrain"
        color="primary"
        icon="i-lucide-plus"
      />
    </div>

    <!-- Listings -->
    <div v-else class="space-y-4">
      <div
        v-for="listing in filteredListings"
        :key="listing.id"
        class="bg-elevated rounded-xl p-5 border border-default hover:border-primary/30 transition-colors"
      >
        <div class="flex flex-col sm:flex-row gap-4">
          <!-- Thumbnail -->
          <div class="w-full sm:w-32 h-24 bg-muted rounded-lg overflow-hidden shrink-0 flex items-center justify-center">
            <UIcon name="i-lucide-map" class="size-8 text-dimmed" />
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-3 mb-2">
              <div class="min-w-0">
                <h3 class="text-base font-semibold text-highlighted truncate">{{ listing.title }}</h3>
                <p class="text-sm text-muted">{{ listing.reference }}</p>
              </div>
              <UBadge
                :color="getStatusInfo(listing.status).color as any"
                variant="soft"
                class="shrink-0"
              >
                {{ getStatusInfo(listing.status).label }}
              </UBadge>
            </div>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted mb-3">
              <span class="flex items-center gap-1">
                <UIcon name="i-lucide-map-pin" class="size-3.5" />
                {{ getLocation(listing) }}
              </span>
              <span class="font-medium text-highlighted">{{ formatPrice(listing.prix_demande) }}</span>
              <span>{{ formatArea(listing.superficie) }}</span>
              <span class="text-dimmed">Cree le {{ formatDate(listing.created_at) }}</span>
            </div>

            <!-- Status Description for refused -->
            <p
              v-if="listing.status === 'refuse'"
              class="text-sm text-error mb-3 flex items-center gap-2"
            >
              <UIcon name="i-lucide-alert-circle" class="size-4" />
              Cette annonce a ete refusee. Modifiez-la et soumettez-la a nouveau.
            </p>

            <!-- Actions -->
            <div class="flex flex-wrap items-center gap-2">
              <NuxtLink :to="`/terrains/${listing.id}`">
                <UButton
                  label="Voir"
                  color="neutral"
                  variant="outline"
                  size="xs"
                  icon="i-lucide-eye"
                />
              </NuxtLink>
              <NuxtLink v-if="canEdit(listing)" :to="`/terrains/${listing.id}/edit`">
                <UButton
                  label="Modifier"
                  color="neutral"
                  variant="outline"
                  size="xs"
                  icon="i-lucide-pencil"
                />
              </NuxtLink>
              <UButton
                v-if="canSubmit(listing)"
                label="Soumettre"
                color="primary"
                size="xs"
                icon="i-lucide-send"
                :loading="submitLoading === listing.id"
                @click="submitListing(listing)"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
