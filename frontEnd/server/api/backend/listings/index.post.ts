import { getCookie, getHeader, proxyRequest } from 'h3'
import { normalizeBackendBaseUrl } from '~~/server/utils/backendBaseUrl'

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const backendBaseUrl = normalizeBackendBaseUrl(config.backendBaseUrl || 'http://localhost:8000')

  const url = new URL('/api/listings', backendBaseUrl)

  const headers: Record<string, string> = { accept: 'application/json' }

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
