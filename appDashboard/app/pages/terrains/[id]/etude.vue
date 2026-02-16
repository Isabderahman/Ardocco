<script setup lang="ts">
import type { BackendListing, EtudeInvestissement } from '~/types/models/listing'
import type { EtudeUpdatePayload, AISuggestionsResponse } from '~/services/etudeService'
import { etudeService } from '~/services/etudeService'

definePageMeta({
  layout: 'dashboard',
  title: 'Etude d\'Investissement',
  middleware: 'auth'
})

const route = useRoute()
const router = useRouter()
const { token } = useAuth()
const { isAdmin, isAgent } = useAccess()
const toast = useToast()

const listingId = computed(() => route.params.id as string)
const listing = ref<BackendListing | null>(null)
const existingEtude = ref<EtudeInvestissement | null>(null)
const loading = ref(true)
const saving = ref(false)
const loadingAI = ref(false)
const submitting = ref(false)
const analyzingPlans = ref(false)
const planFiles = ref<File[]>([])
const planContext = ref('')

// Check access
const canEdit = computed(() => isAdmin.value || isAgent.value)
const canApprove = computed(() => isAdmin.value)
const isEditing = computed(() => !!existingEtude.value)

// Form data
const form = reactive<EtudeUpdatePayload>({
  titre_projet: '',
  type_projet: 'Immeuble R+4',
  nombre_sous_sols: 0,
  nombre_etages: 4,
  localisation: '',
  version: 'V1',
  superficie_terrain: 0,
  prix_terrain_m2: 0,
  taux_immatriculation: 5,
  surfaces_par_niveau: {},
  cout_gros_oeuvres_m2: 2500,
  cout_finition_m2: 1500,
  amenagement_divers: 100000,
  frais_groupement_etudes: 0,
  frais_autorisation_eclatement: 50000,
  frais_lydec: 100000,
  surfaces_vendables: {},
  surface_vendable_commerce: 0,
  surface_vendable_appart: 0,
  prix_vente_m2_commerce: 25000,
  prix_vente_m2_appart: 12000
})

// Niveau management
const niveaux = ref<{ key: string; surface: number; usage: 'commerce' | 'apparts' }[]>([])

// Calculated values (preview)
const calculated = computed(() => {
  const superficieTerrain = form.superficie_terrain || 0
  const prixTerrainM2 = form.prix_terrain_m2 || 0
  const tauxImmat = form.taux_immatriculation || 0
  const coutGrosOeuvres = form.cout_gros_oeuvres_m2 || 0
  const coutFinition = form.cout_finition_m2 || 0
  const amenagementDivers = form.amenagement_divers || 0
  const fraisGroupement = form.frais_groupement_etudes || 0
  const fraisAutorisation = form.frais_autorisation_eclatement || 0
  const fraisLydec = form.frais_lydec || 0
  const prixVenteCommerce = form.prix_vente_m2_commerce || 0
  const prixVenteAppart = form.prix_vente_m2_appart || 0

  // Calculate surfaces from niveaux
  let surfacePlancherTotal = 0
  let surfaceCommerce = 0
  let surfaceAppart = 0
  const surfacesParNiveau: Record<string, number> = {}
  const surfacesVendables: Record<string, { usage: string; surface: number }> = {}

  niveaux.value.forEach(n => {
    surfacePlancherTotal += n.surface
    surfacesParNiveau[n.key] = n.surface
    surfacesVendables[n.key] = { usage: n.usage, surface: n.surface }
    if (n.usage === 'commerce') {
      surfaceCommerce += n.surface
    } else {
      surfaceAppart += n.surface
    }
  })

  // Prix terrain total
  const prixTerrainTotal = superficieTerrain * prixTerrainM2

  // Frais immatriculation
  const fraisImmatriculation = prixTerrainTotal * (tauxImmat / 100)

  // Cout total travaux
  const coutParM2 = coutGrosOeuvres + coutFinition
  const coutTotalTravaux = (coutParM2 * surfacePlancherTotal) + amenagementDivers

  // Frais groupement (2.5% if not set)
  const fraisGroupementCalc = fraisGroupement || coutTotalTravaux * 0.025

  // Total frais construction
  const totalFraisConstruction = coutTotalTravaux + fraisGroupementCalc + fraisAutorisation + fraisLydec

  // Total investissement
  const totalInvestissement = prixTerrainTotal + fraisImmatriculation + totalFraisConstruction

  // Revenus
  const revenusCommerce = surfaceCommerce * prixVenteCommerce
  const revenusAppart = surfaceAppart * prixVenteAppart
  const totalRevenues = revenusCommerce + revenusAppart

  // Resultat brute
  const resultatBrute = totalRevenues - totalInvestissement

  // Ratio
  const ratio = totalInvestissement > 0 ? (resultatBrute / totalInvestissement) * 100 : 0

  return {
    surfacesParNiveau,
    surfacesVendables,
    surfacePlancherTotal,
    surfaceCommerce,
    surfaceAppart,
    prixTerrainTotal,
    fraisImmatriculation,
    coutTotalTravaux,
    fraisGroupementCalc,
    totalFraisConstruction,
    totalInvestissement,
    revenusCommerce,
    revenusAppart,
    totalRevenues,
    resultatBrute,
    ratio
  }
})

