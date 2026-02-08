import type { UserRole } from '~/types/models/auth'

export function useAccess() {
  const { user, isAuthenticated } = useAuth()

  const role = computed<UserRole | null>(() => user.value?.role || null)

  const isAdmin = computed(() => role.value === 'admin')
  const isAgent = computed(() => role.value === 'agent')
  const isVendeur = computed(() => role.value === 'vendeur')
  const isPromoteur = computed(() => role.value === 'promoteur')

  const canManageListings = computed(() => isAdmin.value || isAgent.value || isVendeur.value)
  const canCreateListing = computed(() => isAdmin.value || isAgent.value || isVendeur.value)
  const canAccessAdmin = computed(() => isAdmin.value)
  const canAccessAgent = computed(() => isAdmin.value || isAgent.value)

  function hasRole(...roles: UserRole[]) {
    return Boolean(role.value && roles.includes(role.value))
  }

  return {
    isAuthenticated,
    role,
    isAdmin,
    isAgent,
    isVendeur,
    isPromoteur,
    canManageListings,
    canCreateListing,
    canAccessAdmin,
    canAccessAgent,
    hasRole
  }
}
