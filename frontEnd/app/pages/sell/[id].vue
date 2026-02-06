<script setup lang="ts">
import type { BackendResponse } from '~/types/models/api'
import type { BackendListing } from '~/types/models/listing'

const route = useRoute()
const id = computed(() => String(route.params.id || ''))

const {
  data: response,
  pending,
  error
} = await useFetch<BackendResponse<BackendListing>>(
  () => `/api/backend/listings/${encodeURIComponent(id.value)}`,
  { watch: [id] }
)

const listing = computed(() => response.value?.data)

function asNumber(value: unknown): number | undefined {
  if (typeof value === 'number' && Number.isFinite(value)) return value
  if (typeof value === 'string' && value.trim().length) {
    const parsed = Number(value)
    if (Number.isFinite(parsed)) return parsed
  }
  return undefined
}

const price = computed(() => {
  const numeric = asNumber(listing.value?.prix_demande)
  if (numeric === undefined) return undefined
  return new Intl.NumberFormat('fr-MA', {
    style: 'currency',
    currency: 'MAD',
    maximumFractionDigits: 0
  }).format(numeric)
})

const location = computed(() => {
  const communeName = listing.value?.commune?.name_fr || ''
  const provinceName = listing.value?.commune?.province?.name_fr || ''
  if (communeName && provinceName) return `${communeName}, ${provinceName}`
  return communeName || provinceName || undefined
})

const form = reactive({
  name: '',
  email: '',
  message: ''
})

function onContact() {
  // TODO: wire to backend when available
}
</script>

<template>
  <UContainer class="py-10">
    <div class="grid gap-6 lg:grid-cols-3">
      <ThemeACard
        class="lg:col-span-2"
        :title="listing?.title || `Listing #${id}`"
        :description="listing?.status || 'Property listing'"
      >
        <UAlert
          v-if="error"
          color="error"
          title="Unable to load listing"
          variant="soft"
          :description="error.message"
        />
        <UAlert
          v-else-if="pending"
          color="neutral"
          title="Loading"
          variant="soft"
          description="Fetching listing..."
        />
        <div class="space-y-6">
          <div class="aspect-[16/9] overflow-hidden rounded-xl bg-elevated ring ring-default" />

          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <p class="text-sm font-semibold text-highlighted">
                Location
              </p>
              <p class="mt-1 text-sm text-muted">
                {{ location || '—' }}
              </p>
            </div>
            <div>
              <p class="text-sm font-semibold text-highlighted">
                Price
              </p>
              <p class="mt-1 text-sm text-muted">
                {{ price || '—' }}
              </p>
            </div>
          </div>

          <div>
            <p class="text-sm font-semibold text-highlighted">
              Description
            </p>
            <p class="mt-2 text-sm text-muted">
              {{ listing?.description || '—' }}
            </p>
          </div>
        </div>
      </ThemeACard>

      <ThemeACard title="Contact Sellers">
        <ThemeAForm
          :state="form"
          @submit="onContact"
        >
          <div class="space-y-4">
            <UFormField
              label="Your name"
              name="name"
            >
              <UInput v-model="form.name" />
            </UFormField>
            <UFormField
              label="Your email"
              name="email"
            >
              <UInput
                v-model="form.email"
                type="email"
              />
            </UFormField>
            <UFormField
              label="Message"
              name="message"
            >
              <UTextarea
                v-model="form.message"
                :rows="4"
              />
            </UFormField>
            <UButton
              type="submit"
              label="Send"
              color="primary"
              class="w-full rounded-full"
            />
          </div>
        </ThemeAForm>
      </ThemeACard>
    </div>
  </UContainer>
</template>
