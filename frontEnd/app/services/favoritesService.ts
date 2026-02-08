import type {
  FavoriteCreatePayload,
  FavoriteCreateResponse,
  FavoriteDeleteResponse,
  FavoritesIndexResponse
} from '~/types/models/favorites'

const DEFAULT_API_URL = '/api/backend'

function authHeaders(token?: string | null): Record<string, string> | undefined {
  const normalized = typeof token === 'string' ? token.trim() : ''
  if (!normalized) return undefined
  return { Authorization: `Bearer ${normalized}` }
}

export const favoritesService = {
  indexUrl(apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/favorites`
  },

  itemUrl(id: string, apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/favorites/${encodeURIComponent(id)}`
  },

  async fetchIndex(
    query?: Record<string, unknown>,
    token?: string | null,
    apiBaseUrl: string = DEFAULT_API_URL
  ) {
    return await $fetch<FavoritesIndexResponse>(this.indexUrl(apiBaseUrl), {
      query,
      headers: authHeaders(token)
    })
  },

  async create(payload: FavoriteCreatePayload, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<FavoriteCreateResponse>(this.indexUrl(apiBaseUrl), {
      method: 'POST',
      headers: authHeaders(token),
      body: payload
    })
  },

  async remove(id: string, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<FavoriteDeleteResponse>(this.itemUrl(id, apiBaseUrl), {
      method: 'DELETE',
      headers: authHeaders(token)
    })
  }
}
