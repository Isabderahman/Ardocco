<script setup lang="ts">
import type { BackendListing } from '~/types/models/listing'
import { agentService } from '~/services/agentService'

definePageMeta({
  layout: 'dashboard',
  title: 'Espace Agent',
  middleware: 'agent'
})

const { token } = useAuth()
const toast = useToast()

const listings = ref<BackendListing[]>([])
const loading = ref(true)
const actionLoading = ref<string | null>(null)

const stats = reactive({
  total: 0,
  soumis: 0,
  valide: 0,
  enRevision: 0
})

async function fetchListings() {
  if (!token.value) {
    loading.value = false
    return
  }

  loading.value = true
  try {
    const res = await agentService.fetchListings({ status: 'soumis,en_revision,valide' }, token.value)
    if (res.success && res.data) {
      listings.value = res.data.data || []
      stats.total = listings.value.length
      stats.soumis = listings.value.filter(l => l.status === 'soumis').length
      stats.valide = listings.value.filter(l => l.status === 'valide').length
      stats.enRevision = listings.value.filter(l => l.status === 'en_revision').length
    }
  } catch (err) {
    console.error('Failed to fetch listings:', err)
  } finally {
    loading.value = false
  }
}

async function handleApprove(listing: BackendListing) {
  if (!token.value) return

  actionLoading.value = listing.id
  try {
    const res = await agentService.approveListing(listing.id, token.value)
    if (res.success) {
      toast.add({ title: 'Annonce validee', color: 'success' })
      await fetchListings()
    }
  } catch (err) {
    toast.add({ title: 'Erreur lors de la validation', color: 'error' })
  } finally {
    actionLoading.value = null
  }
}

async function handlePublish(listing: BackendListing) {
  if (!token.value) return

  actionLoading.value = listing.id
  try {
    const res = await agentService.publishListing(listing.id, token.value)
    if (res.success) {
      toast.add({ title: 'Annonce publiee', color: 'success' })
      await fetchListings()
    }
  } catch (err) {
    toast.add({ title: 'Erreur lors de la publication', color: 'error' })
  } finally {
    actionLoading.value = null
  }
}

async function handleReject(listing: BackendListing) {
  const reason = prompt('Raison du refus:')
  if (!reason || !token.value) return

  actionLoading.value = listing.id
  try {
    const res = await agentService.rejectListing(listing.id, reason, token.value)
    if (res.success) {
      toast.add({ title: 'Annonce refusee', color: 'warning' })
      await fetchListings()
    }
  } catch (err) {
    toast.add({ title: 'Erreur lors du refus', color: 'error' })
  } finally {
    actionLoading.value = null
  }
}

async function handleRequestRevision(listing: BackendListing) {
  const message = prompt('Message de revision:')
  if (!message || !token.value) return

  actionLoading.value = listing.id
  try {
    const res = await agentService.requestRevision(listing.id, message, token.value)
    if (res.success) {
      toast.add({ title: 'Revision demandee', color: 'info' })
      await fetchListings()
    }
  } catch (err) {
    toast.add({ title: 'Erreur', color: 'error' })
  } finally {
    actionLoading.value = null
  }
}

function formatPrice(price: number | string | null | undefined): string {
  if (price == null) return 'Prix non defini'
  const num = typeof price === 'string' ? parseFloat(price) : price
  if (isNaN(num)) return 'Prix non defini'
  return new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD', maximumFractionDigits: 0 }).format(num)
}

