<script setup lang="ts">
import { ROLE_LABELS } from '~/types/models/auth'
import { authService } from '~/services/authService'

definePageMeta({
  layout: 'dashboard',
  title: 'Mon Profil',
  middleware: 'auth'
})

const { user, token, refreshUser } = useAuth()

// Profile form state
const profileState = reactive({
  first_name: '',
  last_name: '',
  phone: '',
  company_name: '',
  address: '',
  city: '',
  cin: ''
})

// Password form state
const passwordState = reactive({
  current_password: '',
  password: '',
  password_confirmation: ''
})

const profilePending = ref(false)
const passwordPending = ref(false)
const profileError = ref<string | null>(null)
const passwordError = ref<string | null>(null)
const profileSuccess = ref<string | null>(null)
const passwordSuccess = ref<string | null>(null)

// Initialize form with user data
function initializeForm() {
  if (user.value) {
    profileState.first_name = user.value.first_name || ''
    profileState.last_name = user.value.last_name || ''
    profileState.phone = user.value.phone || ''
    profileState.company_name = user.value.company_name || ''
    profileState.address = user.value.address || ''
    profileState.city = user.value.city || ''
    profileState.cin = user.value.cin || ''
  }
}

// Watch for user changes
watch(user, () => {
  initializeForm()
}, { immediate: true })

async function onUpdateProfile() {
  profilePending.value = true
  profileError.value = null
  profileSuccess.value = null

  try {
    const res = await authService.updateProfile({
      first_name: profileState.first_name,
      last_name: profileState.last_name,
      phone: profileState.phone || null,
      company_name: profileState.company_name || null,
      address: profileState.address || null,
      city: profileState.city || null,
      cin: profileState.cin || null
    }, token.value)

    if (res.success) {
      profileSuccess.value = res.message || 'Profil mis a jour avec succes.'
      await refreshUser()
    } else {
      profileError.value = res.message || 'Erreur lors de la mise a jour.'
    }
  } catch (err) {
    profileError.value = err instanceof Error ? err.message : 'Erreur lors de la mise a jour.'
  } finally {
    profilePending.value = false
  }
}

