<script setup lang="ts">
import type { FicheFinanciere } from '~/types/models/listing'

const props = defineProps<{
  fiche: FicheFinanciere
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
  const viab = parseFloat(String(props.fiche.development_costs || 0)) || 0
  const taxes = parseFloat(String(props.fiche.taxes_fees || 0)) || 0
  return viab + taxes
})

// Rentability color
const rentabilityColor = computed(() => {
  const rent = props.fiche.rentabilite
  if (rent == null) return 'text-muted'
  if (rent >= 15) return 'text-success'
  if (rent >= 8) return 'text-warning'
  return 'text-error'
})

const isValidated = computed(() => !!props.fiche.validated_at)
</script>

<template>
  <div class="bg-elevated rounded-xl overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 px-6 py-4 text-white">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <UIcon name="i-lucide-chart-line" class="size-6" />
          <div>
            <h3 class="text-lg font-semibold">Analyse Financiere</h3>
            <p class="text-emerald-100 text-sm">Evaluation et rentabilite</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <UBadge v-if="isValidated" color="success" variant="soft">
            <UIcon name="i-lucide-check-circle" class="size-3 mr-1" />
            Validee
          </UBadge>
          <UBadge v-else color="warning" variant="soft">
            En attente
          </UBadge>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="p-6 space-y-6">
      <!-- Key Metrics -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="text-center p-4 bg-default rounded-lg">
          <p class="text-xs text-dimmed uppercase tracking-wider">Prix Estime</p>
          <p class="text-lg font-bold text-highlighted">{{ formatCurrency(fiche.estimated_market_price) }}</p>
        </div>
        <div class="text-center p-4 bg-default rounded-lg">
          <p class="text-xs text-dimmed uppercase tracking-wider">Prix/m2</p>
          <p class="text-lg font-bold text-highlighted">{{ formatCurrency(fiche.price_per_sqm) }}</p>
        </div>
        <div class="text-center p-4 bg-default rounded-lg">
          <p class="text-xs text-dimmed uppercase tracking-wider">Couts Dev.</p>
          <p class="text-lg font-bold text-highlighted">{{ formatCurrency(fiche.development_costs) }}</p>
        </div>
        <div class="text-center p-4 bg-default rounded-lg">
          <p class="text-xs text-dimmed uppercase tracking-wider">Rentabilite</p>
          <p class="text-lg font-bold" :class="rentabilityColor">{{ formatPercent(fiche.rentabilite) }}</p>
        </div>
      </div>

      <!-- Detailed breakdown (when not compact) -->
      <template v-if="!compact">
        <!-- Rating -->
        <div v-if="fiche.rating" class="flex items-center justify-center gap-1 py-2">
          <UIcon
            v-for="(filled, idx) in ratingStars"
            :key="idx"
            :name="filled ? 'i-lucide-star' : 'i-lucide-star'"
            class="size-5"
            :class="filled ? 'text-warning fill-warning' : 'text-muted'"
          />
          <span class="ml-2 text-sm text-muted">({{ fiche.rating }}/5)</span>
        </div>

        <!-- Estimation Details -->
        <div class="border-t border-default pt-4">
          <h4 class="font-semibold text-highlighted mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-calculator" class="size-4 text-emerald-500" />
            Estimation du Prix
          </h4>
          <table class="w-full text-sm">
            <tbody>
              <tr class="border-b border-default">
                <td class="py-2 text-muted">Prix marche estime</td>
                <td class="py-2 text-right font-medium text-highlighted">{{ formatCurrency(fiche.estimated_market_price) }}</td>
              </tr>
              <tr class="border-b border-default">
                <td class="py-2 text-muted">Prix au m2</td>
                <td class="py-2 text-right font-medium text-highlighted">{{ formatCurrency(fiche.price_per_sqm) }}</td>
              </tr>
              <tr v-if="fiche.projected_sale_price">
                <td class="py-2 text-muted">Prix de vente projete</td>
                <td class="py-2 text-right font-medium text-success">{{ formatCurrency(fiche.projected_sale_price) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Costs Breakdown -->
        <div class="border-t border-default pt-4">
          <h4 class="font-semibold text-highlighted mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-coins" class="size-4 text-emerald-500" />
            Couts Estimes
          </h4>
          <table class="w-full text-sm">
            <tbody>
              <tr class="border-b border-default">
                <td class="py-2 text-muted">Couts de developpement</td>
                <td class="py-2 text-right font-medium text-highlighted">{{ formatCurrency(fiche.development_costs) }}</td>
              </tr>
              <tr class="border-b border-default">
                <td class="py-2 text-muted">Taxes et frais</td>
                <td class="py-2 text-right font-medium text-highlighted">{{ formatCurrency(fiche.taxes_fees) }}</td>
              </tr>
              <tr class="bg-default">
                <td class="py-2 px-2 text-muted font-semibold">Total couts</td>
                <td class="py-2 px-2 text-right font-bold text-highlighted">{{ formatCurrency(totalCosts) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Comparables -->
        <div v-if="comparables.length" class="border-t border-default pt-4">
          <h4 class="font-semibold text-highlighted mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-scale" class="size-4 text-emerald-500" />
            Comparables du Marche
          </h4>
          <div class="space-y-3">
            <div
              v-for="(comp, idx) in comparables"
              :key="idx"
              class="p-3 bg-default rounded-lg"
            >
              <div class="flex items-start justify-between">
                <div>
                  <p class="font-medium text-highlighted">{{ comp.location || comp.address || `Comparable ${idx + 1}` }}</p>
                  <p class="text-sm text-muted">{{ comp.surface }} m2 - {{ comp.type || 'Terrain' }}</p>
                </div>
                <div class="text-right">
                  <p class="font-bold text-primary">{{ formatCurrency(comp.price) }}</p>
                  <p class="text-xs text-muted">{{ formatCurrency(comp.price_m2) }}/m2</p>
                </div>
              </div>
              <p v-if="comp.date" class="text-xs text-dimmed mt-2">
                Transaction: {{ formatDate(comp.date) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Valuation Assumptions -->
        <div v-if="fiche.valuation_assumptions" class="border-t border-default pt-4">
          <h4 class="font-semibold text-highlighted mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-file-text" class="size-4 text-emerald-500" />
            Hypotheses de Valorisation
          </h4>
          <div class="p-4 bg-default rounded-lg text-sm text-muted whitespace-pre-line">
            {{ fiche.valuation_assumptions }}
          </div>
        </div>

        <!-- Rentability Analysis -->
        <div class="border-t border-default pt-4">
          <h4 class="font-semibold text-highlighted mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-trending-up" class="size-4 text-emerald-500" />
            Analyse de Rentabilite
          </h4>
          <div class="flex items-center justify-center p-6 bg-default rounded-lg">
            <div class="text-center">
              <div
                class="inline-flex items-center justify-center w-24 h-24 rounded-full mb-3"
                :class="fiche.rentabilite && fiche.rentabilite > 0 ? 'bg-success/20' : 'bg-error/20'"
              >
                <span class="text-2xl font-bold" :class="rentabilityColor">
                  {{ formatPercent(fiche.rentabilite) }}
                </span>
              </div>
              <p class="text-sm text-muted">Taux de rentabilite estime</p>
              <p v-if="fiche.rentabilite" class="text-xs mt-1" :class="rentabilityColor">
                <template v-if="fiche.rentabilite >= 15">Excellente opportunite</template>
                <template v-else-if="fiche.rentabilite >= 8">Bonne opportunite</template>
                <template v-else-if="fiche.rentabilite >= 0">Rentabilite moderee</template>
                <template v-else>Attention: rentabilite negative</template>
              </p>
            </div>
          </div>
        </div>

        <!-- Expert Notes -->
        <div v-if="fiche.expert_notes" class="border-t border-default pt-4">
          <h4 class="font-semibold text-highlighted mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-message-square" class="size-4 text-emerald-500" />
            Notes de l'Expert
          </h4>
          <div class="p-4 bg-info/10 rounded-lg text-sm text-info whitespace-pre-line">
            {{ fiche.expert_notes }}
          </div>
        </div>

        <!-- Conclusion -->
        <div v-if="fiche.conclusion" class="border-t border-default pt-4">
          <h4 class="font-semibold text-highlighted mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-check-square" class="size-4 text-emerald-500" />
            Conclusion
          </h4>
          <div class="p-4 bg-success/10 border border-success/20 rounded-lg">
            <p class="text-sm text-success whitespace-pre-line">{{ fiche.conclusion }}</p>
          </div>
        </div>
      </template>
    </div>

    <!-- Footer -->
    <div class="bg-default px-6 py-3 text-xs text-muted flex items-center justify-between">
      <span v-if="fiche.validated_at">
        Validee le {{ formatDate(fiche.validated_at) }}
      </span>
      <span v-else>Non validee</span>
      <span v-if="fiche.validated_by" class="flex items-center gap-1">
        <UIcon name="i-lucide-user-check" class="size-3" />
        Expert valide
      </span>
    </div>
  </div>
</template>
