import { getCookie, getHeader, getQuery } from 'h3'

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const backendBaseUrlRaw = String(config.backendBaseUrl || 'http://localhost:8000').trim()
  const backendBaseUrl = /^https?:\/\//.test(backendBaseUrlRaw)
    ? backendBaseUrlRaw
    : `http://${backendBaseUrlRaw}`

  const url = new URL('/api/public/listings', backendBaseUrl)

  const query = getQuery(event)
  for (const [key, value] of Object.entries(query)) {
    if (Array.isArray(value)) {
      value.forEach(v => url.searchParams.append(key, String(v)))
      continue
    }

    if (value === undefined || value === null) continue
    url.searchParams.set(key, String(value))
  }

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
      statusMessage: message || 'Failed to fetch listings from backend.',
      cause: err,
      data: (err as { data?: unknown }).data
    })
  }
})

