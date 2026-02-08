<script setup lang="ts">
const route = useRoute()
const { canCreateListing } = useAccess()
const title = computed(() => {
  const metaTitle = route.meta?.title
  if (typeof metaTitle === 'string' && metaTitle.length) return metaTitle
  return 'Dashboard'
})
</script>

<template>
  <UDashboardGroup class="flex min-h-svh">
    <ThemeASidebar :collapsible="true" />

    <div class="flex min-w-0 min-h-0 flex-1 flex-col">
      <UDashboardNavbar
        :title="title"
        :ui="{ root: 'sticky top-0 z-40 border-b border-default bg-default/80 backdrop-blur' }"
      >
        <template #right>
          <UButton
            v-if="canCreateListing"
            label="Add Terrain"
            color="primary"
            to="/terrains/new"
            class="rounded-full"
          />
        </template>
      </UDashboardNavbar>

      <main class="min-w-0 min-h-0 flex-1 overflow-y-auto p-4 lg:p-6">
        <slot />
      </main>
    </div>
  </UDashboardGroup>
</template>
