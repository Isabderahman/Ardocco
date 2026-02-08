<script setup lang="ts">
import { nextTick, watch } from 'vue'
import type { ComponentPublicInstance } from 'vue'
import type { BackendListing } from '~/types/models/listing'

type TerrainTypeOption = { label: string, value: string }

const props = withDefaults(defineProps<{
  listings: BackendListing[]
  pending: boolean
  error: unknown
  selectedId?: string | null
  isAuthenticated: boolean
  terrainTypes: TerrainTypeOption[]
}>(), {
  selectedId: null
})

const emit = defineEmits<{
  (e: 'select', id: string): void
  (e: 'retry'): void
  (e: 'reset'): void
}>()

const itemElements = new Map<string, HTMLElement>()

function setItemRef(id: string) {
  return (el: Element | ComponentPublicInstance | null) => {
    if (!el) {
      itemElements.delete(id)
      return
    }
    const resolved = el instanceof HTMLElement ? el : (el as ComponentPublicInstance)?.$el
    if (resolved instanceof HTMLElement) itemElements.set(id, resolved)
  }
}

watch(
  () => props.selectedId,
  (id) => {
    if (!id) return
    void nextTick(() => {
      const el = itemElements.get(id)
      el?.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
    })
  }
)
</script>

<template>
  <section class="h-full min-h-0 overflow-hidden rounded-2xl bg-default ring-1 ring-default">
    <div class="h-full min-h-0 overflow-y-auto p-2 sm:p-4">
      <!-- Loading skeletons -->
      <div
        v-if="props.pending"
        class="space-y-3"
        aria-busy="true"
        aria-live="polite"
      >
        <div
          v-for="i in 7"
          :key="i"
          class="rounded-2xl ring-1 ring-default bg-elevated/40 p-3 animate-pulse"
        >
          <div class="flex gap-3">
            <div class="h-16 w-20 rounded-xl bg-default/60" />
            <div class="flex-1 space-y-2">
              <div class="h-4 w-4/5 rounded bg-default/60" />
              <div class="h-3 w-2/5 rounded bg-default/60" />
              <div class="h-4 w-1/3 rounded bg-default/60" />
            </div>
          </div>
        </div>
      </div>

      <!-- Error -->
      <div
        v-else-if="props.error"
        class="rounded-2xl ring-1 ring-default bg-default p-6 text-center"
      >
        <UIcon
          name="i-lucide-alert-circle"
          class="mx-auto size-10 text-red-500"
        />
        <p class="mt-3 text-sm font-semibold text-highlighted">
          Une erreur est survenue
        </p>
        <p class="mt-1 text-sm text-muted">
          Impossible de charger les terrains pour le moment.
        </p>
        <div class="mt-4 flex items-center justify-center gap-2">
          <UButton
            label="Réessayer"
            color="primary"
            variant="solid"
            @click="emit('retry')"
          />
        </div>
      </div>

      <!-- Empty -->
      <div
        v-else-if="!props.listings.length"
        class="rounded-2xl ring-1 ring-default bg-default p-8 text-center"
      >
        <UIcon
          name="i-lucide-search-x"
          class="mx-auto size-10 text-dimmed"
        />
        <p class="mt-3 text-sm font-semibold text-highlighted">
          Aucun terrain trouvé
        </p>
        <p class="mt-1 text-sm text-muted">
          Essayez d’élargir vos critères ou de réinitialiser les filtres.
        </p>
        <div class="mt-4 flex items-center justify-center gap-2">
          <UButton
            label="Réinitialiser"
            color="neutral"
            variant="outline"
            @click="emit('reset')"
          />
        </div>
      </div>

      <!-- Results -->
      <div
        v-else
        role="list"
        class="space-y-3"
      >
        <div
          v-for="listing in props.listings"
          :key="listing.id"
          :ref="setItemRef(listing.id)"
          role="listitem"
        >
          <ThemeAPublicListingResultCard
            :listing="listing"
            :terrain-types="props.terrainTypes"
            :is-authenticated="props.isAuthenticated"
            :selected="props.selectedId === listing.id"
            @select="emit('select', $event)"
          />
        </div>
      </div>
    </div>
  </section>
</template>
