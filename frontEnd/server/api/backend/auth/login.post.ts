import { readBody } from 'h3'

type LoginPayload = {
  email: string
  password: string
  device_name?: string
}

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const backendBaseUrlRaw = String(config.backendBaseUrl || 'http://localhost:8000').trim()
  const backendBaseUrl = /^https?:\/\//.test(backendBaseUrlRaw)
    ? backendBaseUrlRaw
    : `http://${backendBaseUrlRaw}`

  const payload = await readBody<LoginPayload>(event)
  const url = new URL('/api/auth/login', backendBaseUrl)

  try {
    return await $fetch(url.toString(), {
      method: 'POST',
      body: payload
    })
  } catch (err) {
    const statusCode = Number((err as { statusCode?: number }).statusCode)
    const message = (err as { data?: { message?: string } }).data?.message

    throw createError({
      statusCode: Number.isFinite(statusCode) ? statusCode : 502,
      statusMessage: message || 'Failed to login via backend.',
      cause: err,
      data: (err as { data?: unknown }).data
    })
  }
})
