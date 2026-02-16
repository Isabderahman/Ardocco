<script setup lang="ts">
import type { GeoJSONPolygon } from '~/types/models/geojson'

const props = withDefaults(defineProps<{
  to: string
  title: string
  badge?: string
  location: string
  price: string
  area: string
  imageUrl?: string | null
  lat?: number | null
  lng?: number | null
  geojsonPolygon?: GeoJSONPolygon | null
  showLimitedHint?: boolean
}>(), {
  showLimitedHint: false
})
</script>

<template>
  <NuxtLink
    :to="props.to"
    class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden group"
  >
    <!-- Image placeholder -->
    <div class="aspect-[4/3] bg-gray-200 relative">
      <img
        v-if="props.imageUrl"
        :src="props.imageUrl"
        :alt="props.title"
        class="absolute inset-0 h-full w-full object-cover"
        loading="lazy"
        decoding="async"
      >

      <MiniListingMap
        v-else-if="props.geojsonPolygon || (props.lat != null && props.lng != null)"
        :id="props.to"
        :lat="props.lat ?? null"
        :lng="props.lng ?? null"
        :geojson-polygon="props.geojsonPolygon || null"
      />

      <div v-else class="absolute inset-0 flex items-center justify-center">
        <UIcon
          name="i-lucide-image"
          class="w-12 h-12 text-gray-400"
        />
      </div>

      <div
        v-if="props.badge"
        class="absolute top-3 left-3"
      >
        <span class="bg-primary-500 text-white text-xs px-2 py-1 rounded-full">
          {{ props.badge }}
        </span>
      </div>
    </div>

    <!-- Content -->
    <div class="p-4">
      <h3 class="font-semibold text-gray-900 group-hover:text-primary-600 transition-colors">
        {{ props.title }}
      </h3>

      <p class="text-sm text-gray-500 mt-1 flex items-center gap-1">
        <UIcon
          name="i-lucide-map-pin"
          class="w-4 h-4"
        />
        {{ props.location }}
      </p>

      <div class="mt-4 flex items-center justify-between">
        <span class="text-lg font-bold text-primary-600">
          {{ props.price }}
        </span>
        <span class="text-sm text-gray-500">
          {{ props.area }}
        </span>
      </div>

      <!-- Limited access indicator -->
      <div
        v-if="props.showLimitedHint"
        class="mt-3 pt-3 w-full"
      >
        <p class="text-xs text-primary flex items-center gap-1 ">
          <UIcon
            name="i-lucide-lock"
            class="w-3 h-3"
          />
          Connectez-vous pour plus de détails
        </p>
      </div>
    </div>
  </NuxtLink>
</template>
