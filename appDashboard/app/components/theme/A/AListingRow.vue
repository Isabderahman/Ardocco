<script setup lang="ts">
const props = withDefaults(defineProps<{
  title: string
  to?: string
  price?: string
  location?: string
  status?: string
  typeLabel?: string
  area?: string
  description?: string
  imageUrl?: string
  isFavorite?: boolean
  isExclusive?: boolean
  isUrgent?: boolean
}>(), {
  isFavorite: false,
  isExclusive: false,
  isUrgent: false
})

const emit = defineEmits<{
  (e: 'toggle-favorite'): void
}>()

type StatusBadge = {
  label: string
  color: 'neutral' | 'success' | 'warning' | 'error' | 'info' | 'primary' | 'secondary'
}

function statusBadge(value: string | undefined): StatusBadge | undefined {
  if (!value) return undefined
  const normalized = value.trim().toLowerCase()

  if (['publie', 'valide', 'published'].includes(normalized)) return { label: 'Published', color: 'success' }
  if (['soumis', 'en_revision', 'pending', 'draft'].includes(normalized)) return { label: 'Pending', color: 'warning' }
  if (['vendu', 'sold'].includes(normalized)) return { label: 'Sold', color: 'neutral' }
  if (['refuse', 'rejected'].includes(normalized)) return { label: 'Rejected', color: 'error' }

  return { label: value, color: 'neutral' }
}

const status = computed(() => statusBadge(props.status))
</script>

<template>
  <UCard class="group overflow-hidden rounded-2xl shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md">
    <div class="flex gap-4 p-4">
      <div class="relative h-28 w-32 shrink-0 overflow-hidden rounded-xl bg-elevated ring-1 ring-default">
        <img
          v-if="props.imageUrl"
          :src="props.imageUrl"
          :alt="props.title"
          class="h-full w-full object-cover"
          loading="lazy"
          decoding="async"
        >
        <div
          v-else
          class="absolute inset-0 grid place-items-center"
        >
          <UIcon
            name="i-lucide-image"
            class="size-6 text-dimmed"
          />
        </div>

        <div class="absolute left-2 top-2 flex flex-wrap items-center gap-1">
          <UBadge
            v-if="props.typeLabel"
            color="neutral"
            variant="solid"
          >
            {{ props.typeLabel }}
          </UBadge>
          <UBadge
            v-if="props.isExclusive"
            color="primary"
            variant="solid"
          >
            Exclusive
          </UBadge>
          <UBadge
            v-if="props.isUrgent"
            color="warning"
            variant="solid"
          >
            Urgent
          </UBadge>
        </div>

        <UButton
          type="button"
          color="neutral"
          variant="ghost"
          size="xs"
          class="absolute right-1.5 top-1.5 rounded-full bg-default/80 backdrop-blur"
          @click.stop="emit('toggle-favorite')"
        >
          <UIcon
            name="i-lucide-heart"
            class="size-4"
            :class="props.isFavorite ? 'text-primary' : 'text-muted'"
          />
        </UButton>
      </div>

      <div class="min-w-0 flex-1 space-y-2">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-highlighted">
              {{ props.title }}
            </p>
            <p
              v-if="props.description"
              class="mt-1 line-clamp-2 text-sm text-muted"
            >
              {{ props.description }}
            </p>
          </div>
          <UBadge
            v-if="status"
            :color="status.color"
            variant="soft"
            class="shrink-0"
          >
            {{ status.label }}
          </UBadge>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2">
          <div class="space-y-1">
            <p
              v-if="props.location"
              class="flex items-center gap-1 text-sm text-muted"
            >
              <UIcon
                name="i-lucide-map-pin"
                class="size-4"
              />
              <span class="truncate">{{ props.location }}</span>
            </p>

            <div class="flex flex-wrap items-center gap-3">
              <p
                v-if="props.price"
                class="text-sm font-semibold text-highlighted"
              >
                {{ props.price }}
              </p>
              <p
                v-if="props.area"
                class="flex items-center gap-1 text-sm text-muted"
              >
                <UIcon
                  name="i-lucide-ruler"
                  class="size-4"
                />
                <span>{{ props.area }}</span>
              </p>
            </div>
          </div>

          <UButton
            label="Details"
            color="neutral"
            variant="outline"
            size="sm"
            class="rounded-full"
            :to="props.to"
          />
        </div>
      </div>
    </div>
  </UCard>
</template>
