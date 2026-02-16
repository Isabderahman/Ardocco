<script setup lang="ts">
import type { CardVariant } from '~/types/enums/ui'

withDefaults(defineProps<{
  as?: unknown
  variant?: CardVariant
  title?: string
  description?: string
  ui?: Record<string, unknown>
}>(), {
  variant: 'outline'
})
</script>

<template>
  <UCard
    :as="as"
    :variant="variant"
    :ui="ui"
    class="rounded-xl shadow-sm"
  >
    <template
      v-if="$slots.header || title || description || $slots.actions"
      #header
    >
      <slot name="header">
        <div class="flex items-start justify-between gap-4">
          <div class="min-w-0">
            <p
              v-if="title"
              class="text-sm font-semibold text-highlighted"
            >
              {{ title }}
            </p>
            <p
              v-if="description"
              class="mt-1 text-sm text-muted"
            >
              {{ description }}
            </p>
          </div>

          <div
            v-if="$slots.actions"
            class="shrink-0"
          >
            <slot name="actions" />
          </div>
        </div>
      </slot>
    </template>

    <slot />

    <template
      v-if="$slots.footer"
      #footer
    >
      <slot name="footer" />
    </template>
  </UCard>
</template>
