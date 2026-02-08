import type { BackendResponse, LaravelPage } from '~/types/models/api'

export type FicheTechnique = {
  id?: string
  listing_id?: string
  accessibility?: string | null
  neighborhood?: string | null
  technical_constraints?: string[] | null
  opportunities?: string | null
  equipment?: string[] | null
  photo_analysis?: string | null
  expert_notes?: string | null
  conclusion?: string | null
  rating?: number | null
  validated_by?: string | null
  validated_at?: string | null
}

export type FicheFinanciere = {
  id?: string
  listing_id?: string
  estimated_market_price?: number | null
  price_per_sqm?: number | null
  comparables?: object[] | null
  valuation_assumptions?: string | null
  development_costs?: number | null
  projected_sale_price?: number | null
  taxes_fees?: number | null
  rentabilite?: number | null
  expert_notes?: string | null
  conclusion?: string | null
  rating?: number | null
  validated_by?: string | null
  validated_at?: string | null
}

export type FicheJuridique = {
  id?: string
  listing_id?: string
  land_status?: string | null
  title_number?: string | null
  legal_owner?: string | null
  servitudes?: string[] | null
  restrictions?: string[] | null
  legal_issues?: string | null
  missing_documents?: string[] | null
  compliance_status?: 'conforme' | 'non_conforme' | 'en_cours' | null
  expert_notes?: string | null
  conclusion?: string | null
  rating?: number | null
  validated_by?: string | null
  validated_at?: string | null
}

export type ListingOwner = {
  id: string
  first_name?: string | null
  last_name?: string | null
  email?: string | null
  phone?: string | null
  company_name?: string | null
}

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
  prix_estime?: number | string | null
  prix_par_m2?: number | string | null
  type_terrain?: string | null
  status?: string | null
  titre_foncier?: string | null
  forme_terrain?: string | null
  topographie?: string | null
  viabilisation?: string[] | null
  zonage?: string | null
  coefficient_occupation?: number | string | null
  hauteur_max?: number | string | null
  is_exclusive?: boolean | null
  is_urgent?: boolean | null
  is_featured?: boolean | null
  visibility?: string | null
  views_count?: number | null
  submitted_at?: string | null
  validated_at?: string | null
  published_at?: string | null
  created_at?: string | null
  updated_at?: string | null
  owner_id?: string | null
  agent_id?: string | null
  commune?: {
    id?: number
    name_fr?: string | null
    name_ar?: string | null
    province?: {
      name_fr?: string | null
      region?: { name_fr?: string | null }
    }
  } | null
  owner?: ListingOwner | null
  agent?: ListingOwner | null
  ficheTechnique?: FicheTechnique | null
  ficheFinanciere?: FicheFinanciere | null
  ficheJuridique?: FicheJuridique | null
  documents?: object[] | null
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

export type PublicListingAccessLevel = 'full' | 'limited'

export type PublicListingsResponse = BackendResponse<LaravelPage<BackendListing>> & {
  is_authenticated: boolean
}

export type PublicListingResponse = BackendResponse<BackendListing> & {
  access_level: PublicListingAccessLevel
}

export type PublicListingsFilters = {
  q: string
  type_terrain: string
  prix_min: string
  prix_max: string
  superficie_min: string
  superficie_max: string
  rentabilite_min: string
  sort: string
}
