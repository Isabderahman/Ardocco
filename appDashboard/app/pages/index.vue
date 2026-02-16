<script setup lang="ts">
definePageMeta({
  middleware: 'auth'
})

const { user } = useAuth()

// Redirect based on role
onMounted(() => {
  const role = user.value?.role
  if (role === 'admin') {
    navigateTo('/admin', { replace: true })
  } else if (role === 'agent') {
    navigateTo('/agent', { replace: true })
  } else if (role === 'expert') {
    navigateTo('/expert', { replace: true })
  } else {
    navigateTo('/dashboard', { replace: true })
  }
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center">
      <div class="animate-spin w-8 h-8 border-4 border-emerald-500 border-t-transparent rounded-full mx-auto mb-4" />
      <p class="text-gray-600">Redirection vers le tableau de bord...</p>
    </div>
  </div>
</template>
