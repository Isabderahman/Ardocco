<script setup lang="ts">
definePageMeta({
  layout: false
})

const route = useRoute()
const { token: authToken } = useAuth()

onMounted(async () => {
  const tokenParam = route.query.token

  if (typeof tokenParam === 'string' && tokenParam.trim()) {
    // Store the token in cookie
    authToken.value = tokenParam.trim()

    // Small delay to ensure cookie is set
    await new Promise(resolve => setTimeout(resolve, 100))

    // Redirect to dashboard
    await navigateTo('/', { replace: true })
  } else {
    // No token provided, redirect to main site login
    const config = useRuntimeConfig()
    const mainSiteUrl = config.public.mainSiteUrl || 'https://ardocco.com'
    await navigateTo(`${mainSiteUrl}/login`, { external: true })
  }
})
</script>

<template>
  <div class="flex h-screen w-full items-center justify-center">
    <div class="text-center">
      <div class="mb-4">
        <UIcon name="i-lucide-loader-2" class="h-8 w-8 animate-spin text-primary" />
      </div>
      <p class="text-gray-600 dark:text-gray-400">Authenticating...</p>
    </div>
  </div>
</template>
