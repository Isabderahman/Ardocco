export default defineEventHandler(async (event) => {
  const provinceCode = String(event.context.params?.provinceCode || '').trim()
  if (!provinceCode) {
    throw createError({
      statusCode: 400,
      statusMessage: 'Missing provinceCode parameter.'
    })
  }

  const config = useRuntimeConfig()
  const backendBaseUrlRaw = String(config.backendBaseUrl || 'http://localhost:8000').trim()
  const backendBaseUrl = /^https?:\/\//.test(backendBaseUrlRaw)
    ? backendBaseUrlRaw
    : `http://${backendBaseUrlRaw}`

  const url = new URL(`/api/geo/communes/${encodeURIComponent(provinceCode)}`, backendBaseUrl)

  try {
    return await $fetch(url.toString())
  } catch (err) {
    throw createError({
      statusCode: 502,
      statusMessage: 'Failed to fetch communes from backend.',
      cause: err
    })
  }
})
