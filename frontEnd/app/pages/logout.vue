<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  title: 'Logout'
})

const { logout } = useAuth()
const pending = ref(true)
const error = ref<string | null>(null)

onMounted(async () => {
  try {
    await logout()
    await navigateTo('/')
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Logout failed.'
  } finally {
    pending.value = false
  }
})
</script>

<template>
  <ThemeACard title="Logout">
    <UAlert
      v-if="error"
      color="error"
      variant="soft"
      :description="error"
    />
    <p
      v-else-if="pending"
      class="text-sm text-muted"
    >
      Signing you out...
    </p>
    <div class="mt-6">
      <UButton
        label="Go to Home"
        color="primary"
        to="/"
        class="rounded-full"
      />
    </div>
  </ThemeACard>
</template>
