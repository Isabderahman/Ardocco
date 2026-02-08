import { readBody } from 'h3'
import { normalizeBackendBaseUrl } from '~~/server/utils/backendBaseUrl'

type LoginPayload = {
  email: string
  password: string
  device_name?: string
}

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const backendBaseUrl = normalizeBackendBaseUrl(config.backendBaseUrl || 'http://localhost:8000')

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
