<script setup lang="ts">
import type { UserRole } from '~/types/models/auth'
import { ROLE_LABELS, ROLE_DESCRIPTIONS } from '~/types/models/auth'

definePageMeta({
  title: 'Inscription',
  middleware: 'guest'
})

const { register } = useAuth()

// Available roles for signup
const availableRoles: UserRole[] = ['promoteur', 'vendeur']

// Form state
const step = ref<'role' | 'form' | 'success'>('role')
const selectedRole = ref<UserRole | null>(null)

const state = reactive({
  email: '',
  password: '',
  password_confirmation: '',
  first_name: '',
  last_name: '',
  phone: '',
  company_name: '',
  address: '',
  city: '',
  cin: ''
})

const pending = ref(false)
const error = ref<string | null>(null)
const successMessage = ref<string | null>(null)

function selectRole(role: UserRole) {
  selectedRole.value = role
  step.value = 'form'
}

function goBackToRoleSelection() {
  step.value = 'role'
  error.value = null
}

async function onSubmit() {
  if (!selectedRole.value) return

  pending.value = true
  error.value = null

  try {
    const res = await register({
      email: state.email,
      password: state.password,
      password_confirmation: state.password_confirmation,
      role: selectedRole.value,
      first_name: state.first_name,
      last_name: state.last_name,
      phone: state.phone || undefined,
      company_name: state.company_name || undefined,
      address: state.address || undefined,
      city: state.city || undefined,
      cin: state.cin || undefined
    })

    successMessage.value = res.message || 'Inscription reussie.'
    step.value = 'success'
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Inscription echouee.'
  } finally {
    pending.value = false
  }
}
</script>

