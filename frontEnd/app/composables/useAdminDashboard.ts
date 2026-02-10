import { adminService } from '~/services/adminService'
import type { AdminStatsResponse } from '~/types/models/admin'
import type { BackendListing } from '~/types/models/listing'

export function useAdminDashboard() {
  const { token } = useAuth()

  const stats = ref<AdminStatsResponse['data'] | null>(null)
  const pendingListings = ref<BackendListing[]>([])
  const statsPending = ref(true)
  const pendingPending = ref(true)
  const statsError = ref<Error | null>(null)
  const pendingError = ref<Error | null>(null)

  async function refreshStats() {
    statsPending.value = true
    statsError.value = null
    try {
      const res = await adminService.fetchStats(token.value)
      if (res.success && res.data) {
        stats.value = res.data
      }
    } catch (err) {
      statsError.value = err as Error
    } finally {
      statsPending.value = false
    }
  }

  async function refreshPending() {
    pendingPending.value = true
    pendingError.value = null
    try {
      const res = await adminService.fetchPendingListings({}, token.value)
      if (res.success && res.data) {
        pendingListings.value = res.data.data || []
      }
    } catch (err) {
      pendingError.value = err as Error
    } finally {
      pendingPending.value = false
    }
  }

  async function fetchAll() {
    await Promise.all([refreshStats(), refreshPending()])
  }

  onMounted(() => {
    fetchAll()
  })

  return {
    stats,
    pendingListings,
    statsPending,
    statsError,
    refreshStats,
    pendingPending,
    pendingError,
    refreshPending,
    fetchAll
  }
}
