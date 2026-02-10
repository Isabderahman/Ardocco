<script setup lang="ts">
import type { TerrainType } from '~/types/enums/terrain'

definePageMeta({
  layout: 'dashboard',
  title: 'Déposer un terrain',
  middleware: 'seller'
})

type Perimetre = 'urbain' | 'rural' | 'periurbain'
type StepValue = 'foncier' | 'location' | 'pricing' | 'details'

const STEP_ITEMS: Array<{
  title: string
  description: string
  value: StepValue
  slot: string
  icon: string
}> = [
  {
    title: 'Foncier',
    description: 'Titre foncier ?',
    value: 'foncier',
    slot: 'step-foncier',
    icon: 'i-lucide-file-key'
  },
  {
    title: 'Localisation',
    description: 'Où se trouve votre terrain ?',
    value: 'location',
    slot: 'step-location',
    icon: 'i-lucide-map-pin'
  },
  {
    title: 'Prix',
    description: 'Prix & contact',
    value: 'pricing',
    slot: 'step-pricing',
    icon: 'i-lucide-badge-dollar-sign'
  },
  {
    title: 'Description',
    description: 'Photos & documents',
    value: 'details',
    slot: 'step-details',
    icon: 'i-lucide-file-text'
  }
]

const TOTAL_STEPS = STEP_ITEMS.length

const PROVINCES: Array<{ label: string, value: string }> = [
  { label: 'Casablanca', value: 'CAS' },
  { label: 'Mohammedia', value: 'MOH' },
  { label: 'Benslimane', value: 'BEN' },
  { label: 'Settat', value: 'SET' },
  { label: 'El Jadida', value: 'JDI' },
  { label: 'Berrechid', value: 'BER' },
  { label: 'Mediouna', value: 'MED' },
  { label: 'Nouaceur', value: 'NOU' },
  { label: 'Sidi Bennour', value: 'SBN' }
]

type CommuneApiModel = {
  id: string
  name_fr?: string | null
  name_ar?: string | null
  type?: string | null
  code_postal?: string | null
  latitude?: number | string | null
  longitude?: number | string | null
}

type CommunesResponse = {
  success: boolean
  message?: string
  data?: {
    all?: CommuneApiModel[]
  }
}

const step = ref<StepValue>('foncier')

const TERRAIN_TYPES: Array<{ label: string, value: TerrainType }> = [
  { label: 'Résidentiel', value: 'residentiel' },
  { label: 'Commercial', value: 'commercial' },
  { label: 'Industriel', value: 'industriel' },
  { label: 'Agricole', value: 'agricole' },
  { label: 'Mixte', value: 'mixte' }
]

const PERIMETRE_OPTIONS: Array<{ label: string, value: Perimetre }> = [
  { label: 'Urbain', value: 'urbain' },
  { label: 'Rural', value: 'rural' },
  { label: 'Périurbain', value: 'periurbain' }
]

const ZONAGE_OPTIONS: Array<{ label: string, value: string }> = [
  { label: 'Agricole', value: 'Agricole' },
  { label: 'Commercial', value: 'Commercial' },
  { label: "Groupement d'habitation", value: "Groupement d'habitation" },
  { label: 'Industriel', value: 'Industriel' },
  { label: 'Lots de villa', value: 'Lots de villa' },
  { label: 'Villa construite', value: 'Villa construite' },
  { label: 'Touristique', value: 'Touristique' },
  { label: 'R+1', value: 'R+1' },
  { label: 'R+2', value: 'R+2' },
  { label: 'R+3', value: 'R+3' },
  { label: 'R+4', value: 'R+4' },
  { label: 'R+5', value: 'R+5' },
  { label: 'R+6', value: 'R+6' },
  { label: 'R+7', value: 'R+7' },
  { label: 'R+7 et plus', value: 'R+7 et plus' },
  { label: 'Autre', value: 'Autre' }
]

const form = reactive({
  type_terrain: undefined as TerrainType | undefined,
  title: '',
  province_code: PROVINCES[0]?.value || 'CAS',
  commune_id: '',
  superficie_m2: null as number | null,
  superficie_unknown: false,
  perimetre: 'urbain' as Perimetre,
  zonage: 'Autre',
  geojson_polygon: '',
  price: null as number | null,
  price_on_request: false,
  price_per_m2: false,
  negotiable: false,
  phone: '',
  whatsapp: '',
  email: '',
  description: '',
  photos: null as File[] | null,
  owner_attestation: false,
  titre_foncier: null as boolean | null,
  reference_tf: '',
  plan_situation: null as File | null
})

