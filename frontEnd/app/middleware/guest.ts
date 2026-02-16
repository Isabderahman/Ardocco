export default defineNuxtRouteMiddleware(async () => {
  const { token, ensureUserLoaded } = useAuth()
  const config = useRuntimeConfig()
  const requestUrl = useRequestURL()

  if (!token.value) return

  function normalizeExternalUrl(value: unknown): string | null {
    if (typeof value !== 'string') return null
    const trimmed = value.trim()
    if (!trimmed) return null
    if (!/^https?:\/\//i.test(trimmed)) return null
    return trimmed.replace(/\/+$/, '')
  }

  function resolveDashboardBase(): string | null {
    const external = normalizeExternalUrl(config.public.dashboardUrl)
    if (external) return external

    const host = requestUrl.hostname

    // Production: ardocco.com -> app.ardocco.com
    if (host === 'ardocco.com' || host === 'www.ardocco.com') {
      return 'https://app.ardocco.com'
    }

    // Local development fallback
    if (host === 'localhost' || host === '127.0.0.1') {
      const protocol = requestUrl.protocol || 'http:'
      return `${protocol}//${host}:8002`
    }

    return null
  }

  try {
    const user = await ensureUserLoaded()
    if (user) {
      const externalDashboard = resolveDashboardBase()
      if (externalDashboard && token.value) {
        const encoded = encodeURIComponent(token.value)
        return navigateTo(`${externalDashboard}/auth/consume?token=${encoded}`, { external: true, replace: true })
      }
      return navigateTo('/', { replace: true })
    }
  } catch {
    // If token is invalid/expired, `ensureUserLoaded()` will clear it and login stays accessible.
  }
})
