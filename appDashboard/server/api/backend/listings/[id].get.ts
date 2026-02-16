import { getCookie, getHeader } from 'h3'
import { normalizeBackendBaseUrl } from '~~/server/utils/backendBaseUrl'

export default defineEventHandler(async (event) => {
  const id = String(event.context.params?.id || '').trim()
  if (!id) {
    throw createError({
      statusCode: 400,
      statusMessage: 'Missing listing id.'
    })
  }

  const config = useRuntimeConfig()
  const backendBaseUrl = normalizeBackendBaseUrl(config.backendBaseUrl || 'http://localhost:8000')

  const url = new URL(`/api/listings/${encodeURIComponent(id)}`, backendBaseUrl)

  const headers: Record<string, string> = { accept: 'application/json' }
  const authHeader = getHeader(event, 'authorization')
  const token = getCookie(event, 'auth_token')

  if (typeof authHeader === 'string' && authHeader.length) {
    headers.authorization = authHeader
  } else if (typeof token === 'string' && token.length) {
    headers.authorization = `Bearer ${token}`
  }

  try {
    return await $fetch(url.toString(), { headers })
  } catch (err) {
    const statusCode = Number((err as { statusCode?: number }).statusCode)
    const message = (err as { data?: { message?: string } }).data?.message

    throw createError({
      statusCode: Number.isFinite(statusCode) ? statusCode : 502,
      statusMessage: message || 'Failed to fetch listing from backend.',
      cause: err,
      data: (err as { data?: unknown }).data
    })
  }
})