const stepIndex = computed(() => STEP_ITEMS.findIndex(s => s.value === step.value))
const stepNumber = computed(() => Math.max(0, stepIndex.value) + 1)
const isFirstStep = computed(() => stepIndex.value <= 0)
const isLastStep = computed(() => stepIndex.value >= STEP_ITEMS.length - 1)

const {
  data: communesResponse,
  pending: communesPending,
  error: communesError
} = await useFetch<CommunesResponse>(
  () => `/api/backend/geo/communes/${encodeURIComponent(form.province_code)}`,
  {
    watch: [() => form.province_code]
  }
)

watch(
  () => form.province_code,
  () => {
    form.commune_id = ''
  }
)

const communes = computed<CommuneApiModel[]>(() => communesResponse.value?.data?.all || [])

const communeItems = computed(() => communes.value.map(c => ({
  label: `${c.name_fr || '—'}${c.type ? ` (${c.type})` : ''}`,
  value: c.id
})))

const { ensureUserLoaded, user } = useAuth()
onMounted(() => {
  ensureUserLoaded().catch(() => {})
})

watch(
  user,
  (u) => {
    if (!u) return
    if (!form.phone && u.phone) form.phone = u.phone
    if (!form.email && u.email) form.email = u.email
    if (!form.whatsapp && u.phone) form.whatsapp = u.phone
  },
  { immediate: true }
)

const errors = reactive<Record<string, string>>({})

function setError(field: string, message: string) {
  errors[field] = message
}

function clearErrors() {
  for (const key of Object.keys(errors)) {
    delete errors[key]
  }
}

watch(step, () => {
  clearErrors()
})

function validateStep(currentStep: StepValue) {
  clearErrors()

  if (currentStep === 'foncier') {
    if (form.titre_foncier == null) {
      setError('titre_foncier', 'Veuillez choisir une option.')
    } else if (form.titre_foncier) {
      if (!form.reference_tf.trim()) setError('reference_tf', 'Référence TF requise.')
    } else {
      if (!form.plan_situation) setError('plan_situation', 'Plan de situation requis.')
    }
  }

  if (currentStep === 'location') {
    if (!form.type_terrain) setError('type_terrain', 'Type de terrain requis.')
    if (!form.title.trim()) setError('title', 'Titre requis.')
    if (!form.commune_id) setError('commune_id', 'Ville requise.')

    if (!form.superficie_unknown) {
      if (form.superficie_m2 == null || !Number.isFinite(form.superficie_m2) || form.superficie_m2 <= 0) {
        setError('superficie_m2', 'Superficie requise.')
      }
    }

    const polygonRaw = form.geojson_polygon.trim()
    if (polygonRaw) {
      try {
        const decoded = JSON.parse(polygonRaw) as unknown
        const asObj = decoded as { type?: unknown, coordinates?: unknown }
        if (!asObj || typeof asObj !== 'object' || asObj.type !== 'Polygon' || !Array.isArray(asObj.coordinates)) {
          setError('geojson_polygon', 'Le GeoJSON doit être un Polygon valide.')
        }
      } catch {
        setError('geojson_polygon', 'Le GeoJSON doit être un JSON valide.')
      }
    }
  }

  if (currentStep === 'pricing') {
    if (!form.price_on_request) {
      if (form.price == null || !Number.isFinite(form.price) || form.price <= 0) {
        setError('price', 'Prix requis.')
      }
    }

    if (!form.phone.trim()) setError('phone', 'Téléphone requis.')
    const email = form.email.trim()
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      setError('email', 'Email invalide.')
    }
  }

  if (currentStep === 'details') {
    if (!form.description.trim()) setError('description', 'Description requise.')
    if (!form.owner_attestation) setError('owner_attestation', 'Veuillez confirmer l’attestation.')
    if (Array.isArray(form.photos) && form.photos.length > 3) setError('photos', 'Maximum 3 photos.')
  }

  return Object.keys(errors).length === 0
}

