import { getCookie, getHeader, getProxyRequestHeaders, getQuery, sendProxy } from 'h3'

export default defineEventHandler(async (event) => {
  const rawPath = event.context.params?.path
  const path = Array.isArray(rawPath) ? rawPath.join('/') : String(rawPath || '')
  if (!path) {
    throw createError({
      statusCode: 400,
      statusMessage: 'Missing backend path.'
    })
  }

  const config = useRuntimeConfig()
  const backendBaseUrlRaw = String(config.backendBaseUrl || 'http://localhost:8000').trim()
  const backendBaseUrl = /^https?:\/\//.test(backendBaseUrlRaw)
    ? backendBaseUrlRaw
    : `http://${backendBaseUrlRaw}`

  const url = new URL(`/api/${path}`, backendBaseUrl)

  const query = getQuery(event)
  for (const [key, value] of Object.entries(query)) {
    if (Array.isArray(value)) {
      value.forEach(v => url.searchParams.append(key, String(v)))
      continue
    }

    if (value === undefined || value === null) continue
    url.searchParams.set(key, String(value))
  }

  const headers = getProxyRequestHeaders(event, { host: false }) as Record<string, string>
  headers.accept = headers.accept || 'application/json'

  const authHeader = getHeader(event, 'authorization')
  const token = getCookie(event, 'auth_token')
  if (typeof authHeader === 'string' && authHeader.length) {
    headers.authorization = authHeader
  } else if (typeof token === 'string' && token.length) {
    headers.authorization = `Bearer ${token}`
  }

  return await sendProxy(event, url.toString(), {
    headers,
    streamRequest: true,
    fetchOptions: {
      ignoreResponseError: true
    }
  })
})
