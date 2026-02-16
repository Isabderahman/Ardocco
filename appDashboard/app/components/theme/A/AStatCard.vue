<script setup lang="ts">
withDefaults(defineProps<{
  label: string
  value: string
  icon?: string
  helper?: string
}>(), {
  icon: 'i-lucide-bar-chart-3'
})
</script>

<template>
  <UCard class="rounded-xl shadow-sm">
    <div class="flex items-center gap-4">
      <div class="grid size-11 place-items-center rounded-full bg-primary/10 text-primary">
        <UIcon
          :name="icon"
          class="size-5"
        />
      </div>

      <div class="min-w-0">
        <p class="text-sm font-medium text-muted">
          {{ label }}
        </p>
        <p class="text-2xl font-semibold text-highlighted">
          {{ value }}
        </p>
        <p
          v-if="helper"
          class="mt-1 text-sm text-muted"
        >
          {{ helper }}
        </p>
      </div>
    </div>
  </UCard>
</template>