// Load data
async function fetchListing() {
  try {
    const res = await $fetch<{ success: boolean; data: BackendListing }>(`/api/backend/listings/${listingId.value}`, {
      headers: token.value ? { Authorization: `Bearer ${token.value}` } : undefined
    })
    if (res.success) {
      listing.value = res.data
      // Pre-fill from listing data
      form.titre_projet = res.data.title || ''
      form.localisation = [res.data.quartier, res.data.commune?.name_fr].filter(Boolean).join(', ')
      form.superficie_terrain = parseFloat(String(res.data.superficie || 0))
      if (res.data.prix_demande && res.data.superficie) {
        form.prix_terrain_m2 = parseFloat(String(res.data.prix_demande)) / parseFloat(String(res.data.superficie))
      }
    }
  } catch (err) {
    console.error('Failed to fetch listing:', err)
  }
}

async function fetchEtudes() {
  try {
    const res = await etudeService.fetchEtudes(listingId.value, token.value)
    if (res.success && res.data?.length) {
      // Get most recent or draft
      const draft = res.data.find(e => e.status === 'draft')
      existingEtude.value = draft || res.data[0]
      loadEtudeIntoForm(existingEtude.value)
    }
  } catch (err) {
    console.error('Failed to fetch etudes:', err)
  }
}

function loadEtudeIntoForm(etude: EtudeInvestissement) {
  form.titre_projet = etude.titre_projet || ''
  form.type_projet = etude.type_projet || 'Immeuble R+4'
  form.nombre_sous_sols = etude.nombre_sous_sols || 0
  form.nombre_etages = etude.nombre_etages || 4
  form.localisation = etude.localisation || ''
  form.version = etude.version || 'V1'
  form.superficie_terrain = parseFloat(String(etude.superficie_terrain || 0))
  form.prix_terrain_m2 = parseFloat(String(etude.prix_terrain_m2 || 0))
  form.taux_immatriculation = parseFloat(String(etude.taux_immatriculation || 5))
  form.cout_gros_oeuvres_m2 = parseFloat(String(etude.cout_gros_oeuvres_m2 || 2500))
  form.cout_finition_m2 = parseFloat(String(etude.cout_finition_m2 || 1500))
  form.amenagement_divers = parseFloat(String(etude.amenagement_divers || 100000))
  form.frais_groupement_etudes = parseFloat(String(etude.frais_groupement_etudes || 0))
  form.frais_autorisation_eclatement = parseFloat(String(etude.frais_autorisation_eclatement || 50000))
  form.frais_lydec = parseFloat(String(etude.frais_lydec || 100000))
  form.prix_vente_m2_commerce = parseFloat(String(etude.prix_vente_m2_commerce || 25000))
  form.prix_vente_m2_appart = parseFloat(String(etude.prix_vente_m2_appart || 12000))

  // Load niveaux from surfaces_par_niveau and surfaces_vendables
  const newNiveaux: typeof niveaux.value = []
  const surfaces = etude.surfaces_par_niveau || {}
  const vendables = etude.surfaces_vendables || {}

  Object.entries(surfaces).forEach(([key, surface]) => {
    const vendable = vendables[key]
    newNiveaux.push({
      key,
      surface: parseFloat(String(surface)),
      usage: (vendable?.usage === 'commerce' ? 'commerce' : 'apparts') as 'commerce' | 'apparts'
    })
  })

  if (newNiveaux.length) {
    niveaux.value = newNiveaux
  } else {
    initializeDefaultNiveaux()
  }
}

