import type { AuthUser, RegisterData, RegisterResponse } from '~/types/models/auth'
import { authService } from '~/services/authService'

type MeResponse = {
  success: boolean
  user?: AuthUser | null
}

export function useAuth() {
  const config = useRuntimeConfig()

  // Cookie domain for cross-subdomain sharing (e.g., '.ardocco.com')
  // In production, this allows app.ardocco.com to read cookies set by ardocco.com
  const cookieDomain = config.public.cookieDomain || undefined

  const token = useCookie<string | null>('auth_token', {
    sameSite: 'lax',
    path: '/',
    domain: cookieDomain,
    secure: process.env.NODE_ENV === 'production'
  })

  const user = useState<AuthUser | null>('auth_user', () => null)
  const userPending = useState<boolean>('auth_user_pending', () => false)
  const isAuthenticated = computed(() => Boolean(token.value))

  function clearSession() {
    token.value = null
    user.value = null
  }

  async function refreshUser() {
    if (!token.value) {
      user.value = null
      return null
    }

    if (userPending.value) return user.value

    userPending.value = true
    try {
      const normalized = typeof token.value === 'string' ? token.value.trim() : ''
      const res = await $fetch<MeResponse>('/api/backend/auth/me', {
        method: 'GET',
        headers: normalized ? { Authorization: `Bearer ${normalized}` } : undefined
      })
      if (!res?.success) {
        clearSession()
        return null
      }

      user.value = res.user || null
      return user.value
    } catch (err) {
      const statusCode = Number((err as { statusCode?: number }).statusCode)
      if (statusCode === 401 || statusCode === 403) {
        clearSession()
      }
      throw err
    } finally {
      userPending.value = false
    }
  }

  async function ensureUserLoaded() {
    if (!token.value) {
      user.value = null
      return null
    }
    if (user.value) return user.value
    return await refreshUser()
  }

  async function login(email: string, password: string) {
    const res = await authService.login({
      email,
      password,
      device_name: 'web'
    })

    if (!res?.success || !res.token) {
      throw new Error(res?.message || 'Login failed.')
    }

    token.value = res.token
    user.value = res.user || null

    return res
  }

  async function register(data: RegisterData): Promise<RegisterResponse> {
    const res = await authService.register({
      ...data,
      device_name: 'web'
    })

    if (!res?.success) {
      throw new Error(res?.message || 'Registration failed.')
    }

    // If token is returned (agent/expert), set session
    if (res.token) {
      token.value = res.token
      user.value = res.user as AuthUser || null
    }

    return res
  }

  async function logout() {
    try {
      await authService.logout()
    } finally {
      clearSession()
    }
  }

  return {
    token,
    user,
    userPending,
    isAuthenticated,
    ensureUserLoaded,
    refreshUser,
    login,
    register,
    logout
  }
}
