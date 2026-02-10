import type { BackendResponse, LaravelPage } from '~/types/models/api'
import type { BackendListing } from '~/types/models/listing'

const DEFAULT_API_URL = '/api/backend'

function authHeaders(token?: string | null): Record<string, string> | undefined {
  const normalized = typeof token === 'string' ? token.trim() : ''
  if (!normalized) return undefined
  return { Authorization: `Bearer ${normalized}` }
}

export type AgentListingsResponse = BackendResponse<LaravelPage<BackendListing>>

export const agentService = {
  listingsUrl(apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/agent/listings`
  },

  async fetchListings(
    query?: Record<string, unknown>,
    token?: string | null,
    apiBaseUrl: string = DEFAULT_API_URL
  ) {
    return await $fetch<AgentListingsResponse>(this.listingsUrl(apiBaseUrl), {
      query,
      headers: authHeaders(token)
    })
  },

  async approveListing(id: string, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<BackendResponse<BackendListing>>(`${apiBaseUrl}/agent/listings/${id}/approve`, {
      method: 'POST',
      headers: authHeaders(token)
    })
  },

  async rejectListing(id: string, reason: string, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<BackendResponse<BackendListing>>(`${apiBaseUrl}/agent/listings/${id}/reject`, {
      method: 'POST',
      body: { reason },
      headers: authHeaders(token)
    })
  },

  async requestRevision(id: string, message: string, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<BackendResponse<BackendListing>>(`${apiBaseUrl}/agent/listings/${id}/request-revision`, {
      method: 'POST',
      body: { message },
      headers: authHeaders(token)
    })
  },

  async publishListing(id: string, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<BackendResponse<BackendListing>>(`${apiBaseUrl}/agent/listings/${id}/publish`, {
      method: 'POST',
      headers: authHeaders(token)
    })
  }
}