function initializeDefaultNiveaux() {
  const nombreEtages = form.nombre_etages || 4
  const nombreSousSols = form.nombre_sous_sols || 0
  const superficie = form.superficie_terrain || 200

  const newNiveaux: typeof niveaux.value = []

  // Add sous-sols
  for (let i = nombreSousSols; i >= 1; i--) {
    newNiveaux.push({ key: `SS${i}`, surface: superficie * 0.8, usage: 'apparts' })
  }

  // Add RDC (commerce typically)
  newNiveaux.push({ key: 'RDC', surface: superficie, usage: 'commerce' })

  // Add etages
  for (let i = 1; i <= nombreEtages; i++) {
    newNiveaux.push({ key: `R+${i}`, surface: superficie, usage: 'apparts' })
  }

  niveaux.value = newNiveaux
}

function addNiveau() {
  const lastKey = niveaux.value[niveaux.value.length - 1]?.key || 'R+0'
  const match = lastKey.match(/R\+(\d+)/)
  const nextNum = match ? parseInt(match[1]) + 1 : niveaux.value.length
  niveaux.value.push({
    key: `R+${nextNum}`,
    surface: form.superficie_terrain || 200,
    usage: 'apparts'
  })
}

function removeNiveau(index: number) {
  if (niveaux.value.length > 1) {
    niveaux.value.splice(index, 1)
  }
}

async function loadAISuggestions() {
  loadingAI.value = true
  try {
    const res = await etudeService.getAISuggestions(listingId.value, token.value)
    if (res.success && res.data) {
      const suggestions = res.data
      if (suggestions.type_projet_suggere) form.type_projet = suggestions.type_projet_suggere
      if (suggestions.nombre_etages_recommande) form.nombre_etages = suggestions.nombre_etages_recommande
      if (suggestions.nombre_sous_sols_recommande) form.nombre_sous_sols = suggestions.nombre_sous_sols_recommande
      if (suggestions.couts_estimes) {
        if (suggestions.couts_estimes.gros_oeuvres_m2) form.cout_gros_oeuvres_m2 = suggestions.couts_estimes.gros_oeuvres_m2
        if (suggestions.couts_estimes.finition_m2) form.cout_finition_m2 = suggestions.couts_estimes.finition_m2
        if (suggestions.couts_estimes.amenagement_divers) form.amenagement_divers = suggestions.couts_estimes.amenagement_divers
      }
      if (suggestions.prix_vente_estimes) {
        if (suggestions.prix_vente_estimes.m2_commerce) form.prix_vente_m2_commerce = suggestions.prix_vente_estimes.m2_commerce
        if (suggestions.prix_vente_estimes.m2_appart) form.prix_vente_m2_appart = suggestions.prix_vente_estimes.m2_appart
      }
      if (suggestions.surfaces_par_niveau) {
        const newNiveaux: typeof niveaux.value = []
        Object.entries(suggestions.surfaces_par_niveau).forEach(([key, surface]) => {
          newNiveaux.push({
            key,
            surface: surface as number,
            usage: key === 'RDC' ? 'commerce' : 'apparts'
          })
        })
        if (newNiveaux.length) niveaux.value = newNiveaux
      }
      toast.add({ title: 'Suggestions IA chargees', color: 'success' })
    }
  } catch (err) {
    console.error('Failed to load AI suggestions:', err)
    toast.add({ title: 'Erreur lors du chargement des suggestions IA', color: 'error' })
  } finally {
    loadingAI.value = false
  }
}

function handlePlanFileChange(event: Event) {
  const input = event.target as HTMLInputElement
  if (input.files) {
    planFiles.value = Array.from(input.files)
  }
}

