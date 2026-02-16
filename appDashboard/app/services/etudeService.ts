import type { BackendResponse } from '~/types/models/api'
import type { EtudeInvestissement } from '~/types/models/listing'

const DEFAULT_API_URL = '/api/backend'

function authHeaders(token?: string | null): Record<string, string> | undefined {
  const normalized = typeof token === 'string' ? token.trim() : ''
  if (!normalized) return undefined
  return { Authorization: `Bearer ${normalized}` }
}

export type EtudeListResponse = BackendResponse<EtudeInvestissement[]>
export type EtudeResponse = BackendResponse<EtudeInvestissement> & { formatted?: Record<string, unknown> }
export type AISuggestionsResponse = BackendResponse<{
  type_projet_suggere?: string
  nombre_etages_recommande?: number
  nombre_sous_sols_recommande?: number
  surfaces_par_niveau?: Record<string, number>
  couts_estimes?: {
    gros_oeuvres_m2?: number
    finition_m2?: number
    amenagement_divers?: number
  }
  prix_vente_estimes?: {
    m2_commerce?: number
    m2_appart?: number
  }
  recommandations?: string[]
  risques?: string[]
}>

export type PlanAnalysisResponse = BackendResponse<{
  surfaces_detectees?: Record<string, number>
  type_projet?: string
  nombre_etages?: number
  observations?: string[]
}>

export type PdfResponse = BackendResponse<{
  path: string
  url: string
}>

export type EtudeUpdatePayload = {
  titre_projet?: string
  type_projet?: string
  nombre_sous_sols?: number
  nombre_etages?: number
  localisation?: string
  version?: string
  superficie_terrain?: number
  prix_terrain_m2?: number
  taux_immatriculation?: number
  surfaces_par_niveau?: Record<string, number>
  cout_gros_oeuvres_m2?: number
  cout_finition_m2?: number
  amenagement_divers?: number
  frais_groupement_etudes?: number
  frais_autorisation_eclatement?: number
  frais_lydec?: number
  surfaces_vendables?: Record<string, { usage: string; surface: number }>
  surface_vendable_commerce?: number
  surface_vendable_appart?: number
  prix_vente_m2_commerce?: number
  prix_vente_m2_appart?: number
}

export const etudeService = {
  etudesUrl(listingId: string, apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/listings/${encodeURIComponent(listingId)}/etudes`
  },

  etudeUrl(listingId: string, etudeId: string, apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/listings/${encodeURIComponent(listingId)}/etudes/${encodeURIComponent(etudeId)}`
  },

  suggestionsUrl(listingId: string, apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/listings/${encodeURIComponent(listingId)}/etudes/suggestions`
  },

  analyzePlansUrl(listingId: string, apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/listings/${encodeURIComponent(listingId)}/etudes/analyze-plans`
  },

  generatePdfUrl(listingId: string, etudeId: string, apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/listings/${encodeURIComponent(listingId)}/etudes/${encodeURIComponent(etudeId)}/generate-pdf`
  },

  downloadPdfUrl(listingId: string, etudeId: string, apiBaseUrl: string = DEFAULT_API_URL) {
    return `${apiBaseUrl}/listings/${encodeURIComponent(listingId)}/etudes/${encodeURIComponent(etudeId)}/download-pdf`
  },

  async fetchEtudes(listingId: string, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<EtudeListResponse>(this.etudesUrl(listingId, apiBaseUrl), {
      headers: authHeaders(token)
    })
  },

  async fetchEtude(listingId: string, etudeId: string, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<EtudeResponse>(this.etudeUrl(listingId, etudeId, apiBaseUrl), {
      headers: authHeaders(token)
    })
  },

  async createEtude(listingId: string, payload: EtudeUpdatePayload, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<EtudeResponse>(this.etudesUrl(listingId, apiBaseUrl), {
      method: 'POST',
      body: payload,
      headers: authHeaders(token)
    })
  },

  async updateEtude(listingId: string, etudeId: string, payload: EtudeUpdatePayload, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<EtudeResponse>(this.etudeUrl(listingId, etudeId, apiBaseUrl), {
      method: 'PUT',
      body: payload,
      headers: authHeaders(token)
    })
  },

  async submitEtude(listingId: string, etudeId: string, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<EtudeResponse>(`${this.etudeUrl(listingId, etudeId, apiBaseUrl)}/submit`, {
      method: 'POST',
      headers: authHeaders(token)
    })
  },

  async reviewEtude(listingId: string, etudeId: string, action: 'approve' | 'reject', notes?: string, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<EtudeResponse>(`${this.etudeUrl(listingId, etudeId, apiBaseUrl)}/review`, {
      method: 'POST',
      body: { action, notes },
      headers: authHeaders(token)
    })
  },

  async getAISuggestions(listingId: string, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<AISuggestionsResponse>(this.suggestionsUrl(listingId, apiBaseUrl), {
      headers: authHeaders(token)
    })
  },

  async analyzePlans(listingId: string, plans: File[], context?: string, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    const formData = new FormData()
    plans.forEach((plan, index) => {
      formData.append(`plans[${index}]`, plan)
    })
    if (context) {
      formData.append('context', context)
    }

    return await $fetch<PlanAnalysisResponse>(this.analyzePlansUrl(listingId, apiBaseUrl), {
      method: 'POST',
      body: formData,
      headers: authHeaders(token)
    })
  },

  async generatePdf(listingId: string, etudeId: string, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<PdfResponse>(this.generatePdfUrl(listingId, etudeId, apiBaseUrl), {
      method: 'POST',
      headers: authHeaders(token)
    })
  },

  async deleteEtude(listingId: string, etudeId: string, token?: string | null, apiBaseUrl: string = DEFAULT_API_URL) {
    return await $fetch<BackendResponse<null>>(this.etudeUrl(listingId, etudeId, apiBaseUrl), {
      method: 'DELETE',
      headers: authHeaders(token)
    })
  }
}