<template>
  <UContainer class="py-12">
    <div class="mx-auto max-w-xl">
      <!-- Step 1: Role Selection -->
      <ThemeACard
        v-if="step === 'role'"
        title="Inscription"
        description="Choisissez votre type de compte pour commencer."
      >
        <div class="space-y-4">
          <button
            v-for="role in availableRoles"
            :key="role"
            type="button"
            class="w-full p-6 border-2 rounded-xl text-left transition-all hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20"
            :class="[
              selectedRole === role
                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                : 'border-gray-200 dark:border-gray-700'
            ]"
            @click="selectRole(role)"
          >
            <div class="flex items-start gap-4">
              <div
                class="flex-shrink-0 w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900/40 flex items-center justify-center"
              >
                <UIcon
                  :name="role === 'promoteur' ? 'i-heroicons-building-office-2' : 'i-heroicons-home-modern'"
                  class="w-6 h-6 text-primary-600 dark:text-primary-400"
                />
              </div>
              <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                  {{ ROLE_LABELS[role] }}
                </h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                  {{ ROLE_DESCRIPTIONS[role] }}
                </p>
              </div>
              <UIcon
                name="i-heroicons-chevron-right"
                class="w-5 h-5 text-gray-400"
              />
            </div>
          </button>

          <div class="text-center pt-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Vous avez deja un compte?
              <NuxtLink
                to="/login"
                class="text-primary-600 dark:text-primary-400 hover:underline font-medium"
              >
                Se connecter
              </NuxtLink>
            </p>
          </div>
        </div>
      </ThemeACard>

      <!-- Step 2: Registration Form -->
      <ThemeACard
        v-else-if="step === 'form'"
        :title="`Inscription ${selectedRole ? ROLE_LABELS[selectedRole] : ''}`"
        :description="`Remplissez vos informations pour creer votre compte ${selectedRole ? ROLE_LABELS[selectedRole].toLowerCase() : ''}.`"
      >
        <template #actions>
          <UButton
            variant="ghost"
            color="neutral"
            icon="i-heroicons-arrow-left"
            label="Retour"
            @click="goBackToRoleSelection"
          />
        </template>

        <UAlert
          v-if="error"
          color="error"
          variant="soft"
          :description="error"
          class="mb-4"
        />

        <ThemeAForm
          :state="state"
          @submit="onSubmit"
        >
          <div class="space-y-4">
            <!-- Personal Information -->
            <div class="grid grid-cols-2 gap-4">
              <UFormField
                label="Prenom"
                name="first_name"
                required
              >
                <UInput
                  v-model="state.first_name"
                  class="w-full"
                  placeholder="Votre prenom"
                />
              </UFormField>
              <UFormField
                label="Nom"
                name="last_name"
                required
              >
                <UInput
                  v-model="state.last_name"
                  class="w-full"
                  placeholder="Votre nom"
                />
              </UFormField>
            </div>

            <UFormField
              label="Email"
              name="email"
              required
            >
              <UInput
                v-model="state.email"
                type="email"
                class="w-full"
                placeholder="votre@email.com"
              />
            </UFormField>

            <UFormField
              label="Telephone"
              name="phone"
            >
              <UInput
                v-model="state.phone"
                type="tel"
                class="w-full"
                placeholder="+212 6XX XXX XXX"
              />
            </UFormField>

            <UFormField
              label="Nom de l'entreprise"
              name="company_name"
            >
              <UInput
                v-model="state.company_name"
                class="w-full"
                placeholder="Votre entreprise (optionnel)"
              />
            </UFormField>

            <div class="grid grid-cols-2 gap-4">
              <UFormField
                label="Ville"
                name="city"
              >
                <UInput
                  v-model="state.city"
                  class="w-full"
                  placeholder="Casablanca"
                />
              </UFormField>
              <UFormField
                label="CIN"
                name="cin"
              >
                <UInput
                  v-model="state.cin"
                  class="w-full"
                  placeholder="Numero CIN"
                />
              </UFormField>
            </div>

            <UFormField
              label="Adresse"
              name="address"
            >
              <UInput
                v-model="state.address"
                class="w-full"
                placeholder="Votre adresse complete"
              />
            </UFormField>

            <!-- Password Fields -->
            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
              <UFormField
                label="Mot de passe"
                name="password"
                required
              >
                <UInput
                  v-model="state.password"
                  type="password"
                  class="w-full"
                  placeholder="Minimum 8 caracteres"
                />
              </UFormField>

              <UFormField
                label="Confirmer le mot de passe"
                name="password_confirmation"
                required
                class="mt-4"
              >
                <UInput
                  v-model="state.password_confirmation"
                  type="password"
                  class="w-full"
                  placeholder="Confirmez votre mot de passe"
                />
              </UFormField>
            </div>

            <div class="w-full flex justify-center gap-2 pt-4">
              <UButton
                type="submit"
                label="Creer mon compte"
                color="primary"
                :loading="pending"
                class="rounded-full justify-center w-full sm:w-auto px-8"
              />
            </div>
          </div>
        </ThemeAForm>
      </ThemeACard>

      <!-- Step 3: Success -->
      <ThemeACard
        v-else-if="step === 'success'"
        title="Inscription reussie"
      >
        <div class="text-center space-y-6">
          <div class="w-16 h-16 mx-auto rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center">
            <UIcon
              name="i-heroicons-check"
              class="w-8 h-8 text-green-600 dark:text-green-400"
            />
          </div>

          <div class="space-y-2">
            <p class="text-gray-700 dark:text-gray-300">
              {{ successMessage }}
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Un email vous a ete envoye avec un lien pour signer votre contrat.
              Une fois le contrat signe, votre compte sera examine par un administrateur.
            </p>
          </div>

          <div class="flex flex-col sm:flex-row gap-3 justify-center pt-4">
            <UButton
              to="/login"
              label="Aller a la connexion"
              color="primary"
              class="rounded-full"
            />
            <UButton
              to="/"
              label="Retour a l'accueil"
              variant="outline"
              color="neutral"
              class="rounded-full"
            />
          </div>
        </div>
      </ThemeACard>
    </div>
  </UContainer>
</template>
