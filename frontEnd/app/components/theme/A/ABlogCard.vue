<script setup lang="ts">
defineProps<{
  title: string
  to?: string
  date?: string
  excerpt?: string
}>()
</script>

<template>
  <UCard class="overflow-hidden rounded-xl shadow-sm">
    <div class="relative aspect-[16/10] bg-elevated">
      <div class="absolute inset-0 grid place-items-center">
        <UIcon
          name="i-lucide-newspaper"
          class="size-7 text-dimmed"
        />
      </div>
    </div>

    <div class="space-y-3 p-4">
      <div class="space-y-1">
        <p
          v-if="date"
          class="text-xs text-muted"
        >
          {{ date }}
        </p>
        <h3 class="text-sm font-semibold text-highlighted">
          {{ title }}
        </h3>
        <p
          v-if="excerpt"
          class="text-sm text-muted"
        >
          {{ excerpt }}
        </p>
      </div>

      <div class="flex items-center justify-end">
        <UButton
          label="Read more"
          color="neutral"
          variant="ghost"
          size="sm"
          trailing-icon="i-lucide-arrow-right"
          :to="to"
        />
      </div>
    </div>
  </UCard>
</template>
