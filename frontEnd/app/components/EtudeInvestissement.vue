<script setup lang="ts">
interface EtudeData {
  id: string
  titre_projet: string | null
  type_projet: string | null
  localisation: string | null
  version: string | null
  status: 'draft' | 'pending_review' | 'approved' | 'rejected'
  generated_by_ai: boolean
  superficie_terrain: number | null
  prix_terrain_m2: number | null
  prix_terrain_total: number | null
  frais_immatriculation: number | null
  surface_plancher_total: number | null
  surfaces_par_niveau: Record<string, number> | null
  cout_gros_oeuvres_m2: number | null
  cout_finition_m2: number | null
  amenagement_divers: number | null
  cout_total_travaux: number | null
  frais_groupement_etudes: number | null
  frais_autorisation_eclatement: number | null
  frais_lydec: number | null
  total_frais_construction: number | null
  total_investissement: number | null
  surface_vendable_commerce: number | null
  surface_vendable_appart: number | null
  prix_vente_m2_commerce: number | null
  prix_vente_m2_appart: number | null
  revenus_commerce: number | null
  revenus_appart: number | null
  total_revenues: number | null
  resultat_brute: number | null
  ratio: number | null
  ai_notes: string | null
  pdf_path: string | null
  reviewed_at: string | null
}

const props = defineProps<{
  etude: EtudeData
  showActions?: boolean
  compact?: boolean
}>()