function removePlanFile(index: number) {
  planFiles.value.splice(index, 1)
}

async function analyzePlans() {
  if (!planFiles.value.length) {
    toast.add({ title: 'Veuillez selectionner au moins un plan', color: 'warning' })
    return
  }

  analyzingPlans.value = true
  try {
    const res = await etudeService.analyzePlans(
      listingId.value,
      planFiles.value,
      planContext.value || undefined,
      token.value
    )

    if (res.success && res.data) {
      const analysis = res.data

      // Apply detected surfaces
      if (analysis.surfaces_detectees) {
        const newNiveaux: typeof niveaux.value = []
        Object.entries(analysis.surfaces_detectees).forEach(([key, surface]) => {
          newNiveaux.push({
            key,
            surface: surface as number,
            usage: key.toLowerCase().includes('rdc') || key.toLowerCase().includes('commerce') ? 'commerce' : 'apparts'
          })
        })
        if (newNiveaux.length) {
          niveaux.value = newNiveaux
          toast.add({ title: `${newNiveaux.length} niveaux detectes`, color: 'success' })
        }
      }

      // Apply type projet
      if (analysis.type_projet) {
        form.type_projet = analysis.type_projet
      }

      // Apply nombre etages
      if (analysis.nombre_etages) {
        form.nombre_etages = analysis.nombre_etages
      }

      // Show observations
      if (analysis.observations?.length) {
        toast.add({
          title: 'Observations IA',
          description: analysis.observations.join('\n'),
          color: 'info',
          timeout: 10000
        })
      }

      toast.add({ title: 'Analyse des plans terminee', color: 'success' })
    }
  } catch (err) {
    console.error('Failed to analyze plans:', err)
    toast.add({ title: 'Erreur lors de l\'analyse des plans', color: 'error' })
  } finally {
    analyzingPlans.value = false
    planFiles.value = []
    planContext.value = ''
  }
}

async function saveEtude(submit = false) {
  if (!canEdit.value) return

  saving.value = true
  try {
    // Build payload with calculated surfaces
    const payload: EtudeUpdatePayload = {
      ...form,
      surfaces_par_niveau: calculated.value.surfacesParNiveau,
      surfaces_vendables: calculated.value.surfacesVendables,
      surface_vendable_commerce: calculated.value.surfaceCommerce,
      surface_vendable_appart: calculated.value.surfaceAppart
    }

    let res
    if (existingEtude.value) {
      res = await etudeService.updateEtude(listingId.value, existingEtude.value.id, payload, token.value)
    } else {
      res = await etudeService.createEtude(listingId.value, payload, token.value)
    }

    if (res.success && res.data) {
      existingEtude.value = res.data
      toast.add({ title: 'Etude sauvegardee', color: 'success' })

      if (submit) {
        await submitForReview()
      }
    }
  } catch (err) {
    console.error('Failed to save etude:', err)
    toast.add({ title: 'Erreur lors de la sauvegarde', color: 'error' })
  } finally {
    saving.value = false
  }
}

async function submitForReview() {
  if (!existingEtude.value) return

  submitting.value = true
  try {
    const res = await etudeService.submitEtude(listingId.value, existingEtude.value.id, token.value)
    if (res.success) {
      existingEtude.value = res.data
      toast.add({ title: 'Etude soumise pour revision', color: 'success' })
    }
  } catch (err) {
    console.error('Failed to submit etude:', err)
    toast.add({ title: 'Erreur lors de la soumission', color: 'error' })
  } finally {
    submitting.value = false
  }
}

async function reviewEtude(action: 'approve' | 'reject') {
  if (!existingEtude.value || !canApprove.value) return

  submitting.value = true
  try {
    const res = await etudeService.reviewEtude(listingId.value, existingEtude.value.id, action, undefined, token.value)
    if (res.success) {
      existingEtude.value = res.data
      toast.add({
        title: action === 'approve' ? 'Etude approuvee' : 'Etude rejetee',
        color: action === 'approve' ? 'success' : 'warning'
      })
    }
  } catch (err) {
    console.error('Failed to review etude:', err)
    toast.add({ title: 'Erreur lors de la revision', color: 'error' })
  } finally {
    submitting.value = false
  }
}