function formatDate(date: string | null | undefined): string {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

function getStatusBadge(status: string | null | undefined): { label: string, color: string } {
  const map: Record<string, { label: string, color: string }> = {
    soumis: { label: 'Soumis', color: 'bg-blue-100 text-blue-700' },
    en_revision: { label: 'En revision', color: 'bg-yellow-100 text-yellow-700' },
    valide: { label: 'Valide', color: 'bg-green-100 text-green-700' },
    publie: { label: 'Publie', color: 'bg-emerald-100 text-emerald-700' },
    refuse: { label: 'Refuse', color: 'bg-red-100 text-red-700' }
  }
  return map[status || ''] || { label: status || '', color: 'bg-gray-100 text-gray-700' }
}

watch(token, (newToken) => {
  if (newToken) {
    fetchListings()
  }
}, { immediate: true })
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Espace Agent</h1>
        <p class="text-gray-500 mt-1">Validez et publiez les annonces</p>
      </div>
      <UButton
        label="Actualiser"
        variant="outline"
        icon="i-lucide-refresh-cw"
        @click="fetchListings()"
      />
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
      <ThemeAStatCard
        label="Total"
        :value="String(stats.total)"
        icon="i-lucide-layout-list"
      />
      <ThemeAStatCard
        label="Soumis"
        :value="String(stats.soumis)"
        icon="i-lucide-clock"
      />
      <ThemeAStatCard
        label="En revision"
        :value="String(stats.enRevision)"
        icon="i-lucide-edit"
      />
      <ThemeAStatCard
        label="Valides"
        :value="String(stats.valide)"
        icon="i-lucide-check-circle"
      />
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin text-primary-500" />
    </div>

    <!-- Empty -->
    <div v-else-if="!listings.length" class="text-center py-12">
      <UIcon name="i-lucide-inbox" class="w-12 h-12 mx-auto text-gray-300 mb-4" />
      <p class="text-gray-600">Aucune annonce en attente</p>
    </div>

    <!-- Listings -->
    <div v-else class="space-y-4">
      <div
        v-for="listing in listings"
        :key="listing.id"
        class="bg-white rounded-xl p-6 shadow-sm"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <span class="text-sm text-gray-500">{{ listing.reference }}</span>
              <span
                class="text-xs px-2 py-0.5 rounded-full"
                :class="getStatusBadge(listing.status).color"
              >
                {{ getStatusBadge(listing.status).label }}
              </span>
              <span
                v-if="listing.type_terrain"
                class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600"
              >
                {{ listing.type_terrain }}
              </span>
            </div>

            <h3 class="text-lg font-semibold text-gray-900">{{ listing.title }}</h3>
            <p class="text-gray-500 mt-1">
              {{ listing.quartier || listing.commune?.name_fr || 'Emplacement non defini' }}
            </p>

            <div class="flex items-center gap-6 mt-4 text-sm">
              <span class="text-primary-600 font-semibold">
                {{ formatPrice(listing.prix_demande) }}
              </span>
              <span class="text-gray-500">
                {{ listing.superficie }} m²
              </span>
              <span class="text-gray-500">
                Soumis le {{ formatDate(listing.submitted_at || listing.created_at) }}
              </span>
            </div>

            <div v-if="listing.owner" class="mt-3 text-sm text-gray-500">
              Proprietaire: {{ listing.owner.first_name }} {{ listing.owner.last_name }}
            </div>
          </div>

          <div class="flex flex-col gap-2">
            <NuxtLink :to="`/terrains/${listing.id}`">
              <UButton label="Voir" variant="outline" size="sm" icon="i-lucide-eye" />
            </NuxtLink>

            <template v-if="listing.status === 'soumis'">
              <UButton
                label="Valider"
                color="primary"
                size="sm"
                icon="i-lucide-check"
                :loading="actionLoading === listing.id"
                @click="handleApprove(listing)"
              />
              <UButton
                label="Revision"
                color="warning"
                variant="outline"
                size="sm"
                icon="i-lucide-edit"
                :loading="actionLoading === listing.id"
                @click="handleRequestRevision(listing)"
              />
              <UButton
                label="Refuser"
                color="error"
                variant="outline"
                size="sm"
                icon="i-lucide-x"
                :loading="actionLoading === listing.id"
                @click="handleReject(listing)"
              />
            </template>

            <template v-else-if="listing.status === 'valide'">
              <UButton
                label="Publier"
                color="primary"
                size="sm"
                icon="i-lucide-globe"
                :loading="actionLoading === listing.id"
                @click="handlePublish(listing)"
              />
            </template>

            <template v-else-if="listing.status === 'en_revision'">
              <UButton
                label="Refuser"
                color="error"
                variant="outline"
                size="sm"
                icon="i-lucide-x"
                :loading="actionLoading === listing.id"
                @click="handleReject(listing)"
              />
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
