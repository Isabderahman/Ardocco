import { getCookie, getHeader, getQuery, proxyRequest } from 'h3'
import { normalizeBackendBaseUrl } from '~~/server/utils/backendBaseUrl'

export default defineEventHandler(async (event) => {
  const rawPath = event.context.params?.path
  const path = Array.isArray(rawPath) ? rawPath.join('/') : String(rawPath || '')

  const config = useRuntimeConfig()
  const backendBaseUrl = normalizeBackendBaseUrl(config.backendBaseUrl || 'http://localhost:8000')

  const url = new URL(`/storage/${path}`.replace(/\/+$/, ''), backendBaseUrl)

  const query = getQuery(event)
  for (const [key, value] of Object.entries(query)) {
    if (Array.isArray(value)) {
      value.forEach(v => url.searchParams.append(key, String(v)))
      continue
    }
    if (value === undefined || value === null) continue
    url.searchParams.set(key, String(value))
  }

  const headers: Record<string, string> = {}

  const authHeader = getHeader(event, 'authorization')
  const token = getCookie(event, 'auth_token')
  if (typeof authHeader === 'string' && authHeader.length) {
    headers.authorization = authHeader
  } else if (typeof token === 'string' && token.length) {
    headers.authorization = `Bearer ${token}`
  }

  return await proxyRequest(event, url.toString(), {
    headers,
    streamRequest: true,
    fetchOptions: {
      ignoreResponseError: true
    }
  })
})

