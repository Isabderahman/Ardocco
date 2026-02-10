import type { CreateListingPayload } from '~/types/models/listing'
import { listingService } from '~/services/listingService'

type LaravelValidationError = {
  message?: string
  errors?: Record<string, string[]>
}

function extractListingId(value: unknown): string {
  if (typeof value === 'string') return value.trim()
  if (typeof value === 'number' && Number.isFinite(value)) return String(value)
  if (!value || typeof value !== 'object') return ''

  const id = (value as { id?: unknown }).id
  if (typeof id === 'string') return id.trim()
  if (typeof id === 'number' && Number.isFinite(id)) return String(id)
  return ''
}

function extractListingFromResponse(value: unknown): { id: string } | null {
  const queue: Array<{ value: unknown, depth: number }> = [{ value, depth: 0 }]
  const seen = new Set<unknown>()

  while (queue.length) {
    const item = queue.shift()
    if (!item) break
    if (item.value && typeof item.value === 'object') {
      if (seen.has(item.value)) continue
      seen.add(item.value)
    }

    const id = extractListingId(item.value)
    if (id) return { id }

    if (item.depth >= 4) continue
    if (!item.value || typeof item.value !== 'object') continue

    // Traverse arrays by checking the first element.
    // We only ever reach arrays through envelope keys like `data`, so the first element is typically the "latest" item
    // (ex: Laravel paginator `data: [...]` ordered desc).
    if (Array.isArray(item.value) && item.value.length > 0) {
      const nextDepth = item.depth + 1
      queue.push({ value: item.value[0], depth: nextDepth })
      continue
    }

    const obj = item.value as Record<string, unknown>
    const nextDepth = item.depth + 1

    // Common API envelope keys
    for (const key of ['data', 'listing', 'result', 'payload']) {
      if (key in obj) queue.push({ value: obj[key], depth: nextDepth })
    }
  }

  return null
}

export function useCreateListing() {
  const { token } = useAuth()
  const { canCreateListing, isVendeur } = useAccess()

  const pending = ref(false)
  const error = ref<string | null>(null)
  const fieldErrors = ref<Record<string, string[]> | null>(null)

  const authHeader = computed(() => {
    const value = typeof token.value === 'string' ? token.value.trim() : ''
    return value ? { Authorization: `Bearer ${value}` } : undefined
  })

  async function fetchLatestListingIdFallback(): Promise<{ id: string } | null> {
    try {
      const res = await $fetch(listingService.listingsUrl(), {
        query: { per_page: 1 },
        headers: authHeader.value
      })
      return extractListingFromResponse(res)
    } catch {
      return null
    }
  }

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

      const created = extractListingFromResponse(res)
      if (!created) {
        const keys = res && typeof res === 'object' ? Object.keys(res as object).join(', ') : typeof res
        const dataValue = (res && typeof res === 'object') ? (res as { data?: unknown }).data : undefined
        const dataType = Array.isArray(dataValue) ? `array(len=${dataValue.length})` : typeof dataValue
        const dataKeys = (dataValue && typeof dataValue === 'object' && !Array.isArray(dataValue))
          ? Object.keys(dataValue as object).slice(0, 25).join(', ')
          : ''

        const fallback = await fetchLatestListingIdFallback()
        if (fallback) return fallback

        throw new Error(
          `Unable to create listing: invalid response payload (keys: ${keys}; dataType: ${dataType}${dataKeys ? `; dataKeys: ${dataKeys}` : ''}).`
        )
      }

      if (isVendeur.value) {
        const submitRes = await listingService.submitListing(created.id, token.value)
        if (submitRes?.success) {
          const submitted = extractListingFromResponse(submitRes)
          return submitted || created
        }
      }

      return created
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

      const created = extractListingFromResponse(res)
      if (!created) {
        const keys = res && typeof res === 'object' ? Object.keys(res as object).join(', ') : typeof res
        const dataValue = (res && typeof res === 'object') ? (res as { data?: unknown }).data : undefined
        const dataType = Array.isArray(dataValue) ? `array(len=${dataValue.length})` : typeof dataValue
        const dataKeys = (dataValue && typeof dataValue === 'object' && !Array.isArray(dataValue))
          ? Object.keys(dataValue as object).slice(0, 25).join(', ')
          : ''

        const fallback = await fetchLatestListingIdFallback()
        if (fallback) return fallback

        throw new Error(
          `Unable to create listing: invalid response payload (keys: ${keys}; dataType: ${dataType}${dataKeys ? `; dataKeys: ${dataKeys}` : ''}).`
        )
      }

      if (isVendeur.value) {
        const submitRes = await listingService.submitListing(created.id, token.value)
        if (submitRes?.success) {
          const submitted = extractListingFromResponse(submitRes)
          return submitted || created
        }
      }

      return created
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
