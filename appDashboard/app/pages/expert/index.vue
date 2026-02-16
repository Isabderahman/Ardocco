<script setup lang="ts">
import type { ExpertiseType } from '~/types/models/expert'

definePageMeta({
  layout: 'dashboard',
  title: 'Espace Expert',
  middleware: 'expert'
})

const activeTab = ref<ExpertiseType>('all')

const { listings, pending, refresh } = useExpertPendingListings(activeTab)

const tabs: Array<{ label: string, value: ExpertiseType }> = [
  { label: 'Toutes', value: 'all' },
  { label: 'Technique', value: 'technique' },
  { label: 'Financière', value: 'financiere' },
  { label: 'Juridique', value: 'juridique' }
]

const formatPrice = (price: number | string | null | undefined) => {
  const numPrice = Number(price) || 0
  return new Intl.NumberFormat('fr-MA', {
    style: 'currency',
    currency: 'MAD',
    maximumFractionDigits: 0,
  }).format(numPrice)
}

const formatDate = (date: string | null | undefined) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Espace Expert</h1>
        <p class="text-gray-500 mt-1">Validez les expertises techniques, financières et juridiques</p>
      </div>
      <UButton
        label="Actualiser"
        variant="outline"
        icon="i-lucide-refresh-cw"
        @click="refresh()"
      />
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <ThemeAStatCard
        label="En attente"
        :value="String(listings.length)"
        icon="i-lucide-clock"
      />
      <ThemeAStatCard
        label="Validées aujourd'hui"
        value="0"
        icon="i-lucide-check-circle"
      />
      <ThemeAStatCard
        label="Total validées"
        value="—"
        icon="i-lucide-award"
      />
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 border-b">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        :class="[
          'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
          activeTab === tab.value
            ? 'border-primary-500 text-primary-600'
            : 'border-transparent text-gray-500 hover:text-gray-700'
        ]"
        @click="activeTab = tab.value"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Listings -->
    <div v-if="pending" class="flex justify-center py-12">
      <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin text-primary-500" />
    </div>

    <div v-else-if="!listings.length" class="text-center py-12">
      <UIcon name="i-lucide-check-circle" class="w-12 h-12 mx-auto text-green-500 mb-4" />
      <p class="text-gray-600">Aucune expertise en attente</p>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="listing in listings"
        :key="listing.id"
        class="bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <span class="text-sm text-gray-500">{{ listing.reference }}</span>
              <span
                class="text-xs px-2 py-0.5 rounded-full"
                :class="{
                  'bg-blue-100 text-blue-700': listing.type_terrain === 'residentiel',
                  'bg-purple-100 text-purple-700': listing.type_terrain === 'commercial',
                  'bg-yellow-100 text-yellow-700': listing.type_terrain === 'industriel',
                  'bg-green-100 text-green-700': listing.type_terrain === 'agricole',
                }"
              >
                {{ listing.type_terrain }}
              </span>
            </div>

            <h3 class="text-lg font-semibold text-gray-900">{{ listing.title }}</h3>
            <p class="text-gray-500 mt-1">
              {{ listing.quartier || listing.commune?.name_fr }}
            </p>

            <div class="flex items-center gap-6 mt-4 text-sm">
              <span class="text-primary-600 font-semibold">
                {{ formatPrice(listing.prix_demande) }}
              </span>
              <span class="text-gray-500">
                {{ listing.superficie }} m²
              </span>
              <span class="text-gray-500">
                Soumis le {{ formatDate(listing.created_at) }}
              </span>
            </div>

            <!-- Expertise status -->
            <div class="flex gap-4 mt-4">
              <div class="flex items-center gap-2">
                <UIcon
                  :name="listing.ficheTechnique?.validated_at ? 'i-lucide-check-circle' : 'i-lucide-circle'"
                  :class="listing.ficheTechnique?.validated_at ? 'text-green-500' : 'text-gray-300'"
                  class="w-4 h-4"
                />
                <span class="text-sm text-gray-600">Technique</span>
              </div>
              <div class="flex items-center gap-2">
                <UIcon
                  :name="listing.ficheFinanciere?.validated_at ? 'i-lucide-check-circle' : 'i-lucide-circle'"
                  :class="listing.ficheFinanciere?.validated_at ? 'text-green-500' : 'text-gray-300'"
                  class="w-4 h-4"
                />
                <span class="text-sm text-gray-600">Financière</span>
              </div>
              <div class="flex items-center gap-2">
                <UIcon
                  :name="listing.ficheJuridique?.validated_at ? 'i-lucide-check-circle' : 'i-lucide-circle'"
                  :class="listing.ficheJuridique?.validated_at ? 'text-green-500' : 'text-gray-300'"
                  class="w-4 h-4"
                />
                <span class="text-sm text-gray-600">Juridique</span>
              </div>
            </div>
          </div>

          <div class="flex flex-col gap-2">
            <NuxtLink :to="`/expert/listings/${listing.id}`">
              <UButton label="Examiner" color="primary" />
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
