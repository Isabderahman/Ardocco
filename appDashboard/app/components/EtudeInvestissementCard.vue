<script setup lang="ts">
import type { EtudeInvestissement } from '~/types/models/listing'

const props = defineProps<{
  etude: EtudeInvestissement
  showActions?: boolean
  compact?: boolean
  canEdit?: boolean
  pdfLoading?: boolean
}>()

const emit = defineEmits<{
  (e: 'download-pdf'): void
  (e: 'generate-pdf'): void
  (e: 'edit'): void
}>()

const formatNumber = (value: number | string | null | undefined): string => {
  if (value == null) return '-'
  const num = typeof value === 'string' ? parseFloat(value) : value
  if (isNaN(num)) return '-'
  return new Intl.NumberFormat('fr-MA').format(num)
}

const formatCurrency = (value: number | string | null | undefined): string => {
  if (value == null) return '-'
  const num = typeof value === 'string' ? parseFloat(value) : value
  if (isNaN(num)) return '-'
  return new Intl.NumberFormat('fr-MA', {
    style: 'currency',
    currency: 'MAD',
    maximumFractionDigits: 0
  }).format(num)
}

const formatPercent = (value: number | string | null | undefined): string => {
  if (value == null) return '-'
  const num = typeof value === 'string' ? parseFloat(value) : value
  if (isNaN(num)) return '-'
  return `${num.toFixed(1)}%`
}

const statusBadge = computed(() => {
  const map: Record<string, { label: string; color: string }> = {
    draft: { label: 'Brouillon', color: 'neutral' },
    pending_review: { label: 'En attente', color: 'warning' },
    approved: { label: 'Approuve', color: 'success' },
    rejected: { label: 'Rejete', color: 'error' }
  }
  return map[props.etude.status] || { label: props.etude.status, color: 'neutral' }
})

const isPositiveRatio = computed(() => {
  const ratio = props.etude.ratio
  return ratio != null && ratio > 0
})
</script>

