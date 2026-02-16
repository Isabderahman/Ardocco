export type UserRole = 'acheteur' | 'vendeur' | 'agent' | 'expert' | 'admin' | 'promoteur'

export type AuthUser = {
  id: string
  email: string
  role: UserRole
  first_name?: string | null
  last_name?: string | null
  phone?: string | null
  company_name?: string | null
  address?: string | null
  city?: string | null
  cin?: string | null
  is_verified?: boolean
  is_active?: boolean
}

export type LoginResponse = {
  success: boolean
  message?: string
  token_type?: string
  token?: string
  user?: AuthUser
}

export type RegisterData = {
  email: string
  password: string
  password_confirmation: string
  role: UserRole
  first_name: string
  last_name: string
  phone?: string
  company_name?: string
  address?: string
  city?: string
  cin?: string
  device_name?: string
}

export type RegisterResponse = {
  success: boolean
  message?: string
  account_status?: 'pending_contract' | 'pending_approval' | 'active'
  token_type?: string
  token?: string
  user?: Partial<AuthUser> & { account_status?: string }
}

export const ROLE_LABELS: Record<UserRole, string> = {
  acheteur: 'Acheteur',
  vendeur: 'Vendeur',
  agent: 'Agent',
  expert: 'Expert',
  admin: 'Administrateur',
  promoteur: 'Promoteur'
}

export const ROLE_DESCRIPTIONS: Record<UserRole, string> = {
  acheteur: 'Recherchez et achetez des terrains',
  vendeur: 'Publiez et gérez vos annonces',
  agent: 'Gérez les demandes et accompagnez les clients',
  expert: 'Validez les expertises techniques, financières et juridiques',
  admin: 'Gérez la plateforme et les utilisateurs',
  promoteur: 'Investissez et développez des projets immobiliers'
}
