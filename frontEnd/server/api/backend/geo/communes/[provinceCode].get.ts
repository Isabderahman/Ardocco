import { normalizeBackendBaseUrl } from '~~/server/utils/backendBaseUrl'

export default defineEventHandler(async (event) => {
  const provinceCode = String(event.context.params?.provinceCode || '').trim()
  if (!provinceCode) {
    throw createError({
      statusCode: 400,
      statusMessage: 'Missing provinceCode parameter.'
    })
  }

  const config = useRuntimeConfig()
  const backendBaseUrl = normalizeBackendBaseUrl(config.backendBaseUrl || 'http://localhost:8000')

  const url = new URL(`/api/geo/communes/${encodeURIComponent(provinceCode)}`, backendBaseUrl)

  try {
    return await $fetch(url.toString())
  } catch (err) {
    const statusCode = Number((err as { statusCode?: number }).statusCode)
    const message = (err as { data?: { message?: string } }).data?.message

    throw createError({
      statusCode: Number.isFinite(statusCode) ? statusCode : 502,
      statusMessage: message || 'Failed to fetch communes from backend.',
      cause: err,
      data: (err as { data?: unknown }).data
    })
  }
})
