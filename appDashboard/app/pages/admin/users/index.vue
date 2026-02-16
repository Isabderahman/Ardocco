<script setup lang="ts">
import type { AdminUser } from '~/types/models/admin'
import type { UserRole } from '~/types/models/auth'
import { ROLE_LABELS } from '~/types/models/auth'
import { adminService } from '~/services/adminService'

definePageMeta({
  layout: 'dashboard',
  title: 'Gestion des utilisateurs',
  middleware: 'admin'
})

const { token } = useAuth()
const toast = useToast()

const users = ref<AdminUser[]>([])
const loading = ref(true)
const actionLoading = ref<string | null>(null)
const totalUsers = ref(0)
const currentPage = ref(1)

const roleFilter = ref<string>('')
const statusFilter = ref<string>('')
const searchQuery = ref('')

const roleOptions = [
  { label: 'Tous les roles', value: '' },
  { label: 'Acheteur', value: 'acheteur' },
  { label: 'Vendeur', value: 'vendeur' },
  { label: 'Agent', value: 'agent' },
  { label: 'Expert', value: 'expert' },
  { label: 'Admin', value: 'admin' },
  { label: 'Promoteur', value: 'promoteur' }
]

const statusOptions = [
  { label: 'Tous les statuts', value: '' },
  { label: 'Actif', value: 'active' },
  { label: 'En attente contrat', value: 'pending_contract' },
  { label: 'En attente approbation', value: 'pending_approval' },
  { label: 'Rejete', value: 'rejected' }
]

const availableRoles: UserRole[] = ['acheteur', 'vendeur', 'agent', 'expert', 'admin']

async function fetchUsers() {
  loading.value = true
  try {
    const query: Record<string, unknown> = { page: currentPage.value }
    if (roleFilter.value) query.role = roleFilter.value
    if (statusFilter.value) query.account_status = statusFilter.value
    if (searchQuery.value) query.q = searchQuery.value

    const res = await adminService.fetchUsers(query, token.value)
    if (res.success && res.data) {
      users.value = res.data.data || []
      totalUsers.value = res.data.total || 0
    }
  } catch (err) {
    console.error('Failed to fetch users:', err)
    toast.add({ title: 'Erreur lors du chargement', color: 'error' })
  } finally {
    loading.value = false
  }
}

async function updateRole(user: AdminUser, newRole: UserRole) {
  actionLoading.value = user.id
  try {
    await adminService.updateUserRole(user.id, newRole, token.value)
    user.role = newRole
    toast.add({ title: 'Role mis a jour', color: 'success' })
  } catch (err) {
    console.error('Failed to update role:', err)
    toast.add({ title: 'Erreur lors de la mise a jour', color: 'error' })
  } finally {
    actionLoading.value = null
  }
}

async function toggleStatus(user: AdminUser) {
  actionLoading.value = user.id
  try {
    await adminService.toggleUserStatus(user.id, token.value)
    user.is_active = !user.is_active
    toast.add({
      title: user.is_active ? 'Compte active' : 'Compte desactive',
      color: 'success'
    })
  } catch (err) {
    console.error('Failed to toggle status:', err)
    toast.add({ title: 'Erreur lors de la mise a jour', color: 'error' })
  } finally {
    actionLoading.value = null
  }
}

