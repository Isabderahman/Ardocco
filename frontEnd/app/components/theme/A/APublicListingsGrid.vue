<script setup lang="ts">
import type { BackendListing } from '~/types/models/listing'

type TerrainTypeOption = { label: string, value: string }

const props = defineProps<{
  listings: BackendListing[]
  isAuthenticated: boolean
  terrainTypes: TerrainTypeOption[]
}>()

function terrainTypeLabel(value: string | null | undefined) {
  if (!value) return 'Non spécifié'
  const found = props.terrainTypes.find(t => t.value === value)
  return found?.label || value
}

function formatPrice(price: number | string | null | undefined) {
  const numPrice = Number(price) || 0
  return new Intl.NumberFormat('fr-MA', {
    style: 'currency',
    currency: 'MAD',
    maximumFractionDigits: 0
  }).format(numPrice)
}
</script>

<template>
  <UContainer class="py-6 max-w-6xl">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <ThemeAPublicListingCard
        v-for="listing in props.listings"
        :key="listing.id"
        :to="`/terrains/${listing.id}`"
        :badge="terrainTypeLabel(listing.type_terrain)"
        :title="listing.title"
        :location="listing.quartier || listing.commune?.name_fr || 'Non spécifié'"
        :price="formatPrice(listing.prix_demande)"
        :area="listing.superficie ? `${listing.superficie} m²` : '—'"
        :show-limited-hint="!props.isAuthenticated"
      />
    </div>
  </UContainer>
</template>