function nextStep() {
  if (!validateStep(step.value)) return
  const next = STEP_ITEMS[stepIndex.value + 1]?.value
  if (!next) return
  step.value = next as StepValue
}

function prevStep() {
  clearErrors()
  const prev = STEP_ITEMS[stepIndex.value - 1]?.value
  if (!prev) return
  step.value = prev as StepValue
}

watch(
  () => form.superficie_unknown,
  (value) => {
    if (value) form.superficie_m2 = null
  }
)

watch(
  () => form.price_on_request,
  (value) => {
    if (value) form.price = null
  }
)

const { pending: createPending, error: createError, fieldErrors, createFormData } = useCreateListing()

async function submit() {
  for (const stepToValidate of STEP_ITEMS.map(s => s.value)) {
    if (!validateStep(stepToValidate)) {
      step.value = stepToValidate
      return
    }
  }

  const fd = new FormData()
  if (form.type_terrain) fd.set('type_terrain', form.type_terrain)
  fd.set('title', form.title.trim())
  fd.set('commune_id', form.commune_id)

  // Laravel boolean validation accepts: true, false, 1, 0, "1", "0"
  // It does NOT accept "true" or "false" strings, so we use "1"/"0"
  fd.set('superficie_unknown', form.superficie_unknown ? '1' : '0')
  fd.set('superficie_m2', String(form.superficie_unknown ? 0 : (form.superficie_m2 ?? '')))
  fd.set('perimetre', form.perimetre)
  fd.set('zonage', form.zonage)

  if (form.geojson_polygon.trim()) {
    fd.set('geojson_polygon', form.geojson_polygon.trim())
  }

  fd.set('price_on_request', form.price_on_request ? '1' : '0')
  fd.set('price', String(form.price_on_request ? 0 : (form.price ?? '')))
  fd.set('price_per_m2', form.price_per_m2 ? '1' : '0')
  fd.set('negotiable', form.negotiable ? '1' : '0')

  fd.set('phone', form.phone.trim())
  fd.set('whatsapp', form.whatsapp.trim())
  fd.set('email', form.email.trim())

  fd.set('description', form.description.trim())
  fd.set('owner_attestation', form.owner_attestation ? '1' : '0')

  fd.set('titre_foncier', form.titre_foncier === true ? '1' : '0')

  if (form.titre_foncier === true) {
    fd.set('reference_tf', form.reference_tf.trim())
  }

  if (form.titre_foncier === false && form.plan_situation) {
    fd.append('plan_situation', form.plan_situation)
  }

  if (Array.isArray(form.photos)) {
    form.photos.slice(0, 3).forEach((file) => {
      fd.append('photos[]', file)
    })
  }

  const listing = await createFormData(fd) as { id?: unknown }
  const id = typeof listing?.id === 'string' ? listing.id.trim() : ''
  if (!id) {
    throw new Error("Annonce créée mais l'identifiant est manquant. Veuillez réessayer.")
  }

  // Redirect to dashboard - the listing is in 'brouillon' (draft) status
  // and not publicly visible yet, so the public page would show 404
  await navigateTo('/dashboard')
}

watch(
  () => form.titre_foncier,
  (value) => {
    if (value === true) {
      form.plan_situation = null
      return
    }

    if (value === false) {
      form.reference_tf = ''
      return
    }

    form.reference_tf = ''
    form.plan_situation = null
  }
)
</script>

