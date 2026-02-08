<script setup lang="ts">
definePageMeta({
  layout: 'dashboard',
  title: 'Profile',
  middleware: 'auth'
})

const state = reactive({
  name: 'Demo User',
  email: 'demo@ardocco.com'
})

function onSave() {
  // TODO: wire to backend when available
}
</script>

<template>
  <ThemeACard
    title="Profile"
    description="Manage your account details."
  >
    <ThemeAForm
      :state="state"
      @submit="onSave"
    >
      <div class="grid gap-4 md:grid-cols-2">
        <UFormField
          label="Name"
          name="name"
        >
          <UInput v-model="state.name" />
        </UFormField>
        <UFormField
          label="Email"
          name="email"
        >
          <UInput
            v-model="state.email"
            type="email"
          />
        </UFormField>
      </div>

      <div class="mt-6 flex justify-end">
        <UButton
          type="submit"
          label="Save"
          color="primary"
          class="rounded-full"
        />
      </div>
    </ThemeAForm>
  </ThemeACard>
</template>
