import type { LoginResponse, RegisterData, RegisterResponse } from '~/types/models/auth'

const DEFAULT_API_URL = '/api/backend'

export type LoginPayload = {
  email: string
  password: string
  device_name?: string
}

export type RegisterPayload = RegisterData

export type SignContractPayload = {
  token: string
  signature: string
  accept_terms: boolean
}

export type UpdateProfilePayload = {
  first_name?: string
  last_name?: string
  phone?: string | null
  company_name?: string | null
  address?: string | null
  city?: string | null
  cin?: string | null
}

export type UpdatePasswordPayload = {
  current_password: string
  password: string
  password_confirmation: string
}

export const authService = {
  login(payload: LoginPayload, apiBaseUrl: string = DEFAULT_API_URL) {
    return $fetch<LoginResponse>(`${apiBaseUrl}/auth/login`, {
      method: 'POST',
      body: payload
    })
  },

  register(payload: RegisterPayload, apiBaseUrl: string = DEFAULT_API_URL) {
    return $fetch<RegisterResponse>(`${apiBaseUrl}/auth/register`, {
      method: 'POST',
      body: payload
    })
  },

  logout(apiBaseUrl: string = DEFAULT_API_URL) {
    return $fetch(`${apiBaseUrl}/auth/logout`, { method: 'POST' })
  },

  signContract(payload: SignContractPayload, apiBaseUrl: string = DEFAULT_API_URL) {
    return $fetch(`${apiBaseUrl}/auth/sign-contract`, {
      method: 'POST',
      body: payload
    })
  },

  updateProfile(payload: UpdateProfilePayload, token: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return $fetch<{ success: boolean; message?: string; user?: import('~/types/models/auth').AuthUser }>(`${apiBaseUrl}/auth/profile`, {
      method: 'PUT',
      body: payload,
      headers: token ? { Authorization: `Bearer ${token}` } : undefined
    })
  },

  updatePassword(payload: UpdatePasswordPayload, token: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return $fetch<{ success: boolean; message?: string }>(`${apiBaseUrl}/auth/password`, {
      method: 'PUT',
      body: payload,
      headers: token ? { Authorization: `Bearer ${token}` } : undefined
    })
  }
}
