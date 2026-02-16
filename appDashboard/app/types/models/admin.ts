import type { BackendResponse, LaravelPage } from '~/types/models/api'
import type { BackendListing } from '~/types/models/listing'
import type { UserRole } from '~/types/models/auth'

export type AdminUser = {
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
  account_status?: string
  created_at?: string
  updated_at?: string
  contract_signed_at?: string | null
}

export type AdminStats = {
  users: {
    total: number
    by_role: Record<string, number>
    by_status: Record<string, number>
    active: number
    pending_approval: number
    pending_contract: number
    new_this_month: number
  }
  listings: {
    total: number
    by_status: Record<string, number>
    pending_approval: number
    published: number
    new_this_month: number
    total_views: number
  }
  contact_requests: {
    total: number
    pending: number
    this_month: number
  }
}

export type AdminStatsResponse = BackendResponse<AdminStats>

export type AdminPendingListingsResponse = BackendResponse<LaravelPage<BackendListing>>

export type AdminUsersResponse = BackendResponse<LaravelPage<AdminUser>>

export type AdminUserResponse = BackendResponse<AdminUser>
