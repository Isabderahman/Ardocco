<script setup lang="ts">
import type { BackendListing } from '~/types/models/listing'
import { adminService } from '~/services/adminService'

definePageMeta({
  layout: 'dashboard',
  title: 'Examiner l\'annonce',
  middleware: 'admin'
})

const route = useRoute()
const router = useRouter()
const { token } = useAuth()

const listing = ref<BackendListing | null>(null)
const loading = ref(true)
const actionLoading = ref(false)
const rejectModal = ref(false)
const rejectReason = ref('')

const listingId = computed(() => route.params.id as string)

async function fetchListing() {
  loading.value = true
  try {
    const res = await adminService.fetchListing(listingId.value, token.value) as { success: boolean; data: BackendListing }
    if (res.success && res.data) {
      listing.value = res.data
    }
  } catch (err) {
    console.error('Failed to fetch listing:', err)
  } finally {
    loading.value = false
  }
}

async function approveListing() {
  if (!listing.value) return
  actionLoading.value = true
  try {
    await adminService.approveListing(listing.value.id, token.value)
    router.push('/admin/listings')
  } catch (err) {
    console.error('Failed to approve listing:', err)
  } finally {
    actionLoading.value = false
  }
}

async function confirmReject() {
  if (!listing.value || !rejectReason.value.trim()) return
  actionLoading.value = true
  try {
    await adminService.rejectListing(listing.value.id, rejectReason.value, token.value)
    router.push('/admin/listings')
  } catch (err) {
    console.error('Failed to reject listing:', err)
  } finally {
    actionLoading.value = false
    rejectModal.value = false
  }
}

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

function getLocation(l: BackendListing): string {
  const parts: string[] = []
  if (l.quartier) parts.push(l.quartier)
  if (l.commune?.name_fr) parts.push(l.commune.name_fr)
  if (l.commune?.province?.name_fr) parts.push(l.commune.province.name_fr)
  return parts.join(', ') || 'Emplacement non defini'
}

