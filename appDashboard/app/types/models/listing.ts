import type { BackendResponse, LaravelPage } from '~/types/models/api'
import type { GeoJSONPolygon } from '~/types/models/geojson'
import type { TerrainType } from '~/types/enums/terrain'

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

export type EtudeInvestissement = {
  id: string
  listing_id: string
  created_by?: string | null
  titre_projet: string | null
  type_projet: string | null
  nombre_sous_sols: number | null
  nombre_etages: number | null
  localisation: string | null
  version: string | null
  status: 'draft' | 'pending_review' | 'approved' | 'rejected'
  generated_by_ai: boolean
  taux_immatriculation: number | null
  superficie_terrain: number | null
  prix_terrain_m2: number | null
  prix_terrain_total: number | null
  frais_immatriculation: number | null
  surface_plancher_total: number | null
  surfaces_par_niveau: Record<string, number> | null
  surfaces_vendables: Record<string, { usage: string; surface: number }> | null
  cout_gros_oeuvres_m2: number | null
  cout_finition_m2: number | null
  amenagement_divers: number | null
  cout_total_travaux: number | null
  frais_groupement_etudes: number | null
  frais_autorisation_eclatement: number | null
  frais_lydec: number | null
  total_frais_construction: number | null
  total_investissement: number | null
  surface_vendable_commerce: number | null
  surface_vendable_appart: number | null
  prix_vente_m2_commerce: number | null
  prix_vente_m2_appart: number | null
  revenus_commerce: number | null
  revenus_appart: number | null
  total_revenues: number | null
  resultat_brute: number | null
  ratio: number | null
  ai_notes: string | null
  ai_extracted_data: Record<string, unknown> | null
  plans: string[] | null
  pdf_path: string | null
  pdf_generated_at: string | null
  review_notes: string | null
  reviewed_by?: string | null
  reviewed_at: string | null
  created_at: string | null
  updated_at?: string | null
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
  geojson_polygon?: GeoJSONPolygon | null
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
  cout_investissement?: number | string | null
  ratio?: number | string | null
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
  etudesInvestissement?: EtudeInvestissement[] | null
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

export type ListingVisibility = 'public' | 'private' | 'restricted'

export type CreateListingPayload = {
  reference?: string | null
  title: string
  description?: string | null
  commune_id: string
  quartier?: string | null
  address?: string | null
  latitude?: number | null
  longitude?: number | null
  geojson_polygon?: GeoJSONPolygon | null
  superficie: number
  prix_demande: number
  type_terrain: TerrainType
  visibility?: ListingVisibility | null
  is_exclusive?: boolean
  is_urgent?: boolean
}

export type CreateListingResponse = BackendResponse<BackendListing>

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
