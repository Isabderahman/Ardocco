import type { AdminPendingListingsResponse, AdminStatsResponse } from '~/types/models/admin'

const DEFAULT_API_URL = '/api/backend'

function authHeaders(token?: string | null): Record<string, string> | undefined {
  const normalized = typeof token === 'string' ? token.trim() : ''
  if (!normalized) return undefined
  return { Authorization: `Bearer ${normalized}` }
}

export const adminService = {
  statsUrl(apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/admin/stats`
  },

  pendingListingsUrl(apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/admin/listings/pending`
  },

  async fetchStats(token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<AdminStatsResponse>(this.statsUrl(apiBaseUrl), {
      headers: authHeaders(token)
    })
  },

  async fetchPendingListings(
    query?: Record<string, unknown>,
    token?: string | null,
    apiBaseUrl: string = DEFAULT_API_URL
  ) {
    return await $fetch<AdminPendingListingsResponse>(this.pendingListingsUrl(apiBaseUrl), {
      query,
      headers: authHeaders(token)
    })
  }
}
