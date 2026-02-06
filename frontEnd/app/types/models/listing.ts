export type BackendListing = {
  id: string
  reference?: string | null
  title: string
  description?: string | null
  quartier?: string | null
  address?: string | null
  latitude?: number | string | null
  longitude?: number | string | null
  superficie?: number | string | null
  prix_demande?: number | string | null
  prix_par_m2?: number | string | null
  type_terrain?: string | null
  status?: string | null
  is_exclusive?: boolean | null
  is_urgent?: boolean | null
  visibility?: string | null
  published_at?: string | null
  created_at?: string | null
  commune?: {
    name_fr?: string | null
    province?: { name_fr?: string | null }
  } | null
}

export type ListingRow = {
  id: string
  title: string
  description?: string
  location?: string
  price?: string
  badge?: string
  status?: string
  typeLabel?: string
  area?: string
  imageUrl?: string
  isExclusive?: boolean
  isUrgent?: boolean
  lat?: number
  lng?: number
}