<template>
  <div class="bg-elevated rounded-xl overflow-hidden">
    <!-- Header -->
    <div class="bg-primary px-6 py-4 text-white">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold">{{ etude.titre_projet || 'Business Plan' }}</h3>
          <p class="text-primary-200 text-sm">
            {{ etude.type_projet || 'Projet Immobilier' }} - {{ etude.localisation || '-' }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <UBadge v-if="etude.generated_by_ai" color="info" variant="soft">
            <UIcon name="i-lucide-sparkles" class="size-3 mr-1" />
            IA
          </UBadge>
          <UBadge :color="statusBadge.color as any" variant="soft">
            {{ statusBadge.label }}
          </UBadge>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="p-6 space-y-6">
      <!-- Key metrics -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="text-center p-4 bg-default rounded-lg">
          <p class="text-xs text-dimmed uppercase tracking-wider">Investissement</p>
          <p class="text-lg font-bold text-highlighted">{{ formatCurrency(etude.total_investissement) }}</p>
        </div>
        <div class="text-center p-4 bg-default rounded-lg">
          <p class="text-xs text-dimmed uppercase tracking-wider">Chiffre d'affaires</p>
          <p class="text-lg font-bold text-highlighted">{{ formatCurrency(etude.total_revenues) }}</p>
        </div>
        <div class="text-center p-4 bg-default rounded-lg">
          <p class="text-xs text-dimmed uppercase tracking-wider">Resultat Brut</p>
          <p class="text-lg font-bold" :class="isPositiveRatio ? 'text-success' : 'text-error'">
            {{ formatCurrency(etude.resultat_brute) }}
          </p>
        </div>
        <div class="text-center p-4 bg-default rounded-lg">
          <p class="text-xs text-dimmed uppercase tracking-wider">Ratio</p>
          <p class="text-lg font-bold" :class="isPositiveRatio ? 'text-success' : 'text-error'">
            {{ formatPercent(etude.ratio) }}
          </p>
        </div>
      </div>

      <!-- Detailed breakdown (when not compact) -->
      <template v-if="!compact">
        <!-- Terrain Table -->
        <div class="border-t border-default pt-4">
          <h4 class="font-semibold text-highlighted mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-map" class="size-4 text-primary" />
            Terrain
          </h4>
          <table class="w-full text-sm">
            <tbody>
              <tr class="border-b border-default">
                <td class="py-2 text-muted">Superficie</td>
                <td class="py-2 text-right font-medium text-highlighted">{{ formatNumber(etude.superficie_terrain) }} m2</td>
              </tr>
              <tr class="border-b border-default">
                <td class="py-2 text-muted">Prix/m2</td>
                <td class="py-2 text-right font-medium text-highlighted">{{ formatCurrency(etude.prix_terrain_m2) }}</td>
              </tr>
              <tr class="border-b border-default">
                <td class="py-2 text-muted">Prix total terrain</td>
                <td class="py-2 text-right font-medium text-highlighted">{{ formatCurrency(etude.prix_terrain_total) }}</td>
              </tr>
              <tr>
                <td class="py-2 text-muted">Frais immatriculation</td>
                <td class="py-2 text-right font-medium text-highlighted">{{ formatCurrency(etude.frais_immatriculation) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Construction Table -->
        <div class="border-t border-default pt-4">
          <h4 class="font-semibold text-highlighted mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-building" class="size-4 text-primary" />
            Construction
          </h4>
          <table class="w-full text-sm">
            <tbody>
              <tr class="border-b border-default">
                <td class="py-2 text-muted">Surface plancher total</td>
                <td class="py-2 text-right font-medium text-highlighted">{{ formatNumber(etude.surface_plancher_total) }} m2</td>
              </tr>
              <tr class="border-b border-default">
                <td class="py-2 text-muted">Cout gros oeuvres/m2</td>
                <td class="py-2 text-right font-medium text-highlighted">{{ formatCurrency(etude.cout_gros_oeuvres_m2) }}</td>
              </tr>
              <tr class="border-b border-default">
                <td class="py-2 text-muted">Cout finition/m2</td>
                <td class="py-2 text-right font-medium text-highlighted">{{ formatCurrency(etude.cout_finition_m2) }}</td>
              </tr>
              <tr class="border-b border-default">
                <td class="py-2 text-muted">Amenagement divers</td>
                <td class="py-2 text-right font-medium text-highlighted">{{ formatCurrency(etude.amenagement_divers) }}</td>
              </tr>
              <tr>
                <td class="py-2 text-muted font-semibold">Cout total travaux</td>
                <td class="py-2 text-right font-bold text-highlighted">{{ formatCurrency(etude.cout_total_travaux) }}</td>
              </tr>
            </tbody>
          </table>

          <!-- Surfaces par niveau -->
          <div v-if="etude.surfaces_par_niveau && Object.keys(etude.surfaces_par_niveau).length" class="mt-4 p-4 bg-default rounded-lg">
            <p class="text-xs text-dimmed uppercase tracking-wider mb-3">Surfaces par niveau</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
              <div
                v-for="(surface, niveau) in etude.surfaces_par_niveau"
                :key="niveau"
                class="flex justify-between text-sm"
              >
                <span class="text-muted capitalize">{{ String(niveau).replace('_', ' ') }}</span>
                <span class="font-medium text-highlighted">{{ formatNumber(surface) }} m2</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Frais Table -->
        <div class="border-t border-default pt-4">
          <h4 class="font-semibold text-highlighted mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-receipt" class="size-4 text-primary" />
            Frais
          </h4>
          <table class="w-full text-sm">
            <tbody>
              <tr class="border-b border-default">
                <td class="py-2 text-muted">Groupement etudes</td>
                <td class="py-2 text-right font-medium text-highlighted">{{ formatCurrency(etude.frais_groupement_etudes) }}</td>
              </tr>
              <tr class="border-b border-default">
                <td class="py-2 text-muted">Autorisation + Eclatement</td>
                <td class="py-2 text-right font-medium text-highlighted">{{ formatCurrency(etude.frais_autorisation_eclatement) }}</td>
              </tr>
              <tr class="border-b border-default">
                <td class="py-2 text-muted">Lydec</td>
                <td class="py-2 text-right font-medium text-highlighted">{{ formatCurrency(etude.frais_lydec) }}</td>
              </tr>
              <tr>
                <td class="py-2 text-muted font-semibold">Total frais construction</td>
                <td class="py-2 text-right font-bold text-highlighted">{{ formatCurrency(etude.total_frais_construction) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Revenus Table -->
        <div class="border-t border-default pt-4">
          <h4 class="font-semibold text-highlighted mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-banknote" class="size-4 text-primary" />
            Revenus Estimes
          </h4>
          <table class="w-full text-sm">
            <tbody>
              <tr v-if="etude.surface_vendable_commerce && Number(etude.surface_vendable_commerce) > 0" class="border-b border-default">
                <td class="py-2 text-muted">Surface commerce</td>
                <td class="py-2 text-right font-medium text-highlighted">{{ formatNumber(etude.surface_vendable_commerce) }} m2</td>
              </tr>
              <tr v-if="etude.prix_vente_m2_commerce && Number(etude.prix_vente_m2_commerce) > 0" class="border-b border-default">
                <td class="py-2 text-muted">Prix/m2 commerce</td>
                <td class="py-2 text-right font-medium text-highlighted">{{ formatCurrency(etude.prix_vente_m2_commerce) }}</td>
              </tr>
              <tr v-if="etude.revenus_commerce && Number(etude.revenus_commerce) > 0" class="border-b border-default">
                <td class="py-2 text-muted">Revenus commerce</td>
                <td class="py-2 text-right font-medium text-success">{{ formatCurrency(etude.revenus_commerce) }}</td>
              </tr>
              <tr class="border-b border-default">
                <td class="py-2 text-muted">Surface appartements</td>
                <td class="py-2 text-right font-medium text-highlighted">{{ formatNumber(etude.surface_vendable_appart) }} m2</td>
              </tr>
              <tr class="border-b border-default">
                <td class="py-2 text-muted">Prix/m2 appartements</td>
                <td class="py-2 text-right font-medium text-highlighted">{{ formatCurrency(etude.prix_vente_m2_appart) }}</td>
              </tr>
              <tr class="border-b border-default">
                <td class="py-2 text-muted">Revenus appartements</td>
                <td class="py-2 text-right font-medium text-success">{{ formatCurrency(etude.revenus_appart) }}</td>
              </tr>
              <tr>
                <td class="py-2 text-muted font-semibold">Total revenus</td>
                <td class="py-2 text-right font-bold text-success">{{ formatCurrency(etude.total_revenues) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Resultat Final -->
        <div class="border-t border-default pt-4">
          <h4 class="font-semibold text-highlighted mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-calculator" class="size-4 text-primary" />
            Resultat
          </h4>
          <table class="w-full text-sm">
            <tbody>
              <tr class="border-b border-default">
                <td class="py-2 text-muted">Total investissement</td>
                <td class="py-2 text-right font-medium text-highlighted">{{ formatCurrency(etude.total_investissement) }}</td>
              </tr>
              <tr class="border-b border-default">
                <td class="py-2 text-muted">Total revenus</td>
                <td class="py-2 text-right font-medium text-success">{{ formatCurrency(etude.total_revenues) }}</td>
              </tr>
              <tr class="border-b border-default bg-default">
                <td class="py-3 px-2 font-semibold text-highlighted">Resultat brut</td>
                <td class="py-3 px-2 text-right font-bold text-xl" :class="isPositiveRatio ? 'text-success' : 'text-error'">
                  {{ formatCurrency(etude.resultat_brute) }}
                </td>
              </tr>
              <tr class="bg-default">
                <td class="py-3 px-2 font-semibold text-highlighted">Ratio rentabilite</td>
                <td class="py-3 px-2 text-right font-bold text-xl" :class="isPositiveRatio ? 'text-success' : 'text-error'">
                  {{ formatPercent(etude.ratio) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- AI Notes -->
        <div v-if="etude.ai_notes" class="border-t border-default pt-4">
          <h4 class="font-semibold text-highlighted mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-lightbulb" class="size-4 text-info" />
            Recommandations IA
          </h4>
          <div class="p-4 bg-info/10 rounded-lg text-sm text-info whitespace-pre-line">
            {{ etude.ai_notes }}
          </div>
        </div>
      </template>

      <!-- Actions -->
      <div v-if="showActions" class="border-t border-default pt-4 flex flex-wrap gap-3">
        <UButton
          v-if="etude.pdf_path"
          label="Telecharger PDF"
          icon="i-lucide-download"
          color="success"
          @click="emit('download-pdf')"
        />
        <UButton
          v-else
          label="Generer PDF"
          icon="i-lucide-file-text"
          color="primary"
          :loading="pdfLoading"
          @click="emit('generate-pdf')"
        />
        <UButton
          v-if="canEdit"
          label="Modifier"
          icon="i-lucide-edit"
          variant="outline"
          @click="emit('edit')"
        />
      </div>
    </div>

    <!-- Footer -->
    <div class="bg-default px-6 py-3 text-xs text-muted flex items-center justify-between">
      <span>Version {{ etude.version || '-' }}</span>
      <span v-if="etude.reviewed_at">
        Approuve le {{ new Date(etude.reviewed_at).toLocaleDateString('fr-FR') }}
      </span>
    </div>
  </div>
</template>
