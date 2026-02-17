<script setup lang="ts">
import type { BackendListing, EtudeInvestissement } from '~/types/models/listing'
import { listingService } from '~/services/listingService'
import { etudeService } from '~/services/etudeService'

definePageMeta({
  layout: 'dashboard',
  title: 'Detail Terrain',
  middleware: 'auth'
})

const route = useRoute()
const { token, ensureUserLoaded } = useAuth()
const { isAdmin, isAgent, isVendeur } = useAccess()
const toast = useToast()

const listingId = computed(() => route.params.id as string)
const listing = ref<BackendListing | null>(null)
const etudes = ref<EtudeInvestissement[]>([])
const loading = ref(true)
const pdfLoading = ref(false)

const latestEtude = computed(() => {
  if (!etudes.value.length) return null
  // Prefer approved, then pending_review, then draft
  const approved = etudes.value.find(e => e.status === 'approved')
  if (approved) return approved
  const pending = etudes.value.find(e => e.status === 'pending_review')
  if (pending) return pending
  return etudes.value[0]
})

const canEditEtude = computed(() => isAdmin.value || isAgent.value)
const canGeneratePdf = computed(() => {
  if (!latestEtude.value) return false
  return isAdmin.value || isAgent.value || latestEtude.value.status === 'approved'
})

async function fetchListing() {
  try {
    const res = await $fetch<{ success: boolean; data: BackendListing }>(`/api/backend/listings/${listingId.value}`, {
      headers: token.value ? { Authorization: `Bearer ${token.value}` } : undefined
    })
    if (res.success) {
      listing.value = res.data
    }
  } catch (err) {
    console.error('Failed to fetch listing:', err)
    toast.add({ title: 'Erreur lors du chargement', color: 'error' })
  }
}

async function fetchEtudes() {
  try {
    const res = await etudeService.fetchEtudes(listingId.value, token.value)
    if (res.success && res.data) {
      etudes.value = res.data
    }
  } catch (err) {
    console.error('Failed to fetch etudes:', err)
  }
}

async function generatePdf() {
  if (!latestEtude.value) return

  pdfLoading.value = true
  try {
    const res = await etudeService.generatePdf(listingId.value, latestEtude.value.id, token.value)
    if (res.success) {
      toast.add({ title: 'PDF genere avec succes', color: 'success' })
      await fetchEtudes()
    }
  } catch (err) {
    console.error('Failed to generate PDF:', err)
    toast.add({ title: 'Erreur lors de la generation du PDF', color: 'error' })
  } finally {
    pdfLoading.value = false
  }
}

async function downloadPdf() {
  if (!latestEtude.value?.pdf_path) {
    toast.add({ title: 'Veuillez d\'abord generer le PDF', color: 'warning' })
    return
  }

  window.open(etudeService.downloadPdfUrl(listingId.value, latestEtude.value.id), '_blank')
}

function formatPrice(price: number | string | null | undefined): string {
  if (price == null) return 'Non defini'
  const num = typeof price === 'string' ? parseFloat(price) : price
  if (isNaN(num)) return 'Non defini'
  return new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD', maximumFractionDigits: 0 }).format(num)
}

function formatArea(area: number | string | null | undefined): string {
  if (area == null) return '-'
  const num = typeof area === 'string' ? parseFloat(area) : area
  if (isNaN(num)) return '-'
  return `${num.toLocaleString('fr-MA')} m\u00B2`
}

function formatPercent(value: number | string | null | undefined): string {
  if (value == null) return '-'
  const num = typeof value === 'string' ? parseFloat(value) : value
  if (isNaN(num)) return '-'
  return `${num.toFixed(1)}%`
}

function getLocation(): string {
  if (!listing.value) return '-'
  const parts: string[] = []
  if (listing.value.quartier) parts.push(listing.value.quartier)
  if (listing.value.commune?.name_fr) parts.push(listing.value.commune.name_fr)
  if (listing.value.commune?.province?.name_fr) parts.push(listing.value.commune.province.name_fr)
  return parts.join(', ') || '-'
}

