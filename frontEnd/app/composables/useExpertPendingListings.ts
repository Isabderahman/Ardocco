import type { Ref } from 'vue'
import { expertService } from '~/services/expertService'
import type { ExpertiseType, ExpertPendingListingsResponse } from '~/types/models/expert'
import type { BackendListing } from '~/types/models/listing'

export function useExpertPendingListings(activeType: Ref<ExpertiseType>) {
  const { token } = useAuth()
  const headers = computed(() => {
    const value = typeof token.value === 'string' ? token.value.trim() : ''
    return value ? { Authorization: `Bearer ${value}` } : undefined
  })

  const query = computed(() => ({
    type: activeType.value === 'all' ? undefined : activeType.value
  }))

  const {
    data,
    pending,
    error,
    refresh
  } = useFetch<ExpertPendingListingsResponse>(expertService.pendingListingsUrl(), {
    query,
    headers
  })

  const listings = computed<BackendListing[]>(() => data.value?.data?.data || [])

  return {
    data,
    pending,
    error,
    refresh,
    listings
  }
}
