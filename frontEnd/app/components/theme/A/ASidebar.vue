<script setup lang="ts">
import type { ASidebarItem } from '~/types/models/navigation'

const props = withDefaults(defineProps<{
  title?: string
  logoTo?: string
  items?: ASidebarItem[]
  collapsible?: boolean
}>(), {
  title: 'ARDOCCO',
  logoTo: '/',
  collapsible: false,
  items: () => ([
    { label: 'Dashboards', icon: 'i-lucide-layout-dashboard', to: '/dashboard' },
    { label: 'Profile', icon: 'i-lucide-user', to: '/profile' },
    { label: 'My package', icon: 'i-lucide-package', to: '/package' },
    { label: 'My favorites', icon: 'i-lucide-heart', to: '/favorites' },
    { label: 'My save searches', icon: 'i-lucide-search', to: '/saved-searches' },
    { label: 'Reviews', icon: 'i-lucide-message-square-text', to: '/reviews' },
    { label: 'My Terrains', icon: 'i-lucide-map', to: '/terrains' },
    { label: 'Add Terrain', icon: 'i-lucide-plus-square', to: '/terrains/new' },
    { label: 'Logout', icon: 'i-lucide-log-out', to: '/logout' }
  ])
})

const open = defineModel<boolean>('open', { default: false })
const collapsed = defineModel<boolean>('collapsed', { default: false })
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
            class="size-7 shrink-0"
          />
          <span
            v-if="!isCollapsed"
            class="truncate text-sm font-semibold text-highlighted"
          >
            {{ props.title }}
          </span>
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
      :items="props.items"
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
