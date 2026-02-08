import type { LoginResponse } from '~/types/models/auth'

const DEFAULT_API_URL = '/api/backend'

export type LoginPayload = {
  email: string
  password: string
  device_name?: string
}

export type SignContractPayload = {
  token: string
  signature: string
  accept_terms: boolean
}

export const authService = {
  login(payload: LoginPayload, apiBaseUrl: string = DEFAULT_API_URL) {
    return $fetch<LoginResponse>(`${apiBaseUrl}/auth/login`, {
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
  }
}
