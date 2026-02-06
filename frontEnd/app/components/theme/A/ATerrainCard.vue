<script setup lang="ts">
withDefaults(defineProps<{
  title: string
  to?: string
  price?: string
  location?: string
  badge?: string
  description?: string
}>(), {
  badge: 'Featured'
})
</script>

<template>
  <UCard class="overflow-hidden rounded-xl shadow-sm">
    <div class="relative aspect-[16/10] bg-elevated w-full">
      <div class="absolute inset-0 grid place-items-center">
        <UIcon
          name="i-lucide-image"
          class="size-7 text-dimmed"
        />
      </div>

      <UBadge
        v-if="badge"
        color="primary"
        variant="solid"
        class="absolute left-3 top-3"
      >
        {{ badge }}
      </UBadge>
    </div>

    <div class="space-y-3 p-4">
      <div class="space-y-1">
        <h3 class="text-sm font-semibold text-highlighted">
          {{ title }}
        </h3>
        <p
          v-if="description"
          class="text-sm text-muted"
        >
          {{ description }}
        </p>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="space-y-0.5">
          <p
            v-if="location"
            class="flex items-center gap-1 text-sm text-muted"
          >
            <UIcon
              name="i-lucide-map-pin"
              class="size-4"
            />
            <span class="truncate">{{ location }}</span>
          </p>
          <p
            v-if="price"
            class="text-sm font-semibold text-highlighted"
          >
            {{ price }}
          </p>
        </div>

        <UButton
          label="Details"
          color="neutral"
          variant="outline"
          size="sm"
          :to="to"
        />
      </div>
    </div>
  </UCard>
</template>
