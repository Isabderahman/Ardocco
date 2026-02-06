import type { AuthUser, LoginResponse } from '~/types/models/auth'

export function useAuth() {
  const token = useCookie<string | null>('auth_token', {
    sameSite: 'lax',
    path: '/'
  })

  const user = useState<AuthUser | null>('auth_user', () => null)
  const isAuthenticated = computed(() => Boolean(token.value))

  async function login(email: string, password: string) {
    const res = await $fetch<LoginResponse>('/api/backend/auth/login', {
      method: 'POST',
      body: {
        email,
        password,
        device_name: 'web'
      }
    })

    if (!res?.success || !res.token) {
      throw new Error(res?.message || 'Login failed.')
    }

    token.value = res.token
    user.value = res.user || null

    return res
  }

  async function logout() {
    try {
      await $fetch('/api/backend/auth/logout', { method: 'POST' })
    } finally {
      token.value = null
      user.value = null
    }
  }

  return {
    token,
    user,
    isAuthenticated,
    login,
    logout
  }
}
