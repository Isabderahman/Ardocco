import type { BackendResponse, LaravelPage } from '~/types/models/api'
import type { BackendListing, ListingOwner } from '~/types/models/listing'

export type ContactRequestDocument = {
  name: string
  path: string
  size: number
  mime: string
}

export type ContactRequest = {
  id: string
  listing_id: string
  user_id: string
  name: string
  email: string
  phone?: string | null
  message: string
  documents?: ContactRequestDocument[] | null
  status?: 'pending' | 'responded' | 'closed' | string
  response?: string | null
  responded_at?: string | null
  responded_by?: string | null
  listing?: BackendListing | null
  user?: ListingOwner | null
  created_at?: string | null
  updated_at?: string | null
}

export type ContactRequestCreatePayload = {
  listing_id: string
  name: string
  email: string
  phone?: string
  message: string
}

export type ContactRequestCreateResponse = BackendResponse<ContactRequest>

export type ContactRequestsIndexResponse = BackendResponse<LaravelPage<ContactRequest>>
