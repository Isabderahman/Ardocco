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

function numeric(value: unknown): number | null {
  const n = Number(value)
  return Number.isFinite(n) ? n : null
}

function coverPhotoUrl(listing: BackendListing): string | null {
  const docs = listing.documents
  if (!Array.isArray(docs)) return null

  const photo = docs.find((doc) => {
    const obj = doc as { document_type?: unknown, file_path?: unknown }
    return obj?.document_type === 'photos' && typeof obj.file_path === 'string'
  }) as { file_path?: string } | undefined

  const path = String(photo?.file_path || '').replace(/^\/+/, '')
  return path ? `/storage/${path}` : null
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
        :image-url="coverPhotoUrl(listing)"
        :lat="numeric(listing.latitude)"
        :lng="numeric(listing.longitude)"
        :geojson-polygon="listing.geojson_polygon ?? null"
        :show-limited-hint="!props.isAuthenticated"
      />
    </div>
  </UContainer>
</template>
