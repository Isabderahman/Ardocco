<script setup lang="ts">
type LegendItem = {
  name: string
  color: string
}

const props = withDefaults(defineProps<{
  items: LegendItem[]
  defaultCollapsed?: boolean
}>(), {
  defaultCollapsed: true
})

const collapsed = ref(props.defaultCollapsed)
</script>

<template>
  <div class="pointer-events-none absolute bottom-4 right-4 z-20">
    <div class="pointer-events-auto">
      <UButton
        v-if="collapsed"
        color="neutral"
        variant="outline"
        size="sm"
        icon="i-lucide-layers"
        class="rounded-full bg-default/90 backdrop-blur shadow-lg"
        aria-label="Afficher la légende"
        @click="collapsed = false"
      >
        <span class="hidden sm:inline">Légende</span>
      </UButton>

      <div
        v-else
        class="w-56 rounded-2xl bg-default/95 backdrop-blur ring-1 ring-default shadow-lg"
      >
        <div class="flex items-center justify-between gap-2 border-b border-default px-4 py-3">
          <p class="text-sm font-semibold text-highlighted">
            Provinces
          </p>
          <UButton
            icon="i-lucide-chevron-down"
            size="xs"
            color="neutral"
            variant="ghost"
            class="rounded-full"
            aria-label="Masquer la légende"
            @click="collapsed = true"
          />
        </div>

        <div class="px-4 py-3">
          <ul class="space-y-2 text-xs text-muted">
            <li
              v-for="item in props.items"
              :key="item.name"
              class="flex items-center gap-2"
            >
              <span
                class="inline-flex size-3 rounded-sm ring-1 ring-default"
                :style="{ backgroundColor: item.color }"
              />
              <span class="truncate">
                {{ item.name }}
              </span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>
