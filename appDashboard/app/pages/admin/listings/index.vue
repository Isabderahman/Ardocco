<script setup lang="ts">
import type { BackendListing } from '~/types/models/listing'
import { adminService } from '~/services/adminService'

definePageMeta({
  layout: 'dashboard',
  title: 'Gestion des annonces',
  middleware: 'admin'
})

const { token } = useAuth()

const listings = ref<BackendListing[]>([])
const loading = ref(true)
const activeTab = ref<'pending' | 'all'>('pending')
const actionLoading = ref<string | null>(null)
const rejectModal = ref(false)
const selectedListing = ref<BackendListing | null>(null)
const rejectReason = ref('')

async function fetchListings() {
  loading.value = true
  try {
    const res = await adminService.fetchPendingListings({}, token.value)
    if (res.success && res.data) {
      listings.value = res.data.data || []
    }
  } catch (err) {
    console.error('Failed to fetch listings:', err)
  } finally {
    loading.value = false
  }
}

async function approveListing(listing: BackendListing) {
  actionLoading.value = listing.id
  try {
    await adminService.approveListing(listing.id, token.value)
    listings.value = listings.value.filter(l => l.id !== listing.id)
  } catch (err) {
    console.error('Failed to approve listing:', err)
  } finally {
    actionLoading.value = null
  }
}

function openRejectModal(listing: BackendListing) {
  selectedListing.value = listing
  rejectReason.value = ''
  rejectModal.value = true
}

async function confirmReject() {
  if (!selectedListing.value || !rejectReason.value.trim()) return

  actionLoading.value = selectedListing.value.id
  try {
    await adminService.rejectListing(selectedListing.value.id, rejectReason.value, token.value)
    listings.value = listings.value.filter(l => l.id !== selectedListing.value?.id)
    rejectModal.value = false
    selectedListing.value = null
    rejectReason.value = ''
  } catch (err) {
    console.error('Failed to reject listing:', err)
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

function formatDate(date: string | null | undefined): string {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

onMounted(() => {
  fetchListings()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-highlighted">Gestion des annonces</h1>
        <p class="text-muted mt-1">Examinez et approuvez les nouvelles annonces</p>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="bg-elevated rounded-xl p-6 animate-pulse">
        <div class="flex gap-4">
          <div class="w-32 h-24 bg-muted rounded-lg" />
          <div class="flex-1 space-y-3">
            <div class="h-5 bg-muted rounded w-1/3" />
            <div class="h-4 bg-muted rounded w-1/2" />
            <div class="h-4 bg-muted rounded w-1/4" />
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="listings.length === 0" class="bg-elevated rounded-xl p-12 text-center">
      <UIcon name="i-lucide-check-circle" class="size-16 text-green-500 mx-auto mb-4" />
      <h3 class="text-xl font-semibold text-highlighted mb-2">Aucune annonce en attente</h3>
      <p class="text-muted">Toutes les annonces ont ete traitees.</p>
    </div>

    <!-- Listings List -->
    <div v-else class="space-y-4">
      <div
        v-for="listing in listings"
        :key="listing.id"
        class="bg-elevated rounded-xl p-6 border border-default hover:border-primary/30 transition-colors"
      >
        <div class="flex flex-col lg:flex-row gap-6">
          <!-- Thumbnail -->
          <div class="w-full lg:w-48 h-32 bg-muted rounded-lg overflow-hidden shrink-0">
            <div class="w-full h-full flex items-center justify-center">
              <UIcon name="i-lucide-map" class="size-8 text-dimmed" />
            </div>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-4 mb-3">
              <div>
                <h3 class="text-lg font-semibold text-highlighted">{{ listing.title }}</h3>
                <p class="text-sm text-muted">{{ listing.reference }}</p>
              </div>
              <UBadge color="warning" variant="soft">En attente</UBadge>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
              <div>
                <p class="text-xs text-dimmed uppercase tracking-wider">Localisation</p>
                <p class="text-sm text-highlighted">{{ getLocation(listing) }}</p>
              </div>
              <div>
                <p class="text-xs text-dimmed uppercase tracking-wider">Prix</p>
                <p class="text-sm font-semibold text-highlighted">{{ formatPrice(listing.prix_demande) }}</p>
              </div>
              <div>
                <p class="text-xs text-dimmed uppercase tracking-wider">Superficie</p>
                <p class="text-sm text-highlighted">{{ formatArea(listing.superficie) || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-dimmed uppercase tracking-wider">Soumis le</p>
                <p class="text-sm text-highlighted">{{ formatDate(listing.submitted_at) }}</p>
              </div>
            </div>

            <!-- Owner Info -->
            <div class="flex items-center gap-2 mb-4 text-sm text-muted">
              <UIcon name="i-lucide-user" class="size-4" />
              <span>{{ listing.owner?.first_name }} {{ listing.owner?.last_name }}</span>
              <span class="text-dimmed">-</span>
              <span>{{ listing.owner?.email }}</span>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3">
              <NuxtLink :to="`/admin/listings/${listing.id}`">
                <UButton
                  label="Examiner"
                  color="neutral"
                  variant="outline"
                  size="sm"
                  icon="i-lucide-eye"
                />
              </NuxtLink>
              <UButton
                label="Approuver"
                color="primary"
                size="sm"
                icon="i-lucide-check"
                :loading="actionLoading === listing.id"
                @click="approveListing(listing)"
              />
              <UButton
                label="Refuser"
                color="error"
                variant="soft"
                size="sm"
                icon="i-lucide-x"
                :loading="actionLoading === listing.id"
                @click="openRejectModal(listing)"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reject Modal -->
    <UModal v-model:open="rejectModal">
      <template #content>
        <UCard>
          <template #header>
            <div class="flex items-center gap-3">
              <div class="p-2 bg-error/10 rounded-full">
                <UIcon name="i-lucide-alert-triangle" class="size-5 text-error" />
              </div>
              <div>
                <h3 class="font-semibold text-highlighted">Refuser l'annonce</h3>
                <p class="text-sm text-muted">{{ selectedListing?.title }}</p>
              </div>
            </div>
          </template>

          <div class="space-y-4">
            <p class="text-sm text-muted">
              Veuillez indiquer la raison du refus. Cette information sera communiquee au vendeur.
            </p>
            <UTextarea
              v-model="rejectReason"
              placeholder="Raison du refus..."
              :rows="4"
              autofocus
            />
          </div>

          <template #footer>
            <div class="flex justify-end gap-3">
              <UButton
                label="Annuler"
                color="neutral"
                variant="ghost"
                @click="rejectModal = false"
              />
              <UButton
                label="Confirmer le refus"
                color="error"
                :disabled="!rejectReason.trim()"
                :loading="actionLoading === selectedListing?.id"
                @click="confirmReject"
              />
            </div>
          </template>
        </UCard>
      </template>
    </UModal>
  </div>
</template>
