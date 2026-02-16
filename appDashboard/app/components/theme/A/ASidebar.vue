<script setup lang="ts">
import type { ASidebarItem } from '~/types/models/navigation'

const props = withDefaults(defineProps<{
  title?: string
  logoTo?: string
  items?: ASidebarItem[]
  collapsible?: boolean
}>(), {
  logoTo: '/',
  collapsible: false,
  items: () => ([
    { label: 'Dashboard', icon: 'i-lucide-layout-dashboard', to: '/dashboard' },
    { label: 'Mes terrains', icon: 'i-lucide-map', to: '/dashboard/terrains' },
    { label: 'Add Terrain', icon: 'i-lucide-plus-square', to: '/terrains/new' },
    { label: 'Profile', icon: 'i-lucide-user', to: '/profile' },
    { label: 'Browse Terrains', icon: 'i-lucide-search', to: '/terrains' },
    { label: 'Admin', icon: 'i-lucide-shield', to: '/admin' },
    { label: 'Expert', icon: 'i-lucide-award', to: '/expert' },
    { label: 'Logout', icon: 'i-lucide-log-out', to: '/logout' }
  ])
})

const open = defineModel<boolean>('open', { default: false })
const collapsed = defineModel<boolean>('collapsed', { default: false })

const { canCreateListing, canAccessAdmin, hasRole } = useAccess()

function filterItems(items: ASidebarItem[]): ASidebarItem[] {
  return items
    .map((item) => {
      const children = Array.isArray(item.children) ? filterItems(item.children) : undefined
      return { ...item, children }
    })
    .filter((item) => {
      if (item.to === '/terrains/new') return canCreateListing.value
      if (item.to === '/dashboard/terrains') return canCreateListing.value
      if (item.to === '/admin') return canAccessAdmin.value
      if (item.to === '/expert') return hasRole('expert')
      if (!item.to && Array.isArray(item.children) && item.children.length === 0) return false
      return true
    })
}

const filteredItems = computed(() => filterItems(props.items ?? []))
</script>

<template>
  <UDashboardSidebar
    v-model:open="open"
    v-model:collapsed="collapsed"
    :collapsible="props.collapsible"
    :ui="{
      root: 'bg-default',
      body: 'px-3 py-3 gap-2',
      header: 'px-3',
      footer: 'px-3 py-3'
    }"
  >
    <template #header="{ collapsed: isCollapsed, collapse }">
      <div class="flex w-full items-center justify-between gap-2">
        <NuxtLink
          :to="props.logoTo"
          class="flex min-w-0 items-center gap-2"
        >
          <AppLogo
            variant="mark"
            class="h-8 w-auto shrink-0"
          />
        </NuxtLink>

        <UButton
          v-if="props.collapsible"
          color="neutral"
          variant="ghost"
          :icon="isCollapsed ? 'i-lucide-panel-left-open' : 'i-lucide-panel-left-close'"
          size="sm"
          @click="collapse?.(!isCollapsed)"
        />
      </div>
    </template>

    <UNavigationMenu
      :items="filteredItems"
      orientation="vertical"
      :collapsed="collapsed"
      :tooltip="true"
      :ui="{
        list: 'space-y-1',
        link: [
          'rounded-lg before:rounded-lg',
          'aria-[current=page]:text-white aria-[current=page]:before:bg-primary',
          'aria-[current=page]:hover:text-white aria-[current=page]:hover:before:bg-primary'
        ].join(' '),
        linkLeadingIcon: 'text-dimmed group-[aria-current=page]:text-white'
      }"
    />
  </UDashboardSidebar>
</template>
