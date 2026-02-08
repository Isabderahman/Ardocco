import type { AuthUser } from '~/types/models/auth'
import { authService } from '~/services/authService'

export function useAuth() {
  const token = useCookie<string | null>('auth_token', {
    sameSite: 'lax',
    path: '/'
  })

  const user = useState<AuthUser | null>('auth_user', () => null)
  const isAuthenticated = computed(() => Boolean(token.value))

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

  async function logout() {
    try {
      await authService.logout()
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
