<script setup lang="ts">
interface FicheFinanciereData {
  id?: string
  estimated_market_price?: number | null
  price_per_sqm?: number | null
  comparables?: Array<{
    location?: string
    address?: string
    surface?: number
    type?: string
    price?: number
    price_m2?: number
    date?: string
  }> | null
  valuation_assumptions?: string | null
  development_costs?: number | null
  projected_sale_price?: number | null
  taxes_fees?: number | null
  rentabilite?: number | null
  expert_notes?: string | null
  conclusion?: string | null
  rating?: number | null
  validated_by?: string | null
  validated_at?: string | null
}

const props = defineProps<{
  fiche: FicheFinanciereData
  compact?: boolean
}>()

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

const formatDate = (date: string | null | undefined): string => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

// Parse comparables array
const comparables = computed(() => {
  if (!props.fiche.comparables) return []
  if (Array.isArray(props.fiche.comparables)) return props.fiche.comparables
  return []
})

// Rating stars
const ratingStars = computed(() => {
  const rating = props.fiche.rating ?? 0
  return Array.from({ length: 5 }, (_, i) => i < rating)
})

// Calculate total costs
const totalCosts = computed(() => {
  const dev = parseFloat(String(props.fiche.development_costs || 0)) || 0
  const taxes = parseFloat(String(props.fiche.taxes_fees || 0)) || 0
  return dev + taxes
})

// Rentability assessment
const rentabilityAssessment = computed(() => {
  const rent = props.fiche.rentabilite
  if (rent == null) return { label: 'Non calcule', color: 'text-gray-500', bgColor: 'bg-gray-100' }
  if (rent >= 15) return { label: 'Excellente opportunite', color: 'text-green-700', bgColor: 'bg-green-100' }
  if (rent >= 8) return { label: 'Bonne opportunite', color: 'text-yellow-700', bgColor: 'bg-yellow-100' }
  if (rent >= 0) return { label: 'Rentabilite moderee', color: 'text-orange-700', bgColor: 'bg-orange-100' }
  return { label: 'Rentabilite negative', color: 'text-red-700', bgColor: 'bg-red-100' }
})

const isValidated = computed(() => !!props.fiche.validated_at)
</script>

