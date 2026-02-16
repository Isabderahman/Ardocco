import type { Ref } from 'vue'
import { expertService } from '~/services/expertService'
import type { ExpertiseType } from '~/types/models/expert'
import type { BackendListing } from '~/types/models/listing'

export function useExpertPendingListings(activeType: Ref<ExpertiseType>) {
  const { token } = useAuth()

  const listings = ref<BackendListing[]>([])
  const pending = ref(true)
  const error = ref<Error | null>(null)

  async function refresh() {
    if (!token.value) {
      pending.value = false
      return
    }

    pending.value = true
    error.value = null
    try {
      const res = await expertService.fetchPendingListings(activeType.value, token.value)
      if (res.success && res.data) {
        listings.value = res.data.data || []
      }
    } catch (err) {
      error.value = err as Error
    } finally {
      pending.value = false
    }
  }

  watch(activeType, () => {
    refresh()
  })

  watch(token, (newToken) => {
    if (newToken) {
      refresh()
    }
  }, { immediate: true })

  return {
    pending,
    error,
    refresh,
    listings
  }
}