<template>
  <div class="mx-auto w-full max-w-4xl">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
      <div class="min-w-0">
        <h1 class="text-2xl font-semibold text-highlighted">
          Déposer un terrain
        </h1>
        <p class="mt-1 text-sm text-muted">
          Étape {{ stepNumber }} sur {{ TOTAL_STEPS }}
        </p>
      </div>

      <UButton
        label="Annuler"
        color="neutral"
        variant="outline"
        to="/dashboard"
        class="shrink-0 rounded-full"
      />
    </div>

    <div v-if="createError" class="mt-4">
      <UAlert
        color="error"
        variant="soft"
        title="Impossible de créer l'annonce"
        :description="createError"
      />
    </div>

    <div v-if="fieldErrors" class="mt-4">
      <ThemeACard title="Veuillez corriger les champs suivants">
        <ul class="space-y-1 text-sm text-muted">
          <li
            v-for="(messages, field) in fieldErrors"
            :key="field"
          >
            <span class="font-semibold text-highlighted">{{ field }}:</span>
            {{ messages.join(', ') }}
          </li>
        </ul>
      </ThemeACard>
    </div>

    <div class="mt-6 rounded-2xl bg-default p-6 ring-1 ring-default">
      <UStepper
        v-model="step"
        :items="STEP_ITEMS"
        class="w-full"
      >
        <template #step-foncier>
          <div class="mt-6 space-y-6">
            <div class="text-center space-y-2">
              <p class="text-sm font-semibold text-highlighted">
                Avez-vous un titre foncier ?
              </p>
              <p class="text-sm text-muted">
                Cela nous aide à localiser votre terrain avec précision.
              </p>
            </div>

            <div class="space-y-3">
              <button
                type="button"
                class="w-full rounded-xl border p-4 text-left transition"
                :class="form.titre_foncier === true ? 'border-green-500 bg-green-50 ring-1 ring-green-200' : 'border-default hover:bg-elevated/30'"
                @click="form.titre_foncier = true"
              >
                <div class="flex items-start gap-3">
                  <span
                    class="mt-1 flex h-4 w-4 items-center justify-center rounded-full border"
                    :class="form.titre_foncier === true ? 'border-green-600' : 'border-muted'"
                  >
                    <span
                      v-if="form.titre_foncier === true"
                      class="h-2 w-2 rounded-full bg-green-600"
                    />
                  </span>

                  <div class="min-w-0">
                    <p class="text-sm font-semibold text-highlighted">
                      Oui, j'ai un titre foncier
                    </p>
                    <p class="mt-1 text-sm text-muted">
                      Le terrain sera localisé automatiquement.
                    </p>
                  </div>
                </div>
              </button>

              <button
                type="button"
                class="w-full rounded-xl border p-4 text-left transition"
                :class="form.titre_foncier === false ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-200' : 'border-default hover:bg-elevated/30'"
                @click="form.titre_foncier = false"
              >
                <div class="flex items-start gap-3">
                  <span
                    class="mt-1 flex h-4 w-4 items-center justify-center rounded-full border"
                    :class="form.titre_foncier === false ? 'border-blue-600' : 'border-muted'"
                  >
                    <span
                      v-if="form.titre_foncier === false"
                      class="h-2 w-2 rounded-full bg-blue-600"
                    />
                  </span>

                  <div class="min-w-0">
                    <p class="text-sm font-semibold text-highlighted">
                      Non, mon terrain n'est pas immatriculé
                    </p>
                    <p class="mt-1 text-sm text-muted">
                      Je fournirai un plan de situation.
                    </p>
                  </div>
                </div>
              </button>

              <p v-if="errors.titre_foncier" class="text-xs text-red-600">
                {{ errors.titre_foncier }}
              </p>
            </div>

            <div
              v-if="form.titre_foncier === true"
              class="rounded-xl border border-green-200 bg-green-50 p-4"
            >
              <UFormField label="Numéro de titre foncier *" name="reference_tf">
                <UInput v-model="form.reference_tf" placeholder="Ex: 123456/Z" class="w-full" />
                <p v-if="errors.reference_tf" class="mt-1 text-xs text-red-600">
                  {{ errors.reference_tf }}
                </p>
                <p class="mt-2 text-xs text-muted">
                  Ce numéro reste strictement confidentiel et n'est jamais affiché publiquement.
                </p>
              </UFormField>
            </div>

            <ThemeACard
              v-else-if="form.titre_foncier === false"
              title="Plan de situation (ou croquis) *"
              description="Produit par un ingénieur géomètre topographe, signé et contenant les coordonnées des bornes."
            >
              <UFormField label="Fichier" name="plan_situation">
                <UFileUpload
                  v-model="form.plan_situation"
                  accept="application/pdf,image/png,image/jpeg"
                  :ui="{ base: 'w-full' }"
                  description="PDF, PNG, JPG jusqu’à 10MB"
                />
                <p v-if="errors.plan_situation" class="mt-1 text-xs text-red-600">
                  {{ errors.plan_situation }}
                </p>
              </UFormField>
            </ThemeACard>
          </div>
        </template>

        <template #step-location>
          <div class="mt-6 space-y-6">
            <div class="text-center">
              <p class="text-sm font-semibold text-highlighted">
                Où se trouve votre terrain ?
              </p>
            </div>

            <UFormField label="Type de terrain *" name="type_terrain">
              <USelectMenu
                v-model="form.type_terrain"
                :items="TERRAIN_TYPES"
                value-key="value"
                label-key="label"
                :search-input="false"
                placeholder="Choisir un type"
                class="w-full"
              />
              <p v-if="errors.type_terrain" class="mt-1 text-xs text-red-600">
                {{ errors.type_terrain }}
              </p>
            </UFormField>

            <UFormField label="Titre de l'annonce *" name="title">
              <UInput v-model="form.title" placeholder="Ex: Terrain résidentiel 500m² à Casablanca" class="w-full" />
              <p v-if="errors.title" class="mt-1 text-xs text-red-600">
                {{ errors.title }}
              </p>
            </UFormField>

            <div class="grid gap-4 md:grid-cols-2">
              <UFormField label="Province" name="province_code">
                <USelectMenu
                  v-model="form.province_code"
                  :items="PROVINCES"
                  value-key="value"
                  label-key="label"
                  :search-input="false"
                  class="w-full"
                />
              </UFormField>

              <UFormField label="Ville *" name="commune_id">
                <USelectMenu
                  v-model="form.commune_id"
                  :items="communeItems"
                  value-key="value"
                  label-key="label"
                  :loading="communesPending"
                  placeholder="Sélectionnez une ville"
                  class="w-full"
                />
                <p v-if="communesError" class="mt-1 text-xs text-red-600">
                  Impossible de charger les villes.
                </p>
                <p v-if="errors.commune_id" class="mt-1 text-xs text-red-600">
                  {{ errors.commune_id }}
                </p>
              </UFormField>
            </div>

            <UFormField label="Superficie (m²) *" name="superficie_m2">
              <UInputNumber
                v-model="form.superficie_m2"
                :min="0"
                :disabled="form.superficie_unknown"
                placeholder="Ex: 500"
                :format-options="{ maximumFractionDigits: 0 }"
                class="w-full"
              />
              <p v-if="errors.superficie_m2" class="mt-1 text-xs text-red-600">
                {{ errors.superficie_m2 }}
              </p>

              <div class="mt-3 flex items-start gap-2">
                <UCheckbox v-model="form.superficie_unknown" :ui="{ base: 'mt-0.5' }" />
                <label
                  class="text-sm text-muted cursor-pointer"
                  @click="form.superficie_unknown = !form.superficie_unknown"
                >
                  Je ne connais pas la superficie exacte
                </label>
              </div>
            </UFormField>

            <div class="grid gap-4 md:grid-cols-2">
              <UFormField label="Périmètre" name="perimetre">
                <USelectMenu
                  v-model="form.perimetre"
                  :items="PERIMETRE_OPTIONS"
                  value-key="value"
                  label-key="label"
                  :search-input="false"
                  class="w-full"
                />
              </UFormField>

              <UFormField label="Zonage" name="zonage">
                <USelectMenu
                  v-model="form.zonage"
                  :items="ZONAGE_OPTIONS"
                  value-key="value"
                  label-key="label"
                  class="w-full"
                />
              </UFormField>
            </div>

            <ThemeACard
              title="Limites du terrain (optionnel)"
              description="Collez un GeoJSON Polygon pour afficher les limites sur la carte."
            >
              <UFormField label="GeoJSON Polygon" name="geojson_polygon">
                <UTextarea
                  v-model="form.geojson_polygon"
                  :rows="5"
                  placeholder='{"type":"Polygon","coordinates":[[[lng,lat],[lng,lat],[lng,lat],[lng,lat]]]}'
                  class="w-full"
                />
                <p v-if="errors.geojson_polygon" class="mt-1 text-xs text-red-600">
                  {{ errors.geojson_polygon }}
                </p>
              </UFormField>
            </ThemeACard>
          </div>
        </template>

        <template #step-pricing>
          <div class="mt-6 space-y-6">
            <div class="text-center">
              <p class="text-sm font-semibold text-highlighted">
                Quel est votre prix ?
              </p>
            </div>

            <ThemeACard title="Prix">
              <UFormField label="Prix (MAD) *" name="price">
                <UInputNumber
                  v-model="form.price"
                  :min="0"
                  :disabled="form.price_on_request"
                  placeholder="Ex: 1000000"
                  :format-options="{ maximumFractionDigits: 0 }"
                  class="w-full"
                />
                <p v-if="errors.price" class="mt-1 text-xs text-red-600">
                  {{ errors.price }}
                </p>

                <div class="mt-4 space-y-2">
                  <label class="flex items-center gap-2 text-sm text-muted cursor-pointer">
                    <UCheckbox v-model="form.price_on_request" />
                    <span>Prix sur demande</span>
                  </label>
                  <label class="flex items-center gap-2 text-sm text-muted cursor-pointer">
                    <UCheckbox v-model="form.price_per_m2" />
                    <span>Afficher le prix au m²</span>
                  </label>
                  <label class="flex items-center gap-2 text-sm text-muted cursor-pointer">
                    <UCheckbox v-model="form.negotiable" />
                    <span>Négociable</span>
                  </label>
                </div>
              </UFormField>
            </ThemeACard>

            <ThemeACard title="Informations de contact">
              <div class="grid gap-4 md:grid-cols-2">
                <UFormField label="Téléphone *" name="phone">
                  <UInput v-model="form.phone" placeholder="Ex: 0612345678" class="w-full" />
                  <p v-if="errors.phone" class="mt-1 text-xs text-red-600">
                    {{ errors.phone }}
                  </p>
                </UFormField>

                <UFormField label="WhatsApp (optionnel)" name="whatsapp">
                  <UInput v-model="form.whatsapp" placeholder="Ex: 0612345678" class="w-full" />
                </UFormField>
              </div>

              <div class="mt-4">
                <UFormField label="Email (optionnel)" name="email">
                  <UInput v-model="form.email" type="email" placeholder="Ex: contact@example.com" class="w-full" />
                  <p v-if="errors.email" class="mt-1 text-xs text-red-600">
                    {{ errors.email }}
                  </p>
                </UFormField>
              </div>
            </ThemeACard>
          </div>
        </template>

        <template #step-details>
          <div class="mt-6 space-y-6">
            <div class="text-center">
              <p class="text-sm font-semibold text-highlighted">
                Description du terrain
              </p>
            </div>

            <UFormField label="Description *" name="description">
              <UTextarea v-model="form.description" :rows="6" class="w-full" />
              <p v-if="errors.description" class="mt-1 text-xs text-red-600">
                {{ errors.description }}
              </p>
            </UFormField>

            <UFormField label="Photos (Maximum 3)" name="photos">
              <UFileUpload
                v-model="form.photos"
                multiple
                accept="image/png,image/jpeg"
                :ui="{ base: 'w-full' }"
                description="PNG, JPG jusqu’à 10MB"
              />
              <p v-if="errors.photos" class="mt-1 text-xs text-red-600">
                {{ errors.photos }}
              </p>
            </UFormField>

            <ThemeACard>
              <div class="flex items-start gap-3">
                <UCheckbox v-model="form.owner_attestation" :ui="{ base: 'mt-1' }" />
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-highlighted">
                    Je déclare être habilité(e) à mettre ce terrain en vente (propriétaire ou mandataire). *
                  </p>
                  <p class="mt-1 text-xs text-muted">
                    En cochant cette case, vous confirmez être le propriétaire légitime du terrain et être autorisé à le mettre en vente.
                  </p>
                  <p v-if="errors.owner_attestation" class="mt-2 text-xs text-red-600">
                    {{ errors.owner_attestation }}
                  </p>
                </div>
              </div>
            </ThemeACard>
          </div>
        </template>
      </UStepper>

      <div class="mt-8 flex items-center justify-between gap-3 border-t border-default pt-4">
        <UButton
          label="Retour"
          color="neutral"
          variant="outline"
          :disabled="isFirstStep || createPending"
          @click="prevStep"
        />

        <UButton
          v-if="!isLastStep"
          label="Suivant"
          color="primary"
          :disabled="createPending"
          @click="nextStep"
        />

        <UButton
          v-else
          label="Soumettre mon terrain"
          color="primary"
          :loading="createPending"
          @click="submit"
        />
      </div>
    </div>
  </div>
</template>
