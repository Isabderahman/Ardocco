import type { BackendResponse, LaravelPage } from '~/types/models/api'
import type { BackendListing } from '~/types/models/listing'

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
