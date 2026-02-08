export default defineNuxtRouteMiddleware(async (to) => {
  const { token, ensureUserLoaded } = useAuth()

  if (!token.value) {
    return navigateTo({ path: '/login', query: { redirect: to.fullPath } })
  }

  try {
    const user = await ensureUserLoaded()
    if (!user) {
      return navigateTo({ path: '/login', query: { redirect: to.fullPath } })
    }

    if (user.role !== 'agent' && user.role !== 'admin') {
      return navigateTo('/dashboard')
    }
  } catch {
    return navigateTo({ path: '/login', query: { redirect: to.fullPath } })
  }
})

