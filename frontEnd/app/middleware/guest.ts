export default defineNuxtRouteMiddleware(async () => {
  const { token, ensureUserLoaded } = useAuth()

  if (!token.value) return

  try {
    const user = await ensureUserLoaded()
    if (user) {
      return navigateTo('/', { replace: true })
    }
  } catch {
    // If token is invalid/expired, `ensureUserLoaded()` will clear it and login stays accessible.
  }
})

