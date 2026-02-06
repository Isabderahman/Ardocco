<script setup lang="ts">
import type { FormError } from '~/types/models/form'
import type { TerrainFormState } from '~/types/models/terrain'

definePageMeta({
  layout: 'dashboard',
  title: 'Add Terrain'
})

const state = reactive<TerrainFormState>({
  title: '',
  province: '',
  city: '',
  area: '',
  price: '',
  description: '',
  videoUrl: '',
  agentName: '',
  agentEmail: ''
})

function validate(values: Record<string, unknown>) {
  const errors: FormError[] = []

  const title = String(values.title || '').trim()
  if (!title) errors.push({ name: 'title', message: 'Title is required' })

  const city = String(values.city || '').trim()
  if (!city) errors.push({ name: 'city', message: 'City is required' })

  const price = String(values.price || '').trim()
  if (!price) errors.push({ name: 'price', message: 'Price is required' })

  return errors
}

function onSubmit() {
  // TODO: wire to backend when available
}
</script>

<template>
  <ThemeAForm
    :state="state"
    :validate="validate"
    @submit="onSubmit"
  >
    <ThemeACard title="Upload Media">
      <UFileUpload
        multiple
        class="w-full"
      />
    </ThemeACard>

    <ThemeACard title="Information">
      <div class="grid gap-4 md:grid-cols-2">
        <UFormField
          label="Title"
          name="title"
          required
        >
          <UInput
            v-model="state.title"
            placeholder="Terrain name"
          />
        </UFormField>

        <UFormField
          label="Province"
          name="province"
        >
          <UInput
            v-model="state.province"
            placeholder="Province"
          />
        </UFormField>

        <UFormField
          label="City"
          name="city"
          required
        >
          <UInput
            v-model="state.city"
            placeholder="City"
          />
        </UFormField>

        <UFormField
          label="Area (m²)"
          name="area"
        >
          <UInput
            v-model="state.area"
            placeholder="Area"
          />
        </UFormField>
      </div>

      <div class="mt-4">
        <UFormField
          label="Description"
          name="description"
        >
          <UTextarea
            v-model="state.description"
            placeholder="Describe the terrain..."
          />
        </UFormField>
      </div>
    </ThemeACard>

    <ThemeACard title="Location">
      <div class="h-[320px] overflow-hidden rounded-xl ring ring-default">
        <CasablancaSettatMap
          map-id="add-terrain-map"
          height="100%"
          :zoom="9"
          :show-controls="false"
          :show-legend="true"
        />
      </div>
    </ThemeACard>

    <ThemeACard title="Price">
      <div class="grid gap-4 md:grid-cols-2">
        <UFormField
          label="Price"
          name="price"
          required
        >
          <UInput
            v-model="state.price"
            placeholder="MAD"
          />
        </UFormField>
        <UFormField
          label="Extra"
          name="extra"
        >
          <UInput placeholder="Optional" />
        </UFormField>
      </div>
    </ThemeACard>

    <ThemeACard title="Videos">
      <UFormField
        label="Video URL"
        name="videoUrl"
      >
        <UInput
          v-model="state.videoUrl"
          placeholder="https://..."
        />
      </UFormField>
    </ThemeACard>

    <ThemeACard title="Agent Information">
      <div class="grid gap-4 md:grid-cols-2">
        <UFormField
          label="Agent Name"
          name="agentName"
        >
          <UInput
            v-model="state.agentName"
            placeholder="Name"
          />
        </UFormField>
        <UFormField
          label="Agent Email"
          name="agentEmail"
        >
          <UInput
            v-model="state.agentEmail"
            placeholder="email@example.com"
            type="email"
          />
        </UFormField>
      </div>
    </ThemeACard>

    <div class="flex flex-col items-stretch justify-end gap-3 sm:flex-row sm:items-center">
      <UButton
        label="Save & Preview"
        color="neutral"
        variant="outline"
        class="rounded-full"
      />
      <UButton
        type="submit"
        label="Add Terrain"
        color="primary"
        class="rounded-full"
      />
    </div>
  </ThemeAForm>
</template>
