<script setup lang="ts">
import type { AppLogoVariant } from '~/types/enums/ui'

const props = withDefaults(defineProps<{
  variant?: AppLogoVariant
  alt?: string
}>(), {
  variant: 'full',
  alt: 'ARDOCCO'
})

const src = computed(() => {
  if (props.variant === 'mark') return '/3-04.png'
  return '/3-04.png'
})

const size = computed(() => {
  if (props.variant === 'mark') return { width: 533, height: 157 }
  return { width: 533, height: 157 }
})
</script>

<template>
  <img
    :src="src"
    :alt="props.alt"
    :width="size.width"
    :height="size.height"
    decoding="async"
    draggable="false"
  >
</template>
