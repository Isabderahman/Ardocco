<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  title: 'Reviews'
})

const reviews = [
  {
    name: 'Lorem Ipsum',
    time: '1 day ago',
    message: 'Lorem ipsum dolor sit amet, consectetur incididunt.'
  },
  {
    name: 'Lorem Ipsum',
    time: '3 day ago',
    message: 'Lorem ipsum dolor sit amet, consectetur incididunt.'
  }
]
</script>

<template>
  <ThemeACard title="Reviews">
    <div class="space-y-4">
      <div
        v-for="r in reviews"
        :key="`${r.name}-${r.time}`"
        class="flex items-start gap-3"
      >
        <UAvatar
          :text="r.name.slice(0, 1)"
          size="sm"
        />
        <div class="min-w-0">
          <div class="flex items-center justify-between gap-2">
            <p class="text-sm font-semibold text-highlighted">
              {{ r.name }}
            </p>
            <p class="text-xs text-muted">
              {{ r.time }}
            </p>
          </div>
          <p class="mt-1 text-sm text-muted">
            {{ r.message }}
          </p>
        </div>
      </div>
    </div>
  </ThemeACard>
</template>
