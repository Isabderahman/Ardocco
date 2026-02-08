import type { ExpertPendingListingsResponse, ExpertiseType } from '~/types/models/expert'

const DEFAULT_API_URL = '/api/backend'

function authHeaders(token?: string | null): Record<string, string> | undefined {
  const normalized = typeof token === 'string' ? token.trim() : ''
  if (!normalized) return undefined
  return { Authorization: `Bearer ${normalized}` }
}

export const expertService = {
  pendingListingsUrl(apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/expert/listings`
  },

  async fetchPendingListings(
    type: ExpertiseType,
    token?: string | null,
    apiBaseUrl: string = DEFAULT_API_URL
  ) {
    return await $fetch<ExpertPendingListingsResponse>(this.pendingListingsUrl(apiBaseUrl), {
      query: {
        type: type === 'all' ? undefined : type
      },
      headers: authHeaders(token)
    })
  }
}
