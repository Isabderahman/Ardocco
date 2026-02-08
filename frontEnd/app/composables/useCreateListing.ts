import type { CreateListingPayload } from '~/types/models/listing'
import { listingService } from '~/services/listingService'

type LaravelValidationError = {
  message?: string
  errors?: Record<string, string[]>
}

export function useCreateListing() {
  const { token } = useAuth()
  const { canCreateListing } = useAccess()

  const pending = ref(false)
  const error = ref<string | null>(null)
  const fieldErrors = ref<Record<string, string[]> | null>(null)

  async function create(payload: CreateListingPayload) {
    if (!canCreateListing.value) {
      throw new Error('Forbidden.')
    }

    pending.value = true
    error.value = null
    fieldErrors.value = null

    try {
      const res = await listingService.createListing(payload, token.value)
      if (!res?.success) {
        throw new Error(res?.message || 'Unable to create listing.')
      }

      return res.data
    } catch (err) {
      const statusCode = Number((err as { statusCode?: number }).statusCode)
      const data = (err as { data?: LaravelValidationError }).data

      if (statusCode === 422 && data?.errors) {
        fieldErrors.value = data.errors
      }

      error.value = data?.message || (err instanceof Error ? err.message : 'Unable to create listing.')
      throw err
    } finally {
      pending.value = false
    }
  }

  async function createFormData(formData: FormData) {
    if (!canCreateListing.value) {
      throw new Error('Forbidden.')
    }

    pending.value = true
    error.value = null
    fieldErrors.value = null

    try {
      const res = await listingService.createListingFormData(formData, token.value)
      if (!res?.success) {
        throw new Error(res?.message || 'Unable to create listing.')
      }

      return res.data
    } catch (err) {
      const statusCode = Number((err as { statusCode?: number }).statusCode)
      const data = (err as { data?: LaravelValidationError }).data

      if (statusCode === 422 && data?.errors) {
        fieldErrors.value = data.errors
      }

      error.value = data?.message || (err instanceof Error ? err.message : 'Unable to create listing.')
      throw err
    } finally {
      pending.value = false
    }
  }

  return {
    pending,
    error,
    fieldErrors,
    create,
    createFormData
  }
}
