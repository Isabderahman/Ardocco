import { adminService } from '~/services/adminService'
import type { AdminPendingListingsResponse, AdminStatsResponse } from '~/types/models/admin'
import type { BackendListing } from '~/types/models/listing'

export function useAdminDashboard() {
  const { token } = useAuth()
  const headers = computed(() => {
    const value = typeof token.value === 'string' ? token.value.trim() : ''
    return value ? { Authorization: `Bearer ${value}` } : undefined
  })

  const {
    data: statsData,
    pending: statsPending,
    error: statsError,
    refresh: refreshStats
  } = useFetch<AdminStatsResponse>(adminService.statsUrl(), { headers })

  const {
    data: pendingData,
    pending: pendingPending,
    error: pendingError,
    refresh: refreshPending
  } = useFetch<AdminPendingListingsResponse>(adminService.pendingListingsUrl(), { headers })

  const stats = computed(() => statsData.value?.data)
  const pendingListings = computed<BackendListing[]>(() => pendingData.value?.data?.data || [])

  return {
    stats,
    pendingListings,
    statsPending,
    statsError,
    refreshStats,
    pendingPending,
    pendingError,
    refreshPending
  }
}