async function onUpdatePassword() {
  passwordPending.value = true
  passwordError.value = null
  passwordSuccess.value = null

  try {
    const res = await authService.updatePassword({
      current_password: passwordState.current_password,
      password: passwordState.password,
      password_confirmation: passwordState.password_confirmation
    }, token.value)

    if (res.success) {
      passwordSuccess.value = res.message || 'Mot de passe mis a jour avec succes.'
      // Clear form
      passwordState.current_password = ''
      passwordState.password = ''
      passwordState.password_confirmation = ''
    } else {
      passwordError.value = res.message || 'Erreur lors de la mise a jour.'
    }
  } catch (err) {
    passwordError.value = err instanceof Error ? err.message : 'Erreur lors de la mise a jour.'
  } finally {
    passwordPending.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
      <div class="w-16 h-16 rounded-full bg-primary-100 dark:bg-primary-900/40 flex items-center justify-center">
        <UIcon name="i-heroicons-user" class="w-8 h-8 text-primary-600 dark:text-primary-400" />
      </div>
      <div>
        <h1 class="text-2xl font-bold text-highlighted">
          {{ user?.first_name }} {{ user?.last_name }}
        </h1>
        <p class="text-muted">
          {{ user?.role ? ROLE_LABELS[user.role] : '' }} - {{ user?.email }}
        </p>
      </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <!-- Profile Information Card -->
      <ThemeACard
        title="Informations personnelles"
        description="Mettez a jour vos informations de profil."
      >
        <UAlert
          v-if="profileError"
          color="error"
          variant="soft"
          :description="profileError"
          class="mb-4"
        />

        <UAlert
          v-if="profileSuccess"
          color="success"
          variant="soft"
          :description="profileSuccess"
          class="mb-4"
        />

        <ThemeAForm :state="profileState" @submit="onUpdateProfile">
          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <UFormField label="Prenom" name="first_name" required>
                <UInput
                  v-model="profileState.first_name"
                  class="w-full"
                  placeholder="Votre prenom"
                />
              </UFormField>
              <UFormField label="Nom" name="last_name" required>
                <UInput
                  v-model="profileState.last_name"
                  class="w-full"
                  placeholder="Votre nom"
                />
              </UFormField>
            </div>

            <UFormField label="Email" name="email">
              <UInput
                :model-value="user?.email || ''"
                type="email"
                class="w-full"
                disabled
              />
              <template #hint>
                <span class="text-xs text-muted">L'email ne peut pas etre modifie.</span>
              </template>
            </UFormField>

            <UFormField label="Telephone" name="phone">
              <UInput
                v-model="profileState.phone"
                type="tel"
                class="w-full"
                placeholder="+212 6XX XXX XXX"
              />
            </UFormField>

            <UFormField label="Nom de l'entreprise" name="company_name">
              <UInput
                v-model="profileState.company_name"
                class="w-full"
                placeholder="Votre entreprise (optionnel)"
              />
            </UFormField>

            <div class="grid grid-cols-2 gap-4">
              <UFormField label="Ville" name="city">
                <UInput
                  v-model="profileState.city"
                  class="w-full"
                  placeholder="Casablanca"
                />
              </UFormField>
              <UFormField label="CIN" name="cin">
                <UInput
                  v-model="profileState.cin"
                  class="w-full"
                  placeholder="Numero CIN"
                />
              </UFormField>
            </div>

            <UFormField label="Adresse" name="address">
              <UInput
                v-model="profileState.address"
                class="w-full"
                placeholder="Votre adresse complete"
              />
            </UFormField>

            <div class="pt-4">
              <UButton
                type="submit"
                label="Enregistrer les modifications"
                color="primary"
                :loading="profilePending"
                class="w-full sm:w-auto"
              />
            </div>
          </div>
        </ThemeAForm>
      </ThemeACard>

      <!-- Password Card -->
      <ThemeACard
        title="Changer le mot de passe"
        description="Mettez a jour votre mot de passe pour securiser votre compte."
      >
        <UAlert
          v-if="passwordError"
          color="error"
          variant="soft"
          :description="passwordError"
          class="mb-4"
        />

        <UAlert
          v-if="passwordSuccess"
          color="success"
          variant="soft"
          :description="passwordSuccess"
          class="mb-4"
        />

        <ThemeAForm :state="passwordState" @submit="onUpdatePassword">
          <div class="space-y-4">
            <UFormField label="Mot de passe actuel" name="current_password" required>
              <UInput
                v-model="passwordState.current_password"
                type="password"
                class="w-full"
                placeholder="Votre mot de passe actuel"
              />
            </UFormField>

            <UFormField label="Nouveau mot de passe" name="password" required>
              <UInput
                v-model="passwordState.password"
                type="password"
                class="w-full"
                placeholder="Minimum 8 caracteres"
              />
            </UFormField>

            <UFormField label="Confirmer le nouveau mot de passe" name="password_confirmation" required>
              <UInput
                v-model="passwordState.password_confirmation"
                type="password"
                class="w-full"
                placeholder="Confirmez le nouveau mot de passe"
              />
            </UFormField>

            <div class="pt-4">
              <UButton
                type="submit"
                label="Changer le mot de passe"
                color="primary"
                :loading="passwordPending"
                class="w-full sm:w-auto"
              />
            </div>
          </div>
        </ThemeAForm>
      </ThemeACard>
    </div>

    <!-- Account Status Card -->
    <ThemeACard title="Statut du compte">
      <div class="flex items-center gap-4">
        <div
          class="w-3 h-3 rounded-full"
          :class="user?.is_active ? 'bg-green-500' : 'bg-red-500'"
        />
        <div>
          <p class="font-medium text-highlighted">
            {{ user?.is_active ? 'Compte actif' : 'Compte inactif' }}
          </p>
          <p class="text-sm text-muted">
            {{ user?.is_verified ? 'Email verifie' : 'Email non verifie' }}
          </p>
        </div>
      </div>
    </ThemeACard>
  </div>
</template>
