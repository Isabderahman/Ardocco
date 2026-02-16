import type {
  ContactRequestCreatePayload,
  ContactRequestCreateResponse,
  ContactRequestsIndexResponse
} from '~/types/models/contact-request'

const DEFAULT_API_URL = '/api/backend'

function authHeaders(token?: string | null): Record<string, string> | undefined {
  const normalized = typeof token === 'string' ? token.trim() : ''
  if (!normalized) return undefined
  return { Authorization: `Bearer ${normalized}` }
}

export const contactRequestService = {
  indexUrl(apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/contact-requests`
  },

  async fetchIndex(
    query?: Record<string, unknown>,
    token?: string | null,
    apiBaseUrl: string = DEFAULT_API_URL
  ) {
    return await $fetch<ContactRequestsIndexResponse>(this.indexUrl(apiBaseUrl), {
      query,
      headers: authHeaders(token)
    })
  },

  async create(payload: ContactRequestCreatePayload, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<ContactRequestCreateResponse>(this.indexUrl(apiBaseUrl), {
      method: 'POST',
      headers: authHeaders(token),
      body: payload
    })
  }
}
