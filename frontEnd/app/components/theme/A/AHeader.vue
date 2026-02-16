<template>
  <UHeader
    v-model:open="open"
    :toggle="{ class: 'lg:hidden' }"
    :ui="{
      root: 'sticky top-0 z-50 border-b border-default bg-default/80 backdrop-blur',
      container: 'max-w-none'
    }"
  >
    <template #left>
      <NuxtLink
        to="/"
        class="flex items-center"
      >
        <AppLogo
          variant="full"
          class="h-9 w-auto"
        />
      </NuxtLink>
    </template>

    <template #default>
      <UNavigationMenu
        :items="navItems"
        orientation="horizontal"
        variant="link"
        :ui="{
          link: 'text-base font-bold',
          item: 'py-3'
        }"
      />
    </template>

    <template #right>
      <div class="hidden items-center gap-2 lg:flex">
        <UButton
          icon="i-lucide-phone"
          label="+212 123456789"
          color="neutral"
          variant="ghost"
          to="tel:+212123456789"
          size="lg"
        />

        <UButton
          v-if="isAuthenticated"
          label="Dashboard"
          icon="i-lucide-layout-dashboard"
          color="neutral"
          variant="outline"
          :to="dashboardUrl"
          external
          size="lg"
        />

        <UButton
          v-if="!isAuthenticated"
          label="Login"
          color="neutral"
          variant="outline"
          to="/login"
          size="lg"
        />

        <UButton
          v-else
          label="Logout"
          color="neutral"
          variant="outline"
          to="/logout"
          size="lg"
        />

        <!-- <UDropdownMenu
          :items="pagesItems"
          :content="{ align: 'end' }"
          :ui="{ content: 'min-w-56' }"
        >
          <UButton
            label="Pages"
            color="neutral"
            variant="ghost"
            trailing-icon="i-lucide-chevron-down"
          />
        </UDropdownMenu> -->
      </div>

      <UButton
        v-if="canCreateListing"
        label="Add Terrain"
        color="primary"
        size="lg"
        :to="`${dashboardUrl}/terrains/new`"
        external
        class="rounded-full font-bold"
      />
    </template>

    <template #content>
      <div class="py-4">
        <UNavigationMenu
          :items="navItems"
          orientation="vertical"
          variant="pill"
          :ui="{
            link: 'text-base font-bold'
          }"
        />

        <div class="mt-4 border-t border-default pt-4">
          <UNavigationMenu
            :items="pagesItems"
            orientation="vertical"
            variant="pill"
            :ui="{
              link: 'text-base font-bold'
            }"
          />
        </div>
      </div>
    </template>
  </UHeader>
</template>

<script setup lang="ts">
import type { NavItem } from '~/types/models/navigation'

const open = ref(false)
const { isAuthenticated, ensureUserLoaded } = useAuth()
const { canCreateListing, canAccessAdmin, hasRole } = useAccess()
const config = useRuntimeConfig()

// Dashboard app URL (app.ardocco.com)
const dashboardUrl = computed(() => config.public.dashboardUrl || 'http://localhost:3001')

onMounted(() => {
  if (!isAuthenticated.value) return
  ensureUserLoaded().catch(() => {})
})

const navItems: NavItem[] = [
  { label: 'Accueil', to: '/' },
  { label: 'Comment ça marche', to: '/how-it-works' },
  { label: 'À propos de nous', to: '/about' },
  { label: 'Contact', to: '/contact' }
]

const pagesItems = computed<NavItem[]>(() => {
  const items: NavItem[] = []
  const baseUrl = dashboardUrl.value

  if (isAuthenticated.value) {
    items.push({ label: 'Dashboard', to: baseUrl, icon: 'i-lucide-layout-dashboard' })
    if (canCreateListing.value) items.push({ label: 'Add Terrain', to: `${baseUrl}/terrains/new`, icon: 'i-lucide-plus-square' })
    if (canAccessAdmin.value) items.push({ label: 'Admin', to: `${baseUrl}/admin`, icon: 'i-lucide-shield' })
    if (hasRole('expert', 'admin')) items.push({ label: 'Expert', to: `${baseUrl}/expert`, icon: 'i-lucide-award' })
  }

  if (isAuthenticated.value) {
    items.push({ label: 'Logout', to: '/logout', icon: 'i-lucide-log-out' })
  } else {
    items.push({ label: 'Login', to: '/login', icon: 'i-lucide-log-in' })
  }

  return items
})
</script>