const emit = defineEmits<{
  (e: 'download-pdf'): void
  (e: 'regenerate'): void
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

const statusBadge = computed(() => {
  const map: Record<string, { label: string; color: string }> = {
    draft: { label: 'Brouillon', color: 'bg-gray-100 text-gray-700' },
    pending_review: { label: 'En attente', color: 'bg-yellow-100 text-yellow-700' },
    approved: { label: 'Approuvé', color: 'bg-green-100 text-green-700' },
    rejected: { label: 'Rejeté', color: 'bg-red-100 text-red-700' }
  }
  return map[props.etude.status] || { label: props.etude.status, color: 'bg-gray-100 text-gray-700' }
})

const isPositiveRatio = computed(() => {
  const ratio = props.etude.ratio
  return ratio != null && ratio > 0
})
</script>

<template>
  <div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-4 text-white">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold">Business Plan</h3>
          <p class="text-primary-100 text-sm">
            {{ etude.type_projet || 'Projet Immobilier' }} - {{ etude.localisation || '-' }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <span
            v-if="etude.generated_by_ai"
            class="text-xs px-2 py-1 rounded-full bg-white/20 text-white"
          >
            <UIcon name="i-lucide-sparkles" class="w-3 h-3 inline mr-1" />
            IA
          </span>
          <span
            class="text-xs px-2 py-1 rounded-full"
            :class="statusBadge.color"
          >
            {{ statusBadge.label }}
          </span>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="p-6 space-y-6">
      <!-- Key metrics -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="text-center p-3 bg-gray-50 rounded-lg">
          <p class="text-xs text-gray-500 uppercase">Investissement</p>
          <p class="text-lg font-bold text-gray-900">{{ formatCurrency(etude.total_investissement) }}</p>
        </div>
        <div class="text-center p-3 bg-gray-50 rounded-lg">
          <p class="text-xs text-gray-500 uppercase">Chiffre d'affaires</p>
          <p class="text-lg font-bold text-gray-900">{{ formatCurrency(etude.total_revenues) }}</p>
        </div>
        <div class="text-center p-3 bg-gray-50 rounded-lg">
          <p class="text-xs text-gray-500 uppercase">Résultat Brut</p>
          <p
            class="text-lg font-bold"
            :class="isPositiveRatio ? 'text-green-600' : 'text-red-600'"
          >
            {{ formatCurrency(etude.resultat_brute) }}
          </p>
        </div>
        <div class="text-center p-3 bg-gray-50 rounded-lg">
          <p class="text-xs text-gray-500 uppercase">Ratio</p>
          <p
            class="text-lg font-bold"
            :class="isPositiveRatio ? 'text-green-600' : 'text-red-600'"
          >
            {{ etude.ratio != null ? `${etude.ratio.toFixed(1)}%` : '-' }}
          </p>
        </div>
      </div>

      <!-- Detailed breakdown (when not compact) -->
      <template v-if="!compact">
        <!-- Terrain -->
        <div class="border-t pt-4">
          <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-map" class="w-4 h-4 text-primary-500" />
            Terrain
          </h4>
          <div class="grid sm:grid-cols-3 gap-4 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">Superficie</span>
              <span class="font-medium">{{ formatNumber(etude.superficie_terrain) }} m²</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Prix/m²</span>
              <span class="font-medium">{{ formatCurrency(etude.prix_terrain_m2) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Prix total</span>
              <span class="font-medium">{{ formatCurrency(etude.prix_terrain_total) }}</span>
            </div>
          </div>
        </div>

        <!-- Construction -->
        <div class="border-t pt-4">
          <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-building" class="w-4 h-4 text-primary-500" />
            Construction
          </h4>
          <div class="grid sm:grid-cols-2 gap-4 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">Surface plancher total</span>
              <span class="font-medium">{{ formatNumber(etude.surface_plancher_total) }} m²</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Coût travaux</span>
              <span class="font-medium">{{ formatCurrency(etude.cout_total_travaux) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Gros œuvres/m²</span>
              <span class="font-medium">{{ formatCurrency(etude.cout_gros_oeuvres_m2) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Finition/m²</span>
              <span class="font-medium">{{ formatCurrency(etude.cout_finition_m2) }}</span>
            </div>
          </div>

          <!-- Surfaces par niveau -->
          <div v-if="etude.surfaces_par_niveau" class="mt-3 p-3 bg-gray-50 rounded-lg">
            <p class="text-xs text-gray-500 uppercase mb-2">Surfaces par niveau</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-sm">
              <div
                v-for="(surface, niveau) in etude.surfaces_par_niveau"
                :key="niveau"
                class="flex justify-between"
              >
                <span class="text-gray-500 capitalize">{{ niveau.replace('_', ' ') }}</span>
                <span class="font-medium">{{ formatNumber(surface) }} m²</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Frais -->
        <div class="border-t pt-4">
          <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-receipt" class="w-4 h-4 text-primary-500" />
            Frais
          </h4>
          <div class="grid sm:grid-cols-2 gap-4 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">Immatriculation</span>
              <span class="font-medium">{{ formatCurrency(etude.frais_immatriculation) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Groupement études</span>
              <span class="font-medium">{{ formatCurrency(etude.frais_groupement_etudes) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Autorisation + Éclatement</span>
              <span class="font-medium">{{ formatCurrency(etude.frais_autorisation_eclatement) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Lydec</span>
              <span class="font-medium">{{ formatCurrency(etude.frais_lydec) }}</span>
            </div>
          </div>
        </div>

        <!-- Ventes -->
        <div class="border-t pt-4">
          <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-banknote" class="w-4 h-4 text-primary-500" />
            Revenus estimés
          </h4>
          <div class="grid sm:grid-cols-2 gap-4 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">Surface vendable appart</span>
              <span class="font-medium">{{ formatNumber(etude.surface_vendable_appart) }} m²</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Prix/m² appart</span>
              <span class="font-medium">{{ formatCurrency(etude.prix_vente_m2_appart) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Revenus appart</span>
              <span class="font-medium text-green-600">{{ formatCurrency(etude.revenus_appart) }}</span>
            </div>
            <div v-if="etude.surface_vendable_commerce && etude.surface_vendable_commerce > 0" class="flex justify-between">
              <span class="text-gray-500">Revenus commerce</span>
              <span class="font-medium text-green-600">{{ formatCurrency(etude.revenus_commerce) }}</span>
            </div>
          </div>
        </div>

        <!-- AI Notes -->
        <div v-if="etude.ai_notes" class="border-t pt-4">
          <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-lightbulb" class="w-4 h-4 text-primary-500" />
            Recommandations IA
          </h4>
          <div class="p-3 bg-amber-50 rounded-lg text-sm text-amber-800 whitespace-pre-line">
            {{ etude.ai_notes }}
          </div>
        </div>
      </template>

      <!-- Actions -->
      <div v-if="showActions" class="border-t pt-4 flex gap-3">
        <UButton
          v-if="etude.pdf_path"
          label="Télécharger PDF"
          icon="i-lucide-download"
          variant="outline"
          @click="emit('download-pdf')"
        />
        <UButton
          v-else
          label="Générer PDF"
          icon="i-lucide-file-text"
          color="primary"
          @click="emit('download-pdf')"
        />
      </div>
    </div>

    <!-- Footer -->
    <div class="bg-gray-50 px-6 py-3 text-xs text-gray-500 flex items-center justify-between">
      <span>Version {{ etude.version || '-' }}</span>
      <span v-if="etude.reviewed_at">
        Approuvé le {{ new Date(etude.reviewed_at).toLocaleDateString('fr-FR') }}
      </span>
    </div>
  </div>
</template>