function formatDate(date: string | null | undefined): string {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function getStatusBadge(status: string | null | undefined): { label: string; color: string } {
  const statusMap: Record<string, { label: string; color: string }> = {
    brouillon: { label: 'Brouillon', color: 'neutral' },
    soumis: { label: 'En attente', color: 'warning' },
    en_revision: { label: 'En revision', color: 'info' },
    valide: { label: 'Valide', color: 'success' },
    publie: { label: 'Publie', color: 'success' },
    vendu: { label: 'Vendu', color: 'neutral' },
    refuse: { label: 'Refuse', color: 'error' }
  }
  return statusMap[status || ''] || { label: status || '', color: 'neutral' }
}

function getTypeLabel(type: string | null | undefined): string {
  const typeMap: Record<string, string> = {
    residentiel: 'Residentiel',
    commercial: 'Commercial',
    industriel: 'Industriel',
    agricole: 'Agricole',
    mixte: 'Mixte'
  }
  return typeMap[type || ''] || type || '-'
}

onMounted(() => {
  fetchListing()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Back Button -->
    <div>
      <NuxtLink to="/admin/listings" class="inline-flex items-center gap-2 text-muted hover:text-highlighted transition-colors">
        <UIcon name="i-lucide-arrow-left" class="size-4" />
        <span>Retour aux annonces</span>
      </NuxtLink>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-6">
      <div class="bg-elevated rounded-xl p-8 animate-pulse">
        <div class="h-8 bg-muted rounded w-1/3 mb-4" />
        <div class="h-4 bg-muted rounded w-1/2 mb-8" />
        <div class="grid md:grid-cols-2 gap-6">
          <div class="space-y-4">
            <div class="h-4 bg-muted rounded w-full" />
            <div class="h-4 bg-muted rounded w-3/4" />
            <div class="h-4 bg-muted rounded w-1/2" />
          </div>
          <div class="h-64 bg-muted rounded-lg" />
        </div>
      </div>
    </div>

    <!-- Content -->
    <template v-else-if="listing">
      <!-- Header -->
      <div class="bg-elevated rounded-xl p-6">
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
          <div>
            <div class="flex items-center gap-3 mb-2">
              <h1 class="text-2xl font-bold text-highlighted">{{ listing.title }}</h1>
              <UBadge :color="getStatusBadge(listing.status).color as any" variant="soft">
                {{ getStatusBadge(listing.status).label }}
              </UBadge>
            </div>
            <p class="text-muted">{{ listing.reference }}</p>
          </div>

          <!-- Actions -->
          <div v-if="listing.status === 'soumis'" class="flex items-center gap-3">
            <UButton
              label="Approuver et publier"
              color="primary"
              icon="i-lucide-check"
              :loading="actionLoading"
              @click="approveListing"
            />
            <UButton
              label="Refuser"
              color="error"
              variant="soft"
              icon="i-lucide-x"
              @click="rejectModal = true"
            />
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid lg:grid-cols-3 gap-6">
        <!-- Left Column: Details -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Description -->
          <ThemeACard title="Description">
            <p class="text-muted whitespace-pre-wrap">{{ listing.description || 'Aucune description fournie.' }}</p>
          </ThemeACard>

          <!-- Details Grid -->
          <ThemeACard title="Caracteristiques">
            <div class="grid sm:grid-cols-2 gap-6">
              <div class="space-y-4">
                <div>
                  <p class="text-xs text-dimmed uppercase tracking-wider mb-1">Type de terrain</p>
                  <p class="text-highlighted font-medium">{{ getTypeLabel(listing.type_terrain) }}</p>
                </div>
                <div>
                  <p class="text-xs text-dimmed uppercase tracking-wider mb-1">Superficie</p>
                  <p class="text-highlighted font-medium">{{ formatArea(listing.superficie) }}</p>
                </div>
                <div>
                  <p class="text-xs text-dimmed uppercase tracking-wider mb-1">Prix demande</p>
                  <p class="text-highlighted font-medium text-lg">{{ formatPrice(listing.prix_demande) }}</p>
                </div>
                <div v-if="listing.prix_par_m2">
                  <p class="text-xs text-dimmed uppercase tracking-wider mb-1">Prix au m²</p>
                  <p class="text-highlighted font-medium">{{ formatPrice(listing.prix_par_m2) }}/m²</p>
                </div>
              </div>

              <div class="space-y-4">
                <div>
                  <p class="text-xs text-dimmed uppercase tracking-wider mb-1">Localisation</p>
                  <p class="text-highlighted font-medium">{{ getLocation(listing) }}</p>
                </div>
                <div v-if="listing.address">
                  <p class="text-xs text-dimmed uppercase tracking-wider mb-1">Adresse</p>
                  <p class="text-highlighted">{{ listing.address }}</p>
                </div>
                <div v-if="listing.titre_foncier">
                  <p class="text-xs text-dimmed uppercase tracking-wider mb-1">Titre foncier</p>
                  <p class="text-highlighted">{{ listing.titre_foncier }}</p>
                </div>
                <div v-if="listing.zonage">
                  <p class="text-xs text-dimmed uppercase tracking-wider mb-1">Zonage</p>
                  <p class="text-highlighted">{{ listing.zonage }}</p>
                </div>
              </div>
            </div>
          </ThemeACard>

          <!-- Map -->
          <ThemeACard v-if="listing.latitude && listing.longitude" title="Localisation sur la carte">
            <div class="h-64 rounded-lg overflow-hidden">
              <MiniListingMap
                :id="listing.id"
                :lat="typeof listing.latitude === 'number' ? listing.latitude : parseFloat(listing.latitude)"
                :lng="typeof listing.longitude === 'number' ? listing.longitude : parseFloat(listing.longitude)"
                :geojson-polygon="listing.geojson_polygon || null"
              />
            </div>
          </ThemeACard>
        </div>

        <!-- Right Column: Owner & Timeline -->
        <div class="space-y-6">
          <!-- Owner Info -->
          <ThemeACard title="Proprietaire">
            <div class="space-y-4">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
                  <UIcon name="i-lucide-user" class="size-6 text-primary" />
                </div>
                <div>
                  <p class="font-medium text-highlighted">
                    {{ listing.owner?.first_name }} {{ listing.owner?.last_name }}
                  </p>
                  <p class="text-sm text-muted">Vendeur</p>
                </div>
              </div>

              <div class="space-y-2 pt-2 border-t border-default">
                <div class="flex items-center gap-2 text-sm">
                  <UIcon name="i-lucide-mail" class="size-4 text-muted" />
                  <span class="text-highlighted">{{ listing.owner?.email || '-' }}</span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                  <UIcon name="i-lucide-phone" class="size-4 text-muted" />
                  <span class="text-highlighted">{{ listing.owner?.phone || '-' }}</span>
                </div>
              </div>
            </div>
          </ThemeACard>

          <!-- Timeline -->
          <ThemeACard title="Historique">
            <div class="space-y-4">
              <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-muted flex items-center justify-center shrink-0">
                  <UIcon name="i-lucide-plus" class="size-4 text-dimmed" />
                </div>
                <div>
                  <p class="text-sm font-medium text-highlighted">Cree</p>
                  <p class="text-xs text-muted">{{ formatDate(listing.created_at) }}</p>
                </div>
              </div>

              <div v-if="listing.submitted_at" class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-warning/10 flex items-center justify-center shrink-0">
                  <UIcon name="i-lucide-send" class="size-4 text-warning" />
                </div>
                <div>
                  <p class="text-sm font-medium text-highlighted">Soumis pour validation</p>
                  <p class="text-xs text-muted">{{ formatDate(listing.submitted_at) }}</p>
                </div>
              </div>

              <div v-if="listing.validated_at" class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-success/10 flex items-center justify-center shrink-0">
                  <UIcon name="i-lucide-check" class="size-4 text-success" />
                </div>
                <div>
                  <p class="text-sm font-medium text-highlighted">Valide</p>
                  <p class="text-xs text-muted">{{ formatDate(listing.validated_at) }}</p>
                </div>
              </div>

              <div v-if="listing.published_at" class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                  <UIcon name="i-lucide-globe" class="size-4 text-primary" />
                </div>
                <div>
                  <p class="text-sm font-medium text-highlighted">Publie</p>
                  <p class="text-xs text-muted">{{ formatDate(listing.published_at) }}</p>
                </div>
              </div>
            </div>
          </ThemeACard>

          <!-- Quick Stats -->
          <ThemeACard title="Statistiques">
            <div class="grid grid-cols-2 gap-4">
              <div class="text-center p-3 bg-default rounded-lg">
                <p class="text-2xl font-bold text-highlighted">{{ listing.views_count || 0 }}</p>
                <p class="text-xs text-muted">Vues</p>
              </div>
              <div class="text-center p-3 bg-default rounded-lg">
                <p class="text-2xl font-bold text-highlighted">{{ (listing.documents as any[])?.length || 0 }}</p>
                <p class="text-xs text-muted">Documents</p>
              </div>
            </div>
          </ThemeACard>
        </div>
      </div>
    </template>

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
                <p class="text-sm text-muted">{{ listing?.title }}</p>
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
                :loading="actionLoading"
                @click="confirmReject"
              />
            </div>
          </template>
        </UCard>
      </template>
    </UModal>
  </div>
</template>
