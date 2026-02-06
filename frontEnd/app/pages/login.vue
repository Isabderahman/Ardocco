<script setup lang="ts">
definePageMeta({ title: 'Login' })

const { login } = useAuth()

const state = reactive({
  email: '',
  password: ''
})

const pending = ref(false)
const error = ref<string | null>(null)

async function onSubmit() {
  pending.value = true
  error.value = null
  try {
    await login(state.email, state.password)
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
            >
              <UInput
                v-model="state.email"
                type="email"
              />
            </UFormField>
            <UFormField
              label="Password"
              name="password"
            >
              <UInput
                v-model="state.password"
                type="password"
              />
            </UFormField>
            <UButton
              type="submit"
              label="Login"
              color="primary"
              :loading="pending"
              class="w-full rounded-full"
            />
          </div>
        </ThemeAForm>
      </ThemeACard>
    </div>
  </UContainer>
</template>
