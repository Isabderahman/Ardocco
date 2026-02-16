export default defineNuxtRouteMiddleware(async (to) => {
  const { token, ensureUserLoaded } = useAuth()
  const config = useRuntimeConfig()

  // Redirect to main site login if not authenticated
  const mainSiteUrl = config.public.mainSiteUrl || 'http://localhost:3000'
  const loginUrl = `${mainSiteUrl}/login`

  if (!token.value) {
    return navigateTo(loginUrl, { external: true })
  }

  try {
    const user = await ensureUserLoaded()
    if (!user) {
      return navigateTo(loginUrl, { external: true })
    }
  } catch {
    return navigateTo(loginUrl, { external: true })
  }
})