function getStatusInfo(status: string | null | undefined): { label: string; color: string } {
  const map: Record<string, { label: string; color: string }> = {
    brouillon: { label: 'Brouillon', color: 'neutral' },
    soumis: { label: 'En attente', color: 'warning' },
    en_revision: { label: 'En revision', color: 'info' },
    valide: { label: 'Valide', color: 'success' },
    publie: { label: 'Publie', color: 'success' },
    refuse: { label: 'Refuse', color: 'error' }
  }
  return map[status || ''] || { label: status || '-', color: 'neutral' }
}

function getEtudeStatusInfo(status: string | null | undefined): { label: string; color: string } {
  const map: Record<string, { label: string; color: string }> = {
    draft: { label: 'Brouillon', color: 'neutral' },
    pending_review: { label: 'En attente', color: 'warning' },
    approved: { label: 'Approuvee', color: 'success' },
    rejected: { label: 'Rejetee', color: 'error' }
  }
  return map[status || ''] || { label: status || '-', color: 'neutral' }
}

function getPhotoUrl(doc: { file_path?: string }): string {
  const path = String(doc?.file_path || '').replace(/^\/+/, '')
  return path ? `/storage/${path}` : ''
}

onMounted(async () => {
  loading.value = true
  // Ensure user data is loaded first so canEditEtude has correct values
  await ensureUserLoaded()
  await Promise.all([fetchListing(), fetchEtudes()])
  loading.value = false
})
</script>

