import { normalizeBackendBaseUrl } from '~~/server/utils/backendBaseUrl'

export default defineEventHandler(async (event) => {
  const regionCode = String(event.context.params?.regionCode || '').trim()
  if (!regionCode) {
    throw createError({
      statusCode: 400,
      statusMessage: 'Missing regionCode parameter.'
    })
  }

  const config = useRuntimeConfig()
  const backendBaseUrl = normalizeBackendBaseUrl(config.backendBaseUrl || 'http://localhost:8000')

  const url = new URL(`/api/geo/provinces/${encodeURIComponent(regionCode)}`, backendBaseUrl)

  try {
    return await $fetch(url.toString())
  } catch (err) {
    const statusCode = Number((err as { statusCode?: number }).statusCode)
    const message = (err as { data?: { message?: string } }).data?.message

    throw createError({
      statusCode: Number.isFinite(statusCode) ? statusCode : 502,
      statusMessage: message || 'Failed to fetch provinces from backend.',
      cause: err,
      data: (err as { data?: unknown }).data
    })
  }
})
