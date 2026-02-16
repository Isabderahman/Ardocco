import type { Ref } from 'vue'
import { listingService } from '~/services/listingService'
import type { BackendListing, PublicListingsFilters, PublicListingsResponse } from '~/types/models/listing'

export function usePublicListings(filters: Ref<PublicListingsFilters>, perPage: number = 20) {
  const { token } = useAuth()
  const headers = computed(() => {
    const value = typeof token.value === 'string' ? token.value.trim() : ''
    return value ? { Authorization: `Bearer ${value}` } : undefined
  })

  const query = computed(() => ({
    ...Object.fromEntries(Object.entries(filters.value).filter(([_, v]) => v)),
    per_page: perPage
  }))

  const {
    data,
    pending,
    error,
    refresh
  } = useFetch<PublicListingsResponse>(listingService.publicListingsUrl(), {
    query,
    headers
  })

  const listings = computed<BackendListing[]>(() => data.value?.data?.data || [])
  const isAuthenticated = computed(() => data.value?.is_authenticated || false)

  return {
    data,
    pending,
    error,
    refresh,
    listings,
    isAuthenticated
  }
}