<template>
  <div class="space-y-6">
    <!-- Loading State -->
    <div v-if="loading" class="space-y-6">
      <div class="bg-elevated rounded-xl p-6 animate-pulse">
        <div class="h-8 bg-muted rounded w-1/3 mb-4" />
        <div class="h-4 bg-muted rounded w-1/2" />
      </div>
    </div>

    <!-- Content -->
    <template v-else-if="listing">
      <!-- Header -->
      <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <h1 class="text-2xl font-bold text-highlighted">{{ listing.title }}</h1>
            <UBadge :color="getStatusInfo(listing.status).color as any" variant="soft">
              {{ getStatusInfo(listing.status).label }}
            </UBadge>
          </div>
          <p class="text-muted">{{ listing.reference }}</p>
        </div>
        <div class="flex items-center gap-2">
          <UButton
            v-if="canEditEtude"
            label="Modifier"
            variant="outline"
            icon="i-lucide-pencil"
            :to="`/terrains/${listingId}/edit`"
          />
          <UButton
            v-if="canEditEtude && latestEtude"
            label="Editer Etude"
            color="primary"
            icon="i-lucide-calculator"
            :to="`/terrains/${listingId}/etude`"
          />
          <UButton
            v-if="latestEtude?.pdf_path"
            label="Telecharger PDF"
            color="success"
            icon="i-lucide-download"
            @click="downloadPdf"
          />
        </div>
      </div>

      <div class="grid lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Photos -->
          <div v-if="listing.documents?.length" class="bg-elevated rounded-xl p-6">
            <h2 class="text-lg font-semibold text-highlighted mb-4">Photos</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
              <div
                v-for="(doc, index) in (listing.documents || []).filter((d: any) => d.document_type === 'photos').slice(0, 6)"
                :key="index"
                class="aspect-square bg-muted rounded-lg overflow-hidden"
              >
                <img
                  :src="getPhotoUrl(doc as { file_path?: string })"
                  :alt="`Photo ${index + 1}`"
                  class="w-full h-full object-cover"
                />
              </div>
            </div>
          </div>

          <!-- Description -->
          <div v-if="listing.description" class="bg-elevated rounded-xl p-6">
            <h2 class="text-lg font-semibold text-highlighted mb-4">Description</h2>
            <p class="text-muted whitespace-pre-line">{{ listing.description }}</p>
          </div>

          <!-- Technical Info -->
          <div class="bg-elevated rounded-xl p-6">
            <h2 class="text-lg font-semibold text-highlighted mb-4">Informations Techniques</h2>
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <p class="text-xs text-dimmed uppercase tracking-wider">Superficie</p>
                <p class="text-sm font-medium text-highlighted">{{ formatArea(listing.superficie) }}</p>
              </div>
              <div>
                <p class="text-xs text-dimmed uppercase tracking-wider">Type de terrain</p>
                <p class="text-sm font-medium text-highlighted capitalize">{{ listing.type_terrain || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-dimmed uppercase tracking-wider">Perimetre</p>
                <p class="text-sm font-medium text-highlighted capitalize">{{ listing.zonage || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-dimmed uppercase tracking-wider">Titre Foncier</p>
                <p class="text-sm font-medium text-highlighted">{{ listing.titre_foncier || 'Non' }}</p>
              </div>
              <div v-if="listing.coefficient_occupation">
                <p class="text-xs text-dimmed uppercase tracking-wider">COS</p>
                <p class="text-sm font-medium text-highlighted">{{ listing.coefficient_occupation }}</p>
              </div>
              <div v-if="listing.hauteur_max">
                <p class="text-xs text-dimmed uppercase tracking-wider">Hauteur max</p>
                <p class="text-sm font-medium text-highlighted">{{ listing.hauteur_max }}m</p>
              </div>
            </div>
          </div>

          <!-- Investment Study -->
          <EtudeInvestissementCard
            v-if="latestEtude"
            :etude="latestEtude"
            :show-actions="true"
            :can-edit="canEditEtude"
            :pdf-loading="pdfLoading"
            @download-pdf="downloadPdf"
            @generate-pdf="generatePdf"
            @edit="navigateTo(`/terrains/${listingId}/etude`)"
          />

          <!-- No Etude Yet -->
          <div v-else class="bg-elevated rounded-xl p-6">
            <div class="text-center py-8">
              <UIcon name="i-lucide-calculator" class="size-12 text-muted mx-auto mb-4" />
              <h3 class="text-lg font-semibold text-highlighted mb-2">Pas d'etude d'investissement</h3>
              <p class="text-muted mb-4">
                L'etude d'investissement sera generee automatiquement ou peut etre creee manuellement.
              </p>
              <UButton
                v-if="canEditEtude"
                label="Creer une etude"
                color="primary"
                icon="i-lucide-plus"
                :to="`/terrains/${listingId}/etude`"
              />
            </div>
          </div>

          <!-- Financial Analysis -->
          <FicheFinanciereCard
            v-if="listing.ficheFinanciere"
            :fiche="listing.ficheFinanciere"
          />

          <!-- No Financial Analysis Yet -->
          <div v-else-if="isAdmin || isAgent" class="bg-elevated rounded-xl p-6">
            <div class="text-center py-6">
              <UIcon name="i-lucide-chart-line" class="size-10 text-muted mx-auto mb-3" />
              <h3 class="text-base font-semibold text-highlighted mb-2">Analyse financiere en attente</h3>
              <p class="text-sm text-muted">
                L'analyse financiere sera generee par un expert ou l'IA.
              </p>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Price Card -->
          <div class="bg-elevated rounded-xl p-6">
            <p class="text-xs text-dimmed uppercase tracking-wider mb-1">Prix demande</p>
            <p class="text-3xl font-bold text-primary mb-2">{{ formatPrice(listing.prix_demande) }}</p>
            <p class="text-sm text-muted">{{ formatPrice(listing.prix_par_m2) }}/m\u00B2</p>
          </div>

          <!-- Location Card -->
          <div class="bg-elevated rounded-xl p-6">
            <h3 class="text-sm font-semibold text-highlighted mb-3">Localisation</h3>
            <div class="flex items-start gap-2 text-sm text-muted">
              <UIcon name="i-lucide-map-pin" class="size-4 shrink-0 mt-0.5" />
              <span>{{ getLocation() }}</span>
            </div>
            <div v-if="listing.address" class="flex items-start gap-2 text-sm text-muted mt-2">
              <UIcon name="i-lucide-home" class="size-4 shrink-0 mt-0.5" />
              <span>{{ listing.address }}</span>
            </div>
          </div>

          <!-- Owner Card (for admin/agent) -->
          <div v-if="(isAdmin || isAgent) && listing.owner" class="bg-elevated rounded-xl p-6">
            <h3 class="text-sm font-semibold text-highlighted mb-3">Proprietaire</h3>
            <div class="space-y-2 text-sm">
              <div class="flex items-center gap-2">
                <UIcon name="i-lucide-user" class="size-4 text-muted" />
                <span class="text-highlighted">{{ listing.owner.first_name }} {{ listing.owner.last_name }}</span>
              </div>
              <div v-if="listing.owner.email" class="flex items-center gap-2">
                <UIcon name="i-lucide-mail" class="size-4 text-muted" />
                <span class="text-muted">{{ listing.owner.email }}</span>
              </div>
              <div v-if="listing.owner.phone" class="flex items-center gap-2">
                <UIcon name="i-lucide-phone" class="size-4 text-muted" />
                <span class="text-muted">{{ listing.owner.phone }}</span>
              </div>
            </div>
          </div>

          <!-- PDF Download Card -->
          <div v-if="latestEtude" class="bg-primary/10 border border-primary/20 rounded-xl p-6">
            <h3 class="text-sm font-semibold text-highlighted mb-3">Business Plan PDF</h3>
            <div v-if="latestEtude.pdf_path" class="space-y-3">
              <p class="text-sm text-muted">Le PDF de l'etude est disponible.</p>
              <UButton
                label="Telecharger PDF"
                color="primary"
                icon="i-lucide-download"
                class="w-full"
                @click="downloadPdf"
              />
            </div>
            <div v-else class="space-y-3">
              <p class="text-sm text-muted">Generez le PDF pour telecharger le business plan.</p>
              <UButton
                v-if="canGeneratePdf"
                label="Generer PDF"
                color="primary"
                icon="i-lucide-file-text"
                class="w-full"
                :loading="pdfLoading"
                @click="generatePdf"
              />
            </div>
          </div>

          <!-- Quick Stats -->
          <div v-if="latestEtude" class="bg-elevated rounded-xl p-6">
            <h3 class="text-sm font-semibold text-highlighted mb-3">Indicateurs Cles</h3>
            <div class="space-y-3">
              <div>
                <div class="flex justify-between text-sm mb-1">
                  <span class="text-muted">Ratio</span>
                  <span
                    class="font-semibold"
                    :class="(latestEtude.ratio ?? 0) > 0 ? 'text-success' : 'text-error'"
                  >
                    {{ formatPercent(latestEtude.ratio) }}
                  </span>
                </div>
                <div class="h-2 bg-muted rounded-full overflow-hidden">
                  <div
                    class="h-full rounded-full"
                    :class="(latestEtude.ratio ?? 0) > 0 ? 'bg-success' : 'bg-error'"
                    :style="{ width: `${Math.min(Math.abs(latestEtude.ratio ?? 0), 100)}%` }"
                  />
                </div>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-muted">Cout travaux</span>
                <span class="text-highlighted">{{ formatPrice(latestEtude.cout_total_travaux) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-muted">Frais construction</span>
                <span class="text-highlighted">{{ formatPrice(latestEtude.total_frais_construction) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Not Found -->
    <div v-else class="bg-elevated rounded-xl p-12 text-center">
      <UIcon name="i-lucide-alert-circle" class="size-16 text-muted mx-auto mb-4" />
      <h3 class="text-xl font-semibold text-highlighted mb-2">Terrain non trouve</h3>
      <p class="text-muted mb-4">Ce terrain n'existe pas ou vous n'avez pas les permissions pour le voir.</p>
      <UButton label="Retour au dashboard" color="primary" to="/dashboard" />
    </div>
  </div>
</template>
