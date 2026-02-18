export default defineNuxtRouteMiddleware(async () => {
  const { token, ensureUserLoaded } = useAuth()
  const config = useRuntimeConfig()

  if (!token.value) return

  function resolveDashboardBase(): string | null {
    const host = import.meta.client ? window.location.hostname : useRequestURL().hostname

    // 1. Production: ardocco.com -> app.ardocco.com
    if (host === 'ardocco.com' || host === 'www.ardocco.com') {
      return 'https://app.ardocco.com'
    }

    // 2. Local development fallback
    if (host === 'localhost' || host === '127.0.0.1') {
      const protocol = import.meta.client ? window.location.protocol : 'http:'
      return `${protocol}//${host}:8002`
    }

    // 3. Check explicit config for other environments (staging, etc.)
    const explicit = config.public.dashboardUrl
    if (typeof explicit === 'string' && explicit.trim() && /^https?:\/\//i.test(explicit)) {
      const configUrl = explicit.trim().replace(/\/+$/, '')
      if (!configUrl.includes('127.0.0.1') && !configUrl.includes('localhost')) {
        return configUrl
      }
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
