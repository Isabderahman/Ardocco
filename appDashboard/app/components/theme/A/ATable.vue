<script setup lang="ts" generic="T extends Record<string, unknown> = Record<string, unknown>">
import { computed } from 'vue'
import type { ColumnDef } from '@tanstack/vue-table'
import type { Ui } from '~/types/models/ui'

const props = withDefaults(defineProps<{
  data?: T[]
  columns?: Array<ColumnDef<T, unknown>>
  sticky?: boolean | 'header' | 'footer'
  loading?: boolean
  empty?: string
  ui?: Ui
}>(), {
  sticky: 'header'
})

defineSlots<Record<string, (props: unknown) => unknown>>()

function mergeUi(defaultUi: Ui, overrideUi?: Ui): Ui {
  if (!overrideUi) return defaultUi

  const merged: Ui = { ...defaultUi }
  for (const [key, overrideValue] of Object.entries(overrideUi)) {
    const defaultValue = merged[key]

    if (typeof defaultValue === 'string' && typeof overrideValue === 'string') {
      merged[key] = `${defaultValue} ${overrideValue}`
      continue
    }

    merged[key] = overrideValue
  }

  return merged
}

const mergedUi = computed(() => mergeUi({
  thead: 'relative',
  th: 'bg-slate-900 text-white text-xs font-semibold uppercase tracking-wide py-3'
}, props.ui))
</script>

<template>
  <div class="overflow-hidden rounded-xl ring ring-default bg-default">
    <UTable
      v-slots="$slots"
      v-bind="$attrs"
      :data="props.data"
      :columns="props.columns"
      :sticky="props.sticky"
      :loading="props.loading"
      :empty="props.empty"
      :ui="mergedUi"
    />
  </div>
</template>
