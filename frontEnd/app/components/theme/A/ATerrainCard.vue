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
}>(), {
  badge: 'Featured'
})

const hasMapData = computed(() => props.geojsonPolygon || (props.lat != null && props.lng != null))
</script>

<template>
  <UCard class="overflow-hidden rounded-xl shadow-sm">
    <div class="relative aspect-[3/3] bg-elevated w-full">
      <!-- Image if available -->
      <img
        v-if="props.imageUrl"
        :src="props.imageUrl"
        :alt="props.title"
        class="absolute inset-0 h-full w-full object-cover"
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
      <div v-else class="absolute inset-0 grid place-items-center">
        <UIcon
          name="i-lucide-image"
          class="size-7 text-dimmed"
        />
      </div>

      <UBadge
        v-if="badge"
        color="primary"
        variant="solid"
        class="absolute left-3 top-3 z-10"
      >
        {{ badge }}
      </UBadge>
    </div>

    <div class="space-y-3 p-4">
      <div class="space-y-1">
        <h3 class="text-sm font-semibold text-highlighted">
          {{ title }}
        </h3>
        <p
          v-if="description"
          class="text-sm text-muted line-clamp-2"
        >
          {{ description }}
        </p>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="space-y-0.5">
          <p
            v-if="location"
            class="flex items-center gap-1 text-sm text-muted"
          >
            <UIcon
              name="i-lucide-map-pin"
              class="size-4"
            />
            <span class="truncate">{{ location }}</span>
          </p>
          <div class="flex items-center gap-2">
            <p
              v-if="price"
              class="text-sm font-semibold text-highlighted"
            >
              {{ price }}
            </p>
            <span v-if="price && area" class="text-muted">·</span>
            <p
              v-if="area"
              class="text-sm text-muted"
            >
              {{ area }}
            </p>
          </div>
        </div>

        <UButton
          label="Details"
          color="neutral"
          variant="outline"
          size="sm"
          :to="to"
        />
      </div>
    </div>
  </UCard>
</template>
