<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  title: 'Administration',
  middleware: 'admin'
})

const { stats, pendingListings } = useAdminDashboard()
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">Tableau de bord Admin</h1>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <ThemeAStatCard
        label="Utilisateurs"
        :value="String(stats?.users?.total || 0)"
        icon="i-lucide-users"
        :helper="`${stats?.users?.new_this_month || 0} ce mois`"
      />
      <ThemeAStatCard
        label="Annonces publiées"
        :value="String(stats?.listings?.published || 0)"
        icon="i-lucide-map"
        :helper="`${stats?.listings?.total_views || 0} vues total`"
      />
      <ThemeAStatCard
        label="En attente"
        :value="String(stats?.listings?.pending_approval || 0)"
        icon="i-lucide-clock"
      />
      <ThemeAStatCard
        label="Demandes contact"
        :value="String(stats?.contact_requests?.pending || 0)"
        icon="i-lucide-message-circle"
      />
    </div>

    <!-- Quick Actions -->
    <div class="grid md:grid-cols-2 gap-6">
      <!-- Pending Listings -->
      <ThemeACard title="Annonces en attente" description="Approuvez ou rejetez les nouvelles annonces">
        <div v-if="!pendingListings.length" class="py-8 text-center text-gray-500">
          Aucune annonce en attente
        </div>
        <div v-else class="divide-y">
          <div
            v-for="listing in pendingListings.slice(0, 5)"
            :key="listing.id"
            class="py-3 flex items-center justify-between"
          >
            <div>
              <p class="font-medium text-gray-900">{{ listing.title }}</p>
              <p class="text-sm text-gray-500">
                {{ listing.reference }} - {{ listing.owner?.first_name }} {{ listing.owner?.last_name }}
              </p>
            </div>
            <NuxtLink :to="`/admin/listings/${listing.id}`">
              <UButton label="Examiner" size="sm" variant="outline" />
            </NuxtLink>
          </div>
        </div>
        <template #footer>
          <NuxtLink to="/admin/listings">
            <UButton label="Voir toutes les annonces" variant="ghost" size="sm" />
          </NuxtLink>
        </template>
      </ThemeACard>

      <!-- User Stats -->
      <ThemeACard title="Utilisateurs par rôle" description="Répartition des comptes">
        <div class="space-y-4">
          <div
            v-for="(count, role) in stats?.users?.by_role"
            :key="role"
            class="flex items-center justify-between"
          >
            <span class="capitalize text-gray-600">{{ role }}</span>
            <span class="font-semibold text-gray-900">{{ count }}</span>
          </div>
        </div>
        <template #footer>
          <NuxtLink to="/admin/users">
            <UButton label="Gérer les utilisateurs" variant="ghost" size="sm" />
          </NuxtLink>
        </template>
      </ThemeACard>
    </div>

    <!-- Navigation Cards -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <NuxtLink to="/admin/users" class="block">
        <div class="bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
          <UIcon name="i-lucide-users" class="w-8 h-8 text-primary-500 mb-3" />
          <h3 class="font-semibold text-gray-900">Utilisateurs</h3>
          <p class="text-sm text-gray-500 mt-1">Gérer les comptes et rôles</p>
        </div>
      </NuxtLink>

      <NuxtLink to="/admin/listings" class="block">
        <div class="bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
          <UIcon name="i-lucide-layout-list" class="w-8 h-8 text-primary-500 mb-3" />
          <h3 class="font-semibold text-gray-900">Annonces</h3>
          <p class="text-sm text-gray-500 mt-1">Modérer et mettre en avant</p>
        </div>
      </NuxtLink>

      <NuxtLink to="/admin/analytics" class="block">
        <div class="bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
          <UIcon name="i-lucide-bar-chart-3" class="w-8 h-8 text-primary-500 mb-3" />
          <h3 class="font-semibold text-gray-900">Statistiques</h3>
          <p class="text-sm text-gray-500 mt-1">Performances et analytics</p>
        </div>
      </NuxtLink>

      <NuxtLink to="/admin/settings" class="block">
        <div class="bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
          <UIcon name="i-lucide-settings" class="w-8 h-8 text-primary-500 mb-3" />
          <h3 class="font-semibold text-gray-900">Paramètres</h3>
          <p class="text-sm text-gray-500 mt-1">Configuration plateforme</p>
        </div>
      </NuxtLink>
    </div>
  </div>
</template>
