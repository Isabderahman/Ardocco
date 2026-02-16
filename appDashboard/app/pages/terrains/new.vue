<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  title: 'Ajouter un terrain',
  middleware: 'seller'
})

type StepValue = 'informations' | 'role' | 'documents' | 'validation'
type UserRole = 'proprietaire' | 'agent'

const STEP_ITEMS: Array<{
  title: string
  description: string
  value: StepValue
  slot: string
  icon: string
}> = [
  {
    title: 'Informations',
    description: 'Titre foncier & localisation',
    value: 'informations',
    slot: 'step-informations',
    icon: 'i-lucide-file-text'
  },
  {
    title: 'Votre rôle',
    description: 'Propriétaire ou agent',
    value: 'role',
    slot: 'step-role',
    icon: 'i-lucide-user'
  },
  {
    title: 'Documents',
    description: 'Pièces justificatives',
    value: 'documents',
    slot: 'step-documents',
    icon: 'i-lucide-folder-open'
  },
  {
    title: 'Validation',
    description: 'Résumé & confirmation',
    value: 'validation',
    slot: 'step-validation',
    icon: 'i-lucide-check-circle'
  }
]

const TOTAL_STEPS = STEP_ITEMS.length

const PROVINCES: Array<{ label: string, value: string }> = [
  { label: 'Casablanca', value: 'CAS' },
  { label: 'Nouaceur', value: 'NOU' },
  { label: 'Mohammedia', value: 'MOH' },
  { label: 'Mediouna', value: 'MED' },
  { label: 'Berrechid', value: 'BER' },
  { label: 'Benslimane', value: 'BEN' },
  { label: 'Settat', value: 'SET' },
  { label: 'El Jadida', value: 'JDI' },
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

const step = ref<StepValue>('informations')

const form = reactive({
  // Step 1: Informations
  titre_foncier: '',
  situation_approximative: '',
  province_code: PROVINCES[0]?.value || 'CAS',
  arrondissement: '',

  // Step 2: Role
  user_role: null as UserRole | null,

  // Step 3: Documents
  plan_cadastral: null as File | null,
  photos: null as File[] | null,
  note_renseignement: null as File | null,

  // Step 4: Validation
  title: '',
  description: '',
  owner_attestation: false
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
    form.arrondissement = ''
  }
)

const communes = computed<CommuneApiModel[]>(() => communesResponse.value?.data?.all || [])

const communeItems = computed(() => communes.value.map(c => ({
  label: `${c.name_fr || '—'}${c.type ? ` (${c.type})` : ''}`,
  value: c.id
})))

const selectedProvince = computed(() => PROVINCES.find(p => p.value === form.province_code)?.label || '')
const selectedCommune = computed(() => {
  const commune = communes.value.find(c => c.id === form.arrondissement)
  return commune?.name_fr || ''
})

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

  if (currentStep === 'informations') {
    if (!form.titre_foncier.trim()) setError('titre_foncier', 'Titre foncier requis.')
    if (!form.province_code) setError('province_code', 'Province requise.')
    if (!form.arrondissement) setError('arrondissement', 'Arrondissement requis.')
  }

  if (currentStep === 'role') {
    if (!form.user_role) setError('user_role', 'Veuillez sélectionner votre rôle.')
  }

  if (currentStep === 'documents') {
    if (!form.plan_cadastral) setError('plan_cadastral', 'Plan cadastral requis.')
    if (!form.photos || form.photos.length === 0) setError('photos', 'Au moins une photo requise.')
    if (!form.note_renseignement) setError('note_renseignement', 'Note de renseignement requise.')
  }

  if (currentStep === 'validation') {
    if (!form.title.trim()) setError('title', 'Titre de l\'annonce requis.')
    if (!form.owner_attestation) setError('owner_attestation', 'Veuillez confirmer l\'attestation.')
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

const { pending: createPending, error: createError, fieldErrors, createFormData } = useCreateListing()

async function submit() {
  for (const stepToValidate of STEP_ITEMS.map(s => s.value)) {
    if (!validateStep(stepToValidate)) {
      step.value = stepToValidate
      return
    }
  }

  const fd = new FormData()
  fd.set('title', form.title.trim())
  fd.set('commune_id', form.arrondissement)
  fd.set('reference_tf', form.titre_foncier.trim())
  fd.set('titre_foncier', '1')
  fd.set('quartier', form.situation_approximative.trim())
  fd.set('description', form.description.trim())
  fd.set('owner_attestation', form.owner_attestation ? '1' : '0')
  fd.set('user_role', form.user_role || 'proprietaire')

  // Default values
  fd.set('type_terrain', 'residentiel')
  fd.set('superficie_m2', '0')
  fd.set('superficie_unknown', '1')
  fd.set('price', '0')
  fd.set('price_on_request', '1')
  fd.set('phone', '')
  fd.set('perimetre', 'urbain')
  fd.set('zonage', 'Autre')

  if (form.plan_cadastral) {
    fd.append('plan_cadastral', form.plan_cadastral)
  }

  if (form.note_renseignement) {
    fd.append('note_renseignement', form.note_renseignement)
  }

  if (Array.isArray(form.photos)) {
    form.photos.slice(0, 5).forEach((file) => {
      fd.append('photos[]', file)
    })
  }

  const listing = await createFormData(fd) as { id?: unknown }
  const id = typeof listing?.id === 'string' ? listing.id.trim() : ''
  if (!id) {
    throw new Error("Annonce créée mais l'identifiant est manquant. Veuillez réessayer.")
  }

  await navigateTo('/dashboard')
}
</script>

<template>
  <div class="mx-auto w-full max-w-3xl">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
      <div class="min-w-0">
        <h1 class="text-2xl font-semibold text-highlighted">
          Ajouter un terrain
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
        <!-- Step 1: Informations -->
        <template #step-informations>
          <div class="mt-6 space-y-6">
            <div class="text-center space-y-2">
              <p class="text-lg font-semibold text-highlighted">
                Informations du terrain
              </p>
              <p class="text-sm text-muted">
                Renseignez les informations de base de votre terrain
              </p>
            </div>

            <UFormField label="Titre foncier *" name="titre_foncier">
              <UInput
                v-model="form.titre_foncier"
                placeholder="Ex: 123456/C"
                class="w-full"
              />
              <p v-if="errors.titre_foncier" class="mt-1 text-xs text-red-600">
                {{ errors.titre_foncier }}
              </p>
            </UFormField>

            <UFormField label="Situation approximative" name="situation_approximative">
              <UInput
                v-model="form.situation_approximative"
                placeholder="Ex: Quartier, rue, repère..."
                class="w-full"
              />
            </UFormField>

            <div class="grid gap-4 md:grid-cols-2">
              <UFormField label="Province *" name="province_code">
                <USelectMenu
                  v-model="form.province_code"
                  :items="PROVINCES"
                  value-key="value"
                  label-key="label"
                  :search-input="false"
                  class="w-full"
                />
                <p v-if="errors.province_code" class="mt-1 text-xs text-red-600">
                  {{ errors.province_code }}
                </p>
              </UFormField>

              <UFormField label="Arrondissement *" name="arrondissement">
                <USelectMenu
                  v-model="form.arrondissement"
                  :items="communeItems"
                  value-key="value"
                  label-key="label"
                  :loading="communesPending"
                  placeholder="Sélectionnez"
                  class="w-full"
                />
                <p v-if="communesError" class="mt-1 text-xs text-red-600">
                  Impossible de charger les arrondissements.
                </p>
                <p v-if="errors.arrondissement" class="mt-1 text-xs text-red-600">
                  {{ errors.arrondissement }}
                </p>
              </UFormField>
            </div>
          </div>
        </template>

        <!-- Step 2: Role -->
        <template #step-role>
          <div class="mt-6 space-y-6">
            <div class="text-center space-y-2">
              <p class="text-lg font-semibold text-highlighted">
                Votre rôle
              </p>
              <p class="text-sm text-muted">
                Quel est votre lien avec ce terrain ?
              </p>
            </div>

            <div class="space-y-3">
              <button
                type="button"
                class="w-full rounded-xl border p-5 text-left transition"
                :class="form.user_role === 'proprietaire' ? 'border-[#1A7BFD] bg-blue-50 ring-2 ring-[#1A7BFD]/20' : 'border-default hover:bg-elevated/30'"
                @click="form.user_role = 'proprietaire'"
              >
                <div class="flex items-start gap-4">
                  <div
                    class="flex h-12 w-12 items-center justify-center rounded-full"
                    :class="form.user_role === 'proprietaire' ? 'bg-[#1A7BFD] text-white' : 'bg-gray-100 text-gray-500'"
                  >
                    <UIcon name="i-lucide-home" class="size-6" />
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="text-base font-semibold text-highlighted">
                      Je suis propriétaire
                    </p>
                    <p class="mt-1 text-sm text-muted">
                      Je possède ce terrain et souhaite le mettre en vente
                    </p>
                  </div>
                  <span
                    class="mt-1 flex h-5 w-5 items-center justify-center rounded-full border-2"
                    :class="form.user_role === 'proprietaire' ? 'border-[#1A7BFD] bg-[#1A7BFD]' : 'border-gray-300'"
                  >
                    <UIcon
                      v-if="form.user_role === 'proprietaire'"
                      name="i-lucide-check"
                      class="size-3 text-white"
                    />
                  </span>
                </div>
              </button>

              <button
                type="button"
                class="w-full rounded-xl border p-5 text-left transition"
                :class="form.user_role === 'agent' ? 'border-[#1A7BFD] bg-blue-50 ring-2 ring-[#1A7BFD]/20' : 'border-default hover:bg-elevated/30'"
                @click="form.user_role = 'agent'"
              >
                <div class="flex items-start gap-4">
                  <div
                    class="flex h-12 w-12 items-center justify-center rounded-full"
                    :class="form.user_role === 'agent' ? 'bg-[#1A7BFD] text-white' : 'bg-gray-100 text-gray-500'"
                  >
                    <UIcon name="i-lucide-briefcase" class="size-6" />
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="text-base font-semibold text-highlighted">
                      Je suis agent immobilier
                    </p>
                    <p class="mt-1 text-sm text-muted">
                      Je représente le propriétaire de ce terrain
                    </p>
                  </div>
                  <span
                    class="mt-1 flex h-5 w-5 items-center justify-center rounded-full border-2"
                    :class="form.user_role === 'agent' ? 'border-[#1A7BFD] bg-[#1A7BFD]' : 'border-gray-300'"
                  >
                    <UIcon
                      v-if="form.user_role === 'agent'"
                      name="i-lucide-check"
                      class="size-3 text-white"
                    />
                  </span>
                </div>
              </button>

              <p v-if="errors.user_role" class="text-xs text-red-600">
                {{ errors.user_role }}
              </p>
            </div>
          </div>
        </template>

        <!-- Step 3: Documents -->
        <template #step-documents>
          <div class="mt-6 space-y-6">
            <div class="text-center space-y-2">
              <p class="text-lg font-semibold text-highlighted">
                Documents requis
              </p>
              <p class="text-sm text-muted">
                Fournissez les documents justificatifs
              </p>
            </div>

            <div class="space-y-4">
              <!-- Plan cadastral -->
              <div class="rounded-xl border border-default p-4">
                <div class="flex items-start gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-[#1A7BFD]">
                    <UIcon name="i-lucide-map" class="size-5" />
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-highlighted">
                      Plan cadastral *
                    </p>
                    <p class="mt-0.5 text-xs text-muted">
                      Document officiel de délimitation
                    </p>
                    <div class="mt-3">
                      <UFileUpload
                        v-model="form.plan_cadastral"
                        accept="application/pdf,image/png,image/jpeg"
                        :ui="{ base: 'w-full' }"
                        description="PDF, PNG, JPG"
                      />
                    </div>
                    <p v-if="errors.plan_cadastral" class="mt-1 text-xs text-red-600">
                      {{ errors.plan_cadastral }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Photos -->
              <div class="rounded-xl border border-default p-4">
                <div class="flex items-start gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-[#1A7BFD]">
                    <UIcon name="i-lucide-camera" class="size-5" />
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-highlighted">
                      Photos *
                    </p>
                    <p class="mt-0.5 text-xs text-muted">
                      Photos du terrain (max 5)
                    </p>
                    <div class="mt-3">
                      <UFileUpload
                        v-model="form.photos"
                        multiple
                        accept="image/png,image/jpeg"
                        :ui="{ base: 'w-full' }"
                        description="PNG, JPG"
                      />
                    </div>
                    <p v-if="errors.photos" class="mt-1 text-xs text-red-600">
                      {{ errors.photos }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Note de renseignement -->
              <div class="rounded-xl border border-default p-4">
                <div class="flex items-start gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-[#1A7BFD]">
                    <UIcon name="i-lucide-file-text" class="size-5" />
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-highlighted">
                      Note de renseignement *
                    </p>
                    <p class="mt-0.5 text-xs text-muted">
                      Document de la conservation foncière
                    </p>
                    <div class="mt-3">
                      <UFileUpload
                        v-model="form.note_renseignement"
                        accept="application/pdf,image/png,image/jpeg"
                        :ui="{ base: 'w-full' }"
                        description="PDF, PNG, JPG"
                      />
                    </div>
                    <p v-if="errors.note_renseignement" class="mt-1 text-xs text-red-600">
                      {{ errors.note_renseignement }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- Step 4: Validation -->
        <template #step-validation>
          <div class="mt-6 space-y-6">
            <div class="text-center space-y-2">
              <p class="text-lg font-semibold text-highlighted">
                Validation & Résumé
              </p>
              <p class="text-sm text-muted">
                Vérifiez les informations et confirmez
              </p>
            </div>

            <!-- Summary -->
            <div class="rounded-xl bg-gray-50 p-4 space-y-3">
              <h3 class="text-sm font-semibold text-highlighted">Résumé</h3>

              <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                  <p class="text-xs text-muted">Titre foncier</p>
                  <p class="font-medium text-highlighted">{{ form.titre_foncier || '—' }}</p>
                </div>
                <div>
                  <p class="text-xs text-muted">Province</p>
                  <p class="font-medium text-highlighted">{{ selectedProvince }}</p>
                </div>
                <div>
                  <p class="text-xs text-muted">Arrondissement</p>
                  <p class="font-medium text-highlighted">{{ selectedCommune || '—' }}</p>
                </div>
                <div>
                  <p class="text-xs text-muted">Rôle</p>
                  <p class="font-medium text-highlighted">
                    {{ form.user_role === 'proprietaire' ? 'Propriétaire' : form.user_role === 'agent' ? 'Agent immobilier' : '—' }}
                  </p>
                </div>
              </div>

              <div class="border-t border-default pt-3">
                <p class="text-xs text-muted mb-2">Documents fournis</p>
                <div class="flex flex-wrap gap-2">
                  <UBadge v-if="form.plan_cadastral" color="success" variant="soft" size="xs">
                    Plan cadastral
                  </UBadge>
                  <UBadge v-if="form.photos?.length" color="success" variant="soft" size="xs">
                    {{ form.photos.length }} photo(s)
                  </UBadge>
                  <UBadge v-if="form.note_renseignement" color="success" variant="soft" size="xs">
                    Note de renseignement
                  </UBadge>
                </div>
              </div>
            </div>

            <!-- Titre de l'annonce -->
            <UFormField label="Titre de l'annonce *" name="title">
              <UInput
                v-model="form.title"
                placeholder="Ex: Terrain à vendre à Casablanca"
                class="w-full"
              />
              <p v-if="errors.title" class="mt-1 text-xs text-red-600">
                {{ errors.title }}
              </p>
            </UFormField>

            <!-- Description -->
            <UFormField label="Description (optionnel)" name="description">
              <UTextarea
                v-model="form.description"
                :rows="4"
                placeholder="Décrivez votre terrain..."
                class="w-full"
              />
            </UFormField>

            <!-- Attestation -->
            <div class="rounded-xl border border-default p-4">
              <div class="flex items-start gap-3">
                <UCheckbox v-model="form.owner_attestation" :ui="{ base: 'mt-1' }" />
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-highlighted">
                    Je déclare être habilité(e) à mettre ce terrain en vente *
                  </p>
                  <p class="mt-1 text-xs text-muted">
                    En cochant cette case, vous confirmez être le propriétaire légitime ou le mandataire autorisé.
                  </p>
                  <p v-if="errors.owner_attestation" class="mt-2 text-xs text-red-600">
                    {{ errors.owner_attestation }}
                  </p>
                </div>
              </div>
            </div>
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