function formatPrice(price: number): string {
  return new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD', maximumFractionDigits: 0 }).format(price)
}

function formatPercent(value: number): string {
  return `${value.toFixed(1)}%`
}

function getStatusInfo(status: string | null): { label: string; color: string } {
  const map: Record<string, { label: string; color: string }> = {
    draft: { label: 'Brouillon', color: 'neutral' },
    pending_review: { label: 'En attente', color: 'warning' },
    approved: { label: 'Approuvee', color: 'success' },
    rejected: { label: 'Rejetee', color: 'error' }
  }
  return map[status || ''] || { label: status || '-', color: 'neutral' }
}

onMounted(async () => {
  if (!canEdit.value) {
    toast.add({ title: 'Acces non autorise', color: 'error' })
    router.push(`/terrains/${listingId.value}`)
    return
  }

  loading.value = true
  await fetchListing()
  await fetchEtudes()

  // Initialize default niveaux if no existing data
  if (!niveaux.value.length) {
    initializeDefaultNiveaux()
  }

  loading.value = false
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-3 mb-1">
          <NuxtLink :to="`/terrains/${listingId}`" class="text-muted hover:text-highlighted">
            <UIcon name="i-lucide-arrow-left" class="size-5" />
          </NuxtLink>
          <h1 class="text-2xl font-bold text-highlighted">Etude d'Investissement</h1>
          <UBadge v-if="existingEtude" :color="getStatusInfo(existingEtude.status).color as any" variant="soft">
            {{ getStatusInfo(existingEtude.status).label }}
          </UBadge>
        </div>
        <p class="text-muted">{{ listing?.title || 'Chargement...' }}</p>
      </div>
      <div class="flex items-center gap-2">
        <UButton
          label="Suggestions IA"
          variant="outline"
          icon="i-lucide-sparkles"
          :loading="loadingAI"
          @click="loadAISuggestions"
        />
        <UButton
          label="Sauvegarder"
          color="primary"
          icon="i-lucide-save"
          :loading="saving"
          @click="saveEtude(false)"
        />
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="space-y-6">
      <div class="bg-elevated rounded-xl p-6 animate-pulse">
        <div class="h-8 bg-muted rounded w-1/3 mb-4" />
        <div class="h-4 bg-muted rounded w-1/2" />
      </div>
    </div>

    <!-- Form -->
    <template v-else>
      <div class="grid lg:grid-cols-3 gap-6">
        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Project Info -->
          <div class="bg-elevated rounded-xl p-6">
            <h2 class="text-lg font-semibold text-highlighted mb-4">Informations Projet</h2>
            <div class="grid sm:grid-cols-2 gap-4">
              <UFormField label="Titre du projet">
                <UInput v-model="form.titre_projet" placeholder="Ex: Immeuble Maarif" />
              </UFormField>
              <UFormField label="Type de projet">
                <UInput v-model="form.type_projet" placeholder="Ex: Immeuble R+4" />
              </UFormField>
              <UFormField label="Localisation">
                <UInput v-model="form.localisation" placeholder="Ex: Maarif, Casablanca" />
              </UFormField>
              <UFormField label="Version">
                <UInput v-model="form.version" placeholder="Ex: V1" />
              </UFormField>
              <UFormField label="Nombre d'etages">
                <UInput v-model.number="form.nombre_etages" type="number" min="0" />
              </UFormField>
              <UFormField label="Nombre de sous-sols">
                <UInput v-model.number="form.nombre_sous_sols" type="number" min="0" />
              </UFormField>
            </div>
          </div>

          <!-- Terrain Info -->
          <div class="bg-elevated rounded-xl p-6">
            <h2 class="text-lg font-semibold text-highlighted mb-4">Terrain</h2>
            <div class="grid sm:grid-cols-3 gap-4">
              <UFormField label="Superficie (m2)">
                <UInput v-model.number="form.superficie_terrain" type="number" min="0" step="0.01" />
              </UFormField>
              <UFormField label="Prix/m2 (MAD)">
                <UInput v-model.number="form.prix_terrain_m2" type="number" min="0" step="0.01" />
              </UFormField>
              <UFormField label="Taux immatriculation (%)">
                <UInput v-model.number="form.taux_immatriculation" type="number" min="0" max="100" step="0.1" />
              </UFormField>
            </div>
            <div class="mt-4 p-3 bg-default rounded-lg">
              <div class="flex justify-between text-sm">
                <span class="text-muted">Prix terrain total:</span>
                <span class="font-semibold text-highlighted">{{ formatPrice(calculated.prixTerrainTotal) }}</span>
              </div>
              <div class="flex justify-between text-sm mt-1">
                <span class="text-muted">Frais immatriculation:</span>
                <span class="text-highlighted">{{ formatPrice(calculated.fraisImmatriculation) }}</span>
              </div>
            </div>
          </div>

          <!-- Plan Analysis -->
          <div class="bg-elevated rounded-xl p-6">
            <h2 class="text-lg font-semibold text-highlighted mb-4">Analyse de Plans</h2>
            <p class="text-sm text-muted mb-4">
              Importez vos plans architecturaux pour une analyse automatique par IA des surfaces et niveaux.
            </p>

            <div class="space-y-4">
              <!-- File Upload -->
              <div class="border-2 border-dashed border-muted rounded-lg p-6 text-center">
                <UIcon name="i-lucide-upload" class="size-8 text-muted mx-auto mb-2" />
                <p class="text-sm text-muted mb-3">Glissez vos plans ici ou cliquez pour selectionner</p>
                <input
                  type="file"
                  accept="image/*,.pdf"
                  multiple
                  class="hidden"
                  id="plan-upload"
                  @change="handlePlanFileChange"
                />
                <label for="plan-upload">
                  <UButton
                    as="span"
                    label="Selectionner des fichiers"
                    variant="outline"
                    icon="i-lucide-file-plus"
                    class="cursor-pointer"
                  />
                </label>
              </div>

              <!-- Selected Files -->
              <div v-if="planFiles.length" class="space-y-2">
                <p class="text-sm font-medium text-highlighted">Fichiers selectionnes:</p>
                <div
                  v-for="(file, index) in planFiles"
                  :key="index"
                  class="flex items-center justify-between p-2 bg-default rounded-lg"
                >
                  <div class="flex items-center gap-2">
                    <UIcon name="i-lucide-file-image" class="size-4 text-muted" />
                    <span class="text-sm truncate max-w-xs">{{ file.name }}</span>
                    <span class="text-xs text-muted">({{ (file.size / 1024).toFixed(0) }} KB)</span>
                  </div>
                  <UButton
                    variant="ghost"
                    color="error"
                    icon="i-lucide-x"
                    size="xs"
                    @click="removePlanFile(index)"
                  />
                </div>
              </div>

              <!-- Context Input -->
              <UFormField label="Contexte additionnel (optionnel)">
                <UTextarea
                  v-model="planContext"
                  placeholder="Ex: Immeuble R+5 avec 2 locaux commerciaux au RDC..."
                  rows="2"
                />
              </UFormField>

              <!-- Analyze Button -->
              <UButton
                label="Analyser les plans"
                color="primary"
                icon="i-lucide-scan"
                :loading="analyzingPlans"
                :disabled="!planFiles.length"
                @click="analyzePlans"
              />
            </div>
          </div>

          <!-- Surfaces par Niveau -->
          <div class="bg-elevated rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-highlighted">Surfaces par Niveau</h2>
              <UButton
                label="Ajouter"
                size="sm"
                variant="outline"
                icon="i-lucide-plus"
                @click="addNiveau"
              />
            </div>
            <div class="space-y-3">
              <div
                v-for="(niveau, index) in niveaux"
                :key="index"
                class="flex items-center gap-3 p-3 bg-default rounded-lg"
              >
                <UInput
                  v-model="niveau.key"
                  class="w-24"
                  placeholder="Niveau"
                />
                <UInput
                  v-model.number="niveau.surface"
                  type="number"
                  class="flex-1"
                  placeholder="Surface m2"
                />
                <USelect
                  v-model="niveau.usage"
                  class="w-36"
                  :items="[
                    { label: 'Commerce', value: 'commerce' },
                    { label: 'Appartements', value: 'apparts' }
                  ]"
                />
                <UButton
                  variant="ghost"
                  color="error"
                  icon="i-lucide-trash-2"
                  size="sm"
                  :disabled="niveaux.length <= 1"
                  @click="removeNiveau(index)"
                />
              </div>
            </div>
            <div class="mt-4 p-3 bg-default rounded-lg">
              <div class="flex justify-between text-sm">
                <span class="text-muted">Surface plancher total:</span>
                <span class="font-semibold text-highlighted">{{ calculated.surfacePlancherTotal.toLocaleString() }} m2</span>
              </div>
              <div class="flex justify-between text-sm mt-1">
                <span class="text-muted">Surface commerce:</span>
                <span class="text-highlighted">{{ calculated.surfaceCommerce.toLocaleString() }} m2</span>
              </div>
              <div class="flex justify-between text-sm mt-1">
                <span class="text-muted">Surface appartements:</span>
                <span class="text-highlighted">{{ calculated.surfaceAppart.toLocaleString() }} m2</span>
              </div>
            </div>
          </div>

          <!-- Construction Costs -->
          <div class="bg-elevated rounded-xl p-6">
            <h2 class="text-lg font-semibold text-highlighted mb-4">Couts de Construction</h2>
            <div class="grid sm:grid-cols-3 gap-4">
              <UFormField label="Gros oeuvres/m2 (MAD)">
                <UInput v-model.number="form.cout_gros_oeuvres_m2" type="number" min="0" />
              </UFormField>
              <UFormField label="Finition/m2 (MAD)">
                <UInput v-model.number="form.cout_finition_m2" type="number" min="0" />
              </UFormField>
              <UFormField label="Amenagement divers (MAD)">
                <UInput v-model.number="form.amenagement_divers" type="number" min="0" />
              </UFormField>
            </div>
            <div class="mt-4 p-3 bg-default rounded-lg">
              <div class="flex justify-between text-sm">
                <span class="text-muted">Cout total travaux:</span>
                <span class="font-semibold text-highlighted">{{ formatPrice(calculated.coutTotalTravaux) }}</span>
              </div>
            </div>
          </div>

          <!-- Additional Fees -->
          <div class="bg-elevated rounded-xl p-6">
            <h2 class="text-lg font-semibold text-highlighted mb-4">Frais Additionnels</h2>
            <div class="grid sm:grid-cols-3 gap-4">
              <UFormField label="Groupement etudes (MAD)">
                <UInput v-model.number="form.frais_groupement_etudes" type="number" min="0" placeholder="Auto: 2.5%" />
              </UFormField>
              <UFormField label="Autorisation eclatement (MAD)">
                <UInput v-model.number="form.frais_autorisation_eclatement" type="number" min="0" />
              </UFormField>
              <UFormField label="LYDEC (MAD)">
                <UInput v-model.number="form.frais_lydec" type="number" min="0" />
              </UFormField>
            </div>
            <div class="mt-4 p-3 bg-default rounded-lg">
              <div class="flex justify-between text-sm">
                <span class="text-muted">Total frais construction:</span>
                <span class="font-semibold text-highlighted">{{ formatPrice(calculated.totalFraisConstruction) }}</span>
              </div>
            </div>
          </div>

          <!-- Sales Prices -->
          <div class="bg-elevated rounded-xl p-6">
            <h2 class="text-lg font-semibold text-highlighted mb-4">Prix de Vente</h2>
            <div class="grid sm:grid-cols-2 gap-4">
              <UFormField label="Prix/m2 Commerce (MAD)">
                <UInput v-model.number="form.prix_vente_m2_commerce" type="number" min="0" />
              </UFormField>
              <UFormField label="Prix/m2 Appartements (MAD)">
                <UInput v-model.number="form.prix_vente_m2_appart" type="number" min="0" />
              </UFormField>
            </div>
            <div class="mt-4 p-3 bg-default rounded-lg space-y-1">
              <div class="flex justify-between text-sm">
                <span class="text-muted">Revenus commerce:</span>
                <span class="text-highlighted">{{ formatPrice(calculated.revenusCommerce) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-muted">Revenus appartements:</span>
                <span class="text-highlighted">{{ formatPrice(calculated.revenusAppart) }}</span>
              </div>
              <div class="flex justify-between text-sm font-semibold pt-2 border-t border-default">
                <span class="text-muted">Total revenus:</span>
                <span class="text-highlighted">{{ formatPrice(calculated.totalRevenues) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar - Results Preview -->
        <div class="space-y-6">
          <!-- Summary Card -->
          <div class="bg-elevated rounded-xl p-6 sticky top-6">
            <h2 class="text-lg font-semibold text-highlighted mb-4">Resultat</h2>

            <!-- Ratio Badge -->
            <div class="text-center mb-6">
              <p class="text-xs text-dimmed uppercase tracking-wider mb-2">Ratio Rentabilite</p>
              <div
                class="inline-flex items-center justify-center w-24 h-24 rounded-full"
                :class="calculated.ratio > 0 ? 'bg-success/20' : 'bg-error/20'"
              >
                <span
                  class="text-2xl font-bold"
                  :class="calculated.ratio > 0 ? 'text-success' : 'text-error'"
                >
                  {{ formatPercent(calculated.ratio) }}
                </span>
              </div>
            </div>

            <!-- Key Metrics -->
            <div class="space-y-3">
              <div class="flex justify-between">
                <span class="text-muted">Investissement total:</span>
                <span class="font-semibold text-highlighted">{{ formatPrice(calculated.totalInvestissement) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted">Revenus totaux:</span>
                <span class="font-semibold text-highlighted">{{ formatPrice(calculated.totalRevenues) }}</span>
              </div>
              <div class="pt-3 border-t border-default">
                <div class="flex justify-between">
                  <span class="text-muted">Resultat brute:</span>
                  <span
                    class="font-bold"
                    :class="calculated.resultatBrute > 0 ? 'text-success' : 'text-error'"
                  >
                    {{ formatPrice(calculated.resultatBrute) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 space-y-3">
              <UButton
                label="Sauvegarder"
                color="primary"
                class="w-full"
                icon="i-lucide-save"
                :loading="saving"
                @click="saveEtude(false)"
              />

              <UButton
                v-if="existingEtude?.status === 'draft'"
                label="Soumettre pour revision"
                variant="outline"
                class="w-full"
                icon="i-lucide-send"
                :loading="submitting"
                @click="saveEtude(true)"
              />

              <!-- Admin Review Actions -->
              <template v-if="canApprove && existingEtude?.status === 'pending_review'">
                <UButton
                  label="Approuver"
                  color="success"
                  class="w-full"
                  icon="i-lucide-check"
                  :loading="submitting"
                  @click="reviewEtude('approve')"
                />
                <UButton
                  label="Rejeter"
                  color="error"
                  variant="outline"
                  class="w-full"
                  icon="i-lucide-x"
                  :loading="submitting"
                  @click="reviewEtude('reject')"
                />
              </template>
            </div>

            <!-- Status Info -->
            <div v-if="existingEtude?.generated_by_ai" class="mt-4 p-3 bg-info/10 rounded-lg">
              <div class="flex items-center gap-2 text-sm text-info">
                <UIcon name="i-lucide-sparkles" class="size-4" />
                <span>Generee par IA</span>
              </div>
            </div>
          </div>

          <!-- Investment Breakdown -->
          <div class="bg-elevated rounded-xl p-6">
            <h3 class="text-sm font-semibold text-highlighted mb-4">Detail Investissement</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-muted">Prix terrain:</span>
                <span>{{ formatPrice(calculated.prixTerrainTotal) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted">Frais immat.:</span>
                <span>{{ formatPrice(calculated.fraisImmatriculation) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted">Travaux:</span>
                <span>{{ formatPrice(calculated.coutTotalTravaux) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted">Frais const.:</span>
                <span>{{ formatPrice(calculated.totalFraisConstruction - calculated.coutTotalTravaux) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
