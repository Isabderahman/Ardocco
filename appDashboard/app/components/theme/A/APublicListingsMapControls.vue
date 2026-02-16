<script setup lang="ts">
const props = withDefaults(defineProps<{
  showSearchInArea: boolean
  hasAreaFilter: boolean
}>(), {
  showSearchInArea: false,
  hasAreaFilter: false
})

const emit = defineEmits<{
  (e: 'search'): void
  (e: 'reset'): void
}>()
</script>

<template>
  <div class="pointer-events-none absolute left-4 top-4 z-20 flex flex-col gap-2">
    <UButton
      v-if="props.showSearchInArea"
      label="Rechercher dans cette zone"
      color="primary"
      variant="solid"
      size="sm"
      class="pointer-events-auto shadow-lg rounded-full"
      icon="i-lucide-search"
      @click="emit('search')"
    />

    <UButton
      v-if="props.hasAreaFilter"
      label="Réinitialiser la zone"
      color="neutral"
      variant="outline"
      size="sm"
      class="pointer-events-auto shadow-lg rounded-full bg-default/90 backdrop-blur"
      icon="i-lucide-undo-2"
      @click="emit('reset')"
    />
  </div>
</template>
