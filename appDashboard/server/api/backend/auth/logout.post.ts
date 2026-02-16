import { getCookie, getHeader } from 'h3'
import { normalizeBackendBaseUrl } from '~~/server/utils/backendBaseUrl'

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const backendBaseUrl = normalizeBackendBaseUrl(config.backendBaseUrl || 'http://localhost:8000')

  const url = new URL('/api/auth/logout', backendBaseUrl)

  const headers: Record<string, string> = { accept: 'application/json' }
  const authHeader = getHeader(event, 'authorization')
  const token = getCookie(event, 'auth_token')

  if (typeof authHeader === 'string' && authHeader.length) {
    headers.authorization = authHeader
  } else if (typeof token === 'string' && token.length) {
    headers.authorization = `Bearer ${token}`
  }

  try {
    return await $fetch(url.toString(), {
      method: 'POST',
      headers
    })
  } catch (err) {
    const statusCode = Number((err as { statusCode?: number }).statusCode)
    const message = (err as { data?: { message?: string } }).data?.message

    throw createError({
      statusCode: Number.isFinite(statusCode) ? statusCode : 502,
      statusMessage: message || 'Failed to logout via backend.',
      cause: err,
      data: (err as { data?: unknown }).data
    })
  }
})
