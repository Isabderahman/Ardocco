<script setup lang="ts">
definePageMeta({
  title: 'Login',
  middleware: 'guest'
})

const { login } = useAuth()
const route = useRoute()
const config = useRuntimeConfig()
const requestUrl = useRequestURL()

const state = reactive({
  email: '',
  password: ''
})

const pending = ref(false)
const error = ref<string | null>(null)

function safeRedirectPath(value: unknown): string | null {
  if (typeof value !== 'string') return null
  const trimmed = value.trim()
  if (!trimmed.startsWith('/')) return null
  if (trimmed.startsWith('//')) return null
  return trimmed
}

function normalizeExternalUrl(value: unknown): string | null {
  if (typeof value !== 'string') return null
  const trimmed = value.trim()
  if (!trimmed) return null
  if (!/^https?:\/\//i.test(trimmed)) return null
  return trimmed.replace(/\/+$/, '')
}

function resolveDashboardBase(): string | null {
  const external = normalizeExternalUrl(config.public.dashboardUrl)
  if (external) return external

  const host = requestUrl.hostname
  if (host === 'localhost' || host === '127.0.0.1') {
    const protocol = requestUrl.protocol || 'http:'
    return `${protocol}//${host}:8002`
  }

  return null
}

async function onSubmit() {
  pending.value = true
  error.value = null
  try {
    const res = await login(state.email, state.password)

    const redirect = safeRedirectPath(route.query.redirect)
    if (redirect) {
      await navigateTo(redirect)
      return
    }

    const externalDashboard = resolveDashboardBase()
    if (externalDashboard && res.token) {
      const token = encodeURIComponent(res.token)
      await navigateTo(`${externalDashboard}/auth/consume?token=${token}`, { external: true })
      return
    }

    const role = res.user?.role
    if (role === 'admin') {
      await navigateTo('/admin')
      return
    }
    if (role === 'expert') {
      await navigateTo('/expert')
      return
    }
    if (role === 'agent' || role === 'vendeur') {
      await navigateTo('/dashboard')
      return
    }

    await navigateTo('/dashboard')
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Login failed.'
  } finally {
    pending.value = false
  }
}
</script>

<template>
  <UContainer class="py-12">
    <div class="mx-auto max-w-md">
      <ThemeACard
        title="Login"
        description="Sign in to manage your terrains."
      >
        <UAlert
          v-if="error"
          color="error"
          variant="soft"
          :description="error"
        />

        <ThemeAForm
          :state="state"
          @submit="onSubmit"
        >
          <div class="space-y-4">
            <UFormField
              label="Email"
              name="email"
              class=" w-full"
            >
              <UInput
                class="w-full"
                v-model="state.email"
                type="email"
              />
            </UFormField>
            <UFormField
              label="Password"
              name="password"
              class="w-full"
            >
              <UInput
                class="w-full"
                v-model="state.password"
                type="password"
              />
            </UFormField>
            <div class="w-full flex justify-center gap-1">
            <UButton
              type="submit"
              label="Login"
              color="primary"
              :loading="pending"
              class="rounded-full justify-center w-1/2"
            />
            </div>

            <div class="text-center pt-4">
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Pas encore de compte?
                <NuxtLink
                  to="/signup"
                  class="text-primary-600 dark:text-primary-400 hover:underline font-medium"
                >
                  S'inscrire
                </NuxtLink>
              </p>
            </div>
          </div>
        </ThemeAForm>
      </ThemeACard>
    </div>
  </UContainer>
</template>
