export default defineEventHandler(async (event) => {
  const regionCode = String(event.context.params?.regionCode || '').trim()
  if (!regionCode) {
    throw createError({
      statusCode: 400,
      statusMessage: 'Missing regionCode parameter.'
    })
  }

  const config = useRuntimeConfig()
  const backendBaseUrlRaw = String(config.backendBaseUrl || 'http://localhost:8000').trim()
  const backendBaseUrl = /^https?:\/\//.test(backendBaseUrlRaw)
    ? backendBaseUrlRaw
    : `http://${backendBaseUrlRaw}`

  const url = new URL(`/api/geo/provinces/${encodeURIComponent(regionCode)}`, backendBaseUrl)

  try {
    return await $fetch(url.toString())
  } catch (err) {
    throw createError({
      statusCode: 502,
      statusMessage: 'Failed to fetch provinces from backend.',
      cause: err
    })
  }
})
