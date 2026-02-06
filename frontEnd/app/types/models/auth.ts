export type AuthUser = {
  id: string
  email: string
  role: string
  first_name?: string | null
  last_name?: string | null
  phone?: string | null
  company_name?: string | null
}

export type LoginResponse = {
  success: boolean
  message?: string
  token_type?: string
  token?: string
  user?: AuthUser
}
