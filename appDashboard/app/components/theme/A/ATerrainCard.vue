<script setup lang="ts">
import type { GeoJSONPolygon } from '~/types/models/geojson'

const props = withDefaults(defineProps<{
  title: string
  to?: string
  price?: string
  location?: string
  badge?: string
  description?: string
  imageUrl?: string | null
  lat?: number | null
  lng?: number | null
  geojsonPolygon?: GeoJSONPolygon | null
  area?: string
  etages?: string
  investmentCost?: string
  ratio?: string
}>(), {
  badge: 'Featured'
})

const hasMapData = computed(() => props.geojsonPolygon || (props.lat != null && props.lng != null))

function normalizedBadge(value: string | null | undefined): string {
  return String(value || '').trim().toLowerCase()
}

const badgeColor = computed(() => {
  const b = normalizedBadge(props.badge)
  if (!b) return 'primary'
  if (['publie', 'publié', 'published', 'valide', 'validé'].some(k => b.includes(k))) return 'success'
  if (['soumis', 'attente', 'pending', 'revision', 'révision', 'en_revision', 'en révision'].some(k => b.includes(k))) return 'warning'
  if (['brouillon', 'draft'].some(k => b.includes(k))) return 'neutral'
  if (['refuse', 'refus', 'refusé', 'rejected'].some(k => b.includes(k))) return 'error'
  return 'primary'
})
</script>

<template>
  <UCard class="group overflow-hidden rounded-2xl shadow-sm ring-1 ring-default transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="relative aspect-[4/3] bg-elevated w-full overflow-hidden">
      <!-- Image if available -->
      <img
        v-if="props.imageUrl"
        :src="props.imageUrl"
        :alt="props.title"
        class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
        loading="lazy"
        decoding="async"
      >

      <!-- Map fallback if no image but has coordinates -->
      <MiniListingMap
        v-else-if="hasMapData"
        :id="props.to || props.title"
        :lat="props.lat ?? null"
        :lng="props.lng ?? null"
        :geojson-polygon="props.geojsonPolygon || null"
      />

      <!-- Placeholder if neither image nor map data -->
      <div v-else class="absolute inset-0 grid place-items-center bg-gradient-to-br from-elevated to-default">
        <UIcon
          name="i-lucide-image"
          class="size-8 text-dimmed"
        />
      </div>

      <UBadge
        v-if="badge"
        :color="badgeColor"
        variant="solid"
        class="absolute left-3 top-3 z-10 shadow-sm"
      >
        {{ badge }}
      </UBadge>
    </div>

    <div class="space-y-3 p-4">
      <div class="space-y-1">
        <h3 class="text-sm font-semibold text-highlighted line-clamp-2">
          {{ title }}
        </h3>

        <p
          v-if="description"
          class="text-xs text-muted line-clamp-2"
        >
          {{ description }}
        </p>

        <p
          v-if="location"
          class="flex items-center gap-1 text-xs text-muted"
        >
          <UIcon
            name="i-lucide-map-pin"
            class="size-3"
          />
          <span class="truncate">{{ location }}</span>
        </p>
      </div>

      <!-- Info Grid -->
      <div class="grid grid-cols-2 gap-x-3 gap-y-2 text-xs">
        <div v-if="area" class="flex items-center gap-1.5 text-muted">
          <UIcon name="i-lucide-ruler" class="size-3.5 text-primary-600" />
          <span>{{ area }}</span>
        </div>
        <div v-if="etages" class="flex items-center gap-1.5 text-muted">
          <UIcon name="i-lucide-building-2" class="size-3.5 text-primary-600" />
          <span>{{ etages }}</span>
        </div>
        <div v-if="price" class="flex items-center gap-1.5 font-semibold text-highlighted">
          <UIcon name="i-lucide-banknote" class="size-3.5 text-primary-600" />
          <span>{{ price }}</span>
        </div>
        <div v-if="investmentCost" class="flex items-center gap-1.5 text-muted">
          <UIcon name="i-lucide-wallet" class="size-3.5 text-primary-600" />
          <span>{{ investmentCost }}</span>
        </div>
        <div v-if="ratio" class="col-span-2 flex items-center gap-1.5 text-muted">
          <UIcon name="i-lucide-percent" class="size-3.5 text-primary-600" />
          <span>Ratio: {{ ratio }}</span>
        </div>
      </div>

      <UButton
        label="Voir détails"
        color="primary"
        variant="outline"
        size="sm"
        :to="to"
        class="w-full"
        icon="i-lucide-arrow-right"
        trailing
      />
    </div>
  </UCard>
</template>
