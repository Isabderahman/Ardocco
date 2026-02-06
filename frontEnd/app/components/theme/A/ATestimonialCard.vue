<script setup lang="ts">
defineProps<{
  quote: string
  name: string
  role?: string
}>()
</script>

<template>
  <UCard class="rounded-xl shadow-sm">
    <div class="space-y-4">
      <div class="flex items-start gap-3">
        <div class="grid size-10 place-items-center rounded-full bg-primary/10 text-primary">
          <UIcon
            name="i-lucide-quote"
            class="size-5"
          />
        </div>
        <p class="text-sm text-muted">
          {{ quote }}
        </p>
      </div>

      <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
          <p class="text-sm font-semibold text-highlighted">
            {{ name }}
          </p>
          <p
            v-if="role"
            class="text-sm text-muted"
          >
            {{ role }}
          </p>
        </div>
        <UAvatar
          :text="name.slice(0, 1).toUpperCase()"
          size="sm"
        />
      </div>
    </div>
  </UCard>
</template>
