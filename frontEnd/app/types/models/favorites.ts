import type { BackendResponse, LaravelPage } from '~/types/models/api'
import type { BackendListing } from '~/types/models/listing'

export type FavoritesTableRow = {
  name: string
  seller: string
  added: string
}

export type Favorite = {
  id: string
  listing_id: string
  user_id: string
  notes?: string | null
  listing?: BackendListing | null
  created_at?: string | null
  updated_at?: string | null
}

export type FavoriteCreatePayload = {
  listing_id: string
  notes?: string
}

export type FavoritesIndexResponse = BackendResponse<LaravelPage<Favorite>>

export type FavoriteCreateResponse = BackendResponse<Favorite>

export type FavoriteDeleteResponse = {
  success: boolean
  message?: string
}