function formatDate(date: string | null | undefined): string {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

function getStatusBadge(status: string | undefined): { label: string; color: string } {
  const map: Record<string, { label: string; color: string }> = {
    active: { label: 'Actif', color: 'success' },
    pending_contract: { label: 'Contrat en attente', color: 'warning' },
    pending_approval: { label: 'Approbation en attente', color: 'info' },
    rejected: { label: 'Rejete', color: 'error' }
  }
  return map[status || ''] || { label: status || '-', color: 'neutral' }
}

function getUserName(user: AdminUser): string {
  const parts = [user.first_name, user.last_name].filter(Boolean)
  return parts.length > 0 ? parts.join(' ') : 'Sans nom'
}

watch([roleFilter, statusFilter, searchQuery], () => {
  currentPage.value = 1
  fetchUsers()
})

onMounted(() => {
  fetchUsers()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-highlighted">Gestion des utilisateurs</h1>
        <p class="text-muted mt-1">{{ totalUsers }} utilisateurs au total</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-4 bg-elevated rounded-xl p-4">
      <UInput
        v-model="searchQuery"
        placeholder="Rechercher par nom, email..."
        icon="i-lucide-search"
        class="w-full sm:w-64"
      />
      <USelect
        v-model="roleFilter"
        :items="roleOptions"
        value-key="value"
        class="w-full sm:w-48"
      />
      <USelect
        v-model="statusFilter"
        :items="statusOptions"
        value-key="value"
        class="w-full sm:w-48"
      />
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 5" :key="i" class="bg-elevated rounded-xl p-6 animate-pulse">
        <div class="flex gap-4">
          <div class="w-12 h-12 bg-muted rounded-full" />
          <div class="flex-1 space-y-3">
            <div class="h-5 bg-muted rounded w-1/4" />
            <div class="h-4 bg-muted rounded w-1/3" />
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="users.length === 0" class="bg-elevated rounded-xl p-12 text-center">
      <UIcon name="i-lucide-users" class="size-16 text-muted mx-auto mb-4" />
      <h3 class="text-xl font-semibold text-highlighted mb-2">Aucun utilisateur trouve</h3>
      <p class="text-muted">Modifiez vos filtres pour voir plus de resultats.</p>
    </div>

    <!-- Users List -->
    <div v-else class="space-y-4">
      <div
        v-for="user in users"
        :key="user.id"
        class="bg-elevated rounded-xl p-6 border border-default hover:border-primary/30 transition-colors"
      >
        <div class="flex flex-col lg:flex-row gap-6">
          <!-- Avatar -->
          <div class="shrink-0">
            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
              <UIcon name="i-lucide-user" class="size-6 text-primary" />
            </div>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-4 mb-3">
              <div>
                <h3 class="text-lg font-semibold text-highlighted">{{ getUserName(user) }}</h3>
                <p class="text-sm text-muted">{{ user.email }}</p>
              </div>
              <div class="flex items-center gap-2">
                <UBadge :color="getStatusBadge(user.account_status).color as any" variant="soft">
                  {{ getStatusBadge(user.account_status).label }}
                </UBadge>
                <UBadge v-if="!user.is_active" color="error" variant="soft">
                  Desactive
                </UBadge>
              </div>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
              <div>
                <p class="text-xs text-dimmed uppercase tracking-wider">Role</p>
                <p class="text-sm font-medium text-highlighted">{{ ROLE_LABELS[user.role] || user.role }}</p>
              </div>
              <div>
                <p class="text-xs text-dimmed uppercase tracking-wider">Telephone</p>
                <p class="text-sm text-highlighted">{{ user.phone || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-dimmed uppercase tracking-wider">Entreprise</p>
                <p class="text-sm text-highlighted">{{ user.company_name || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-dimmed uppercase tracking-wider">Inscrit le</p>
                <p class="text-sm text-highlighted">{{ formatDate(user.created_at) }}</p>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-wrap items-center gap-3">
              <USelect
                :model-value="user.role"
                :items="availableRoles.map(r => ({ label: ROLE_LABELS[r], value: r }))"
                value-key="value"
                placeholder="Changer le role"
                size="sm"
                class="w-40"
                :disabled="actionLoading === user.id"
                @update:model-value="(val: string) => updateRole(user, val as UserRole)"
              />
              <UButton
                :label="user.is_active ? 'Desactiver' : 'Activer'"
                :color="user.is_active ? 'error' : 'success'"
                variant="soft"
                size="sm"
                :icon="user.is_active ? 'i-lucide-user-x' : 'i-lucide-user-check'"
                :loading="actionLoading === user.id"
                @click="toggleStatus(user)"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="totalUsers > 20" class="flex justify-center">
      <UPagination
        v-model="currentPage"
        :total="totalUsers"
        :items-per-page="20"
        @update:model-value="fetchUsers"
      />
    </div>
  </div>
</template>