<template>
  <div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 px-6 py-4 text-white">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <UIcon name="i-lucide-chart-line" class="w-6 h-6" />
          <div>
            <h3 class="text-lg font-semibold">Analyse Financiere</h3>
            <p class="text-emerald-100 text-sm">Evaluation et rentabilite du terrain</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span
            v-if="isValidated"
            class="text-xs px-2 py-1 rounded-full bg-white/20 text-white flex items-center gap-1"
          >
            <UIcon name="i-lucide-check-circle" class="w-3 h-3" />
            Validee
          </span>
          <span v-else class="text-xs px-2 py-1 rounded-full bg-yellow-500/80 text-white">
            En attente
          </span>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="p-6 space-y-6">
      <!-- Key Metrics -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="text-center p-4 bg-gray-50 rounded-lg">
          <p class="text-xs text-gray-500 uppercase tracking-wider">Prix Estime</p>
          <p class="text-lg font-bold text-gray-900">{{ formatCurrency(fiche.estimated_market_price) }}</p>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded-lg">
          <p class="text-xs text-gray-500 uppercase tracking-wider">Prix/m2</p>
          <p class="text-lg font-bold text-gray-900">{{ formatCurrency(fiche.price_per_sqm) }}</p>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded-lg">
          <p class="text-xs text-gray-500 uppercase tracking-wider">Couts Dev.</p>
          <p class="text-lg font-bold text-gray-900">{{ formatCurrency(fiche.development_costs) }}</p>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded-lg">
          <p class="text-xs text-gray-500 uppercase tracking-wider">Rentabilite</p>
          <p
            class="text-lg font-bold"
            :class="{
              'text-green-600': fiche.rentabilite && fiche.rentabilite >= 15,
              'text-yellow-600': fiche.rentabilite && fiche.rentabilite >= 8 && fiche.rentabilite < 15,
              'text-orange-600': fiche.rentabilite && fiche.rentabilite >= 0 && fiche.rentabilite < 8,
              'text-red-600': fiche.rentabilite && fiche.rentabilite < 0,
              'text-gray-500': !fiche.rentabilite
            }"
          >
            {{ formatPercent(fiche.rentabilite) }}
          </p>
        </div>
      </div>

      <!-- Detailed breakdown (when not compact) -->
      <template v-if="!compact">
        <!-- Rating -->
        <div v-if="fiche.rating" class="flex items-center justify-center gap-1 py-2">
          <UIcon
            v-for="(filled, idx) in ratingStars"
            :key="idx"
            name="i-lucide-star"
            class="w-5 h-5"
            :class="filled ? 'text-yellow-400 fill-yellow-400' : 'text-gray-300'"
          />
          <span class="ml-2 text-sm text-gray-500">({{ fiche.rating }}/5)</span>
        </div>

        <!-- Estimation Details Table -->
        <div class="border-t pt-4">
          <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-calculator" class="w-4 h-4 text-emerald-500" />
            Estimation du Prix
          </h4>
          <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100">
              <tr>
                <td class="py-2 text-gray-500">Prix marche estime</td>
                <td class="py-2 text-right font-medium text-gray-900">{{ formatCurrency(fiche.estimated_market_price) }}</td>
              </tr>
              <tr>
                <td class="py-2 text-gray-500">Prix au m2</td>
                <td class="py-2 text-right font-medium text-gray-900">{{ formatCurrency(fiche.price_per_sqm) }}</td>
              </tr>
              <tr v-if="fiche.projected_sale_price">
                <td class="py-2 text-gray-500">Prix de vente projete</td>
                <td class="py-2 text-right font-medium text-green-600">{{ formatCurrency(fiche.projected_sale_price) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Costs Breakdown Table -->
        <div class="border-t pt-4">
          <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-coins" class="w-4 h-4 text-emerald-500" />
            Couts Estimes
          </h4>
          <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100">
              <tr>
                <td class="py-2 text-gray-500">Couts de developpement</td>
                <td class="py-2 text-right font-medium text-gray-900">{{ formatCurrency(fiche.development_costs) }}</td>
              </tr>
              <tr>
                <td class="py-2 text-gray-500">Taxes et frais</td>
                <td class="py-2 text-right font-medium text-gray-900">{{ formatCurrency(fiche.taxes_fees) }}</td>
              </tr>
              <tr class="bg-gray-50">
                <td class="py-2 px-2 font-semibold text-gray-700">Total couts</td>
                <td class="py-2 px-2 text-right font-bold text-gray-900">{{ formatCurrency(totalCosts) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Comparables -->
        <div v-if="comparables.length" class="border-t pt-4">
          <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-scale" class="w-4 h-4 text-emerald-500" />
            Comparables du Marche
          </h4>
          <div class="space-y-3">
            <div
              v-for="(comp, idx) in comparables"
              :key="idx"
              class="p-3 bg-gray-50 rounded-lg"
            >
              <div class="flex items-start justify-between">
                <div>
                  <p class="font-medium text-gray-900">{{ comp.location || comp.address || `Comparable ${idx + 1}` }}</p>
                  <p class="text-sm text-gray-500">{{ comp.surface }} m2 - {{ comp.type || 'Terrain' }}</p>
                </div>
                <div class="text-right">
                  <p class="font-bold text-primary-600">{{ formatCurrency(comp.price) }}</p>
                  <p class="text-xs text-gray-500">{{ formatCurrency(comp.price_m2) }}/m2</p>
                </div>
              </div>
              <p v-if="comp.date" class="text-xs text-gray-400 mt-2">
                Transaction: {{ formatDate(comp.date) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Valuation Assumptions -->
        <div v-if="fiche.valuation_assumptions" class="border-t pt-4">
          <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-file-text" class="w-4 h-4 text-emerald-500" />
            Hypotheses de Valorisation
          </h4>
          <div class="p-4 bg-gray-50 rounded-lg text-sm text-gray-600 whitespace-pre-line">
            {{ fiche.valuation_assumptions }}
          </div>
        </div>

        <!-- Rentability Analysis -->
        <div class="border-t pt-4">
          <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-trending-up" class="w-4 h-4 text-emerald-500" />
            Analyse de Rentabilite
          </h4>
          <div class="flex items-center justify-center p-6 bg-gray-50 rounded-lg">
            <div class="text-center">
              <div
                class="inline-flex items-center justify-center w-24 h-24 rounded-full mb-3"
                :class="rentabilityAssessment.bgColor"
              >
                <span class="text-2xl font-bold" :class="rentabilityAssessment.color">
                  {{ formatPercent(fiche.rentabilite) }}
                </span>
              </div>
              <p class="text-sm text-gray-500">Taux de rentabilite estime</p>
              <p class="text-xs mt-1 font-medium" :class="rentabilityAssessment.color">
                {{ rentabilityAssessment.label }}
              </p>
            </div>
          </div>
        </div>

        <!-- Expert Notes -->
        <div v-if="fiche.expert_notes" class="border-t pt-4">
          <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-message-square" class="w-4 h-4 text-emerald-500" />
            Notes de l'Expert
          </h4>
          <div class="p-4 bg-blue-50 rounded-lg text-sm text-blue-800 whitespace-pre-line">
            {{ fiche.expert_notes }}
          </div>
        </div>

        <!-- Conclusion -->
        <div v-if="fiche.conclusion" class="border-t pt-4">
          <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-check-square" class="w-4 h-4 text-emerald-500" />
            Conclusion
          </h4>
          <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-800 whitespace-pre-line">{{ fiche.conclusion }}</p>
          </div>
        </div>
      </template>
    </div>

    <!-- Footer -->
    <div class="bg-gray-50 px-6 py-3 text-xs text-gray-500 flex items-center justify-between">
      <span v-if="fiche.validated_at">
        Validee le {{ formatDate(fiche.validated_at) }}
      </span>
      <span v-else>En attente de validation</span>
      <span v-if="fiche.validated_by" class="flex items-center gap-1">
        <UIcon name="i-lucide-user-check" class="w-3 h-3" />
        Expert valide
      </span>
    </div>
  </div>
</template>
