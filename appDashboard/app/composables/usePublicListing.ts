import type { Ref } from 'vue'
import { listingService } from '~/services/listingService'
import type { BackendListing, PublicListingAccessLevel, PublicListingResponse } from '~/types/models/listing'

export function usePublicListing(listingId: Ref<string>) {
  const { token } = useAuth()
  const headers = computed(() => {
    const value = typeof token.value === 'string' ? token.value.trim() : ''
    return value ? { Authorization: `Bearer ${value}` } : undefined
  })

  const {
    data,
    pending,
    error,
    refresh
  } = useFetch<PublicListingResponse>(
    () => listingService.publicListingUrl(listingId.value),
    {
      watch: [listingId],
      headers
    }
  )

  const listing = computed<BackendListing | undefined>(() => data.value?.data)
  const accessLevel = computed<PublicListingAccessLevel>(() => data.value?.access_level || 'limited')
  const hasFullAccess = computed(() => accessLevel.value === 'full')

  return {
    data,
    pending,
    error,
    refresh,
    listing,
    accessLevel,
    hasFullAccess
  }
}
