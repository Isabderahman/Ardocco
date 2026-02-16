import type { AdminPendingListingsResponse, AdminStatsResponse, AdminUsersResponse, AdminUserResponse } from '~/types/models/admin'
import type { BackendResponse } from '~/types/models/api'

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

  usersUrl(apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/admin/users`
  },

  userUrl(id: string, apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/admin/users/${encodeURIComponent(id)}`
  },

  userRoleUrl(id: string, apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/admin/users/${encodeURIComponent(id)}/role`
  },

  userToggleStatusUrl(id: string, apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/admin/users/${encodeURIComponent(id)}/toggle-status`
  },

  pendingListingsUrl(apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/admin/listings/pending`
  },

  approveListingUrl(id: string, apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/admin/listings/${encodeURIComponent(id)}/approve`
  },

  rejectListingUrl(id: string, apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/admin/listings/${encodeURIComponent(id)}/reject`
  },

  listingUrl(id: string, apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/listings/${encodeURIComponent(id)}`
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
  },

  async approveListing(id: string, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch(this.approveListingUrl(id, apiBaseUrl), {
      method: 'POST',
      headers: authHeaders(token)
    })
  },

  async rejectListing(id: string, reason: string, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch(this.rejectListingUrl(id, apiBaseUrl), {
      method: 'POST',
      body: { reason },
      headers: authHeaders(token)
    })
  },

  async fetchListing(id: string, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch(this.listingUrl(id, apiBaseUrl), {
      headers: authHeaders(token)
    })
  },

  async fetchUsers(
    query?: Record<string, unknown>,
    token?: string | null,
    apiBaseUrl: string = DEFAULT_API_URL
  ) {
    return await $fetch<AdminUsersResponse>(this.usersUrl(apiBaseUrl), {
      query,
      headers: authHeaders(token)
    })
  },

  async fetchUser(id: string, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<AdminUserResponse>(this.userUrl(id, apiBaseUrl), {
      headers: authHeaders(token)
    })
  },

  async updateUserRole(id: string, role: string, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<BackendResponse<unknown>>(this.userRoleUrl(id, apiBaseUrl), {
      method: 'PUT',
      body: { role },
      headers: authHeaders(token)
    })
  },

  async toggleUserStatus(id: string, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<BackendResponse<unknown>>(this.userToggleStatusUrl(id, apiBaseUrl), {
      method: 'POST',
      headers: authHeaders(token)
    })
  }
}
