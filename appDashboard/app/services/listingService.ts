import type { BackendResponse, LaravelPage } from '~/types/models/api'
import type { BackendListing, CreateListingPayload, CreateListingResponse, PublicListingResponse, PublicListingsResponse } from '~/types/models/listing'

const DEFAULT_API_URL = '/api/backend'

function authHeaders(token?: string | null): Record<string, string> | undefined {
  const normalized = typeof token === 'string' ? token.trim() : ''
  if (!normalized) return undefined
  return { Authorization: `Bearer ${normalized}` }
}

export const listingService = {
  listingsUrl(apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/listings`
  },

  submitListingUrl(id: string, apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/listings/${encodeURIComponent(id)}/submit`
  },

  publicListingsUrl(apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/public/listings`
  },

  publicListingUrl(id: string, apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/public/listings/${encodeURIComponent(id)}`
  },

  async fetchPublicListings(
    query?: Record<string, unknown>,
    token?: string | null,
    apiBaseUrl: string = DEFAULT_API_URL
  ) {
    return await $fetch<PublicListingsResponse>(this.publicListingsUrl(apiBaseUrl), {
      query,
      headers: authHeaders(token)
    })
  },

  async fetchPublicListing(id: string, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<PublicListingResponse>(this.publicListingUrl(id, apiBaseUrl), {
      headers: authHeaders(token)
    })
  },

  async fetchMyListings(
    query?: Record<string, unknown>,
    token?: string | null,
    apiBaseUrl: string = DEFAULT_API_URL
  ) {
    return await $fetch<BackendResponse<LaravelPage<BackendListing>>>(this.listingsUrl(apiBaseUrl), {
      query,
      headers: authHeaders(token)
    })
  },

  async createListing(payload: CreateListingPayload, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<CreateListingResponse>(this.listingsUrl(apiBaseUrl), {
      method: 'POST',
      body: payload,
      headers: authHeaders(token)
    })
  },

  async createListingFormData(formData: FormData, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<CreateListingResponse>(this.listingsUrl(apiBaseUrl), {
      method: 'POST',
      body: formData,
      headers: authHeaders(token)
    })
  },

  async submitListing(id: string, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<CreateListingResponse>(this.submitListingUrl(id, apiBaseUrl), {
      method: 'POST',
      headers: authHeaders(token)
    })
  }
}
