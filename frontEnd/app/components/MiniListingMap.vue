<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, computed } from 'vue'
import type { GeoJSONPolygon } from '~/types/models/geojson'
import type { MapMarker } from '~/types/models/map'

const props = defineProps<{
  id: string
  lat?: number | null
  lng?: number | null
  geojsonPolygon?: GeoJSONPolygon | null
}>()

const hostEl = ref<HTMLElement | null>(null)
const isVisible = ref(false)
let observer: IntersectionObserver | null = null

const hasMarker = computed(() => Number.isFinite(props.lat) && Number.isFinite(props.lng))
const markers = computed<MapMarker[]>(() => {
  if (!hasMarker.value) return []
  return [{
    id: props.id,
    lat: props.lat as number,
    lng: props.lng as number
  }]
})

const hasPolygon = computed(() => !!props.geojsonPolygon)
const hasMapData = computed(() => hasPolygon.value || hasMarker.value)

onMounted(() => {
  if (!import.meta.client) return
  if (!hostEl.value) return

  if (!('IntersectionObserver' in window)) {
    isVisible.value = true
    return
  }

  observer = new IntersectionObserver((entries) => {
    const entry = entries[0]
    if (!entry) return
    if (entry.isIntersecting) {
      isVisible.value = true
      observer?.disconnect()
      observer = null
    }
  }, { rootMargin: '200px' })

  observer.observe(hostEl.value)
})

onBeforeUnmount(() => {
  observer?.disconnect()
  observer = null
})
</script>

<template>
  <div ref="hostEl" class="h-full w-full">
    <ClientOnly>
      <CasablancaSettatMap
        v-if="hasMapData && isVisible"
        height="100%"
        :show-legend="false"
        :show-controls="false"
        :show-zoom-control="false"
        :scroll-wheel-zoom="false"
        :interactive="false"
        :show-province-boundaries="false"
        :fit-to-region="false"
        :markers="markers"
        :fit-to-markers="!hasPolygon"
        :selected-geojson-polygon="props.geojsonPolygon || null"
        :fit-to-selected-geojson-polygon="hasPolygon"
      />
    </ClientOnly>
  </div>
</template>
