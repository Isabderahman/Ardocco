<script setup lang="ts">
import type { BackendListing } from '~/types/models/listing'

type TerrainTypeOption = { label: string, value: string }

type StatusBadge = {
  label: string
  color: 'neutral' | 'success' | 'warning' | 'error' | 'info' | 'primary' | 'secondary'
}

const props = withDefaults(defineProps<{
  listing: BackendListing
  terrainTypes: TerrainTypeOption[]
  isAuthenticated: boolean
  selected?: boolean
}>(), {
  selected: false
})

const emit = defineEmits<{
  (e: 'select', id: string): void
}>()

function terrainTypeLabel(value: string | null | undefined) {
  if (!value) return 'Non spécifié'
  const found = props.terrainTypes.find(t => t.value === value)
  return found?.label || value
}

function statusBadge(value: string | null | undefined): StatusBadge | undefined {
  if (!value) return undefined
  const normalized = value.trim().toLowerCase()

  if (['publie', 'valide', 'published'].includes(normalized)) return { label: 'Publié', color: 'success' }
  if (['soumis', 'en_revision', 'pending', 'draft'].includes(normalized)) return { label: 'En attente', color: 'warning' }
  if (['vendu', 'sold'].includes(normalized)) return { label: 'Vendu', color: 'neutral' }
  if (['refuse', 'rejected'].includes(normalized)) return { label: 'Refusé', color: 'error' }

  return { label: value, color: 'neutral' }
}

function formatCurrency(value: number | string | null | undefined) {
  const numValue = Number(value)
  if (!Number.isFinite(numValue) || numValue <= 0) return '—'

  return new Intl.NumberFormat('fr-MA', {
    style: 'currency',
    currency: 'MAD',
    maximumFractionDigits: 0
  }).format(numValue)
}

function formatArea(value: number | string | null | undefined) {
  const numValue = Number(value)
  if (!Number.isFinite(numValue) || numValue <= 0) return '—'
  return `${new Intl.NumberFormat('fr-MA', { maximumFractionDigits: 0 }).format(numValue)} m²`
}

const typeLabel = computed(() => terrainTypeLabel(props.listing.type_terrain))
const status = computed(() => statusBadge(props.listing.status))

const location = computed(() => {
  return props.listing.quartier
    || props.listing.commune?.name_fr
    || props.listing.commune?.province?.name_fr
    || 'Non spécifié'
})

const price = computed(() => formatCurrency(props.listing.prix_demande))
const area = computed(() => formatArea(props.listing.superficie))

function onSelect() {
  emit('select', props.listing.id)
}

function onFocus() {
  if (!props.selected) onSelect()
}
</script>

<template>
  <article
    class="group w-full jus rounded-2xl bg-default text-left transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 "
    :class="props.selected ? 'ring-2 ring-primary-500 bg-primary-50/40' : 'ring-1 ring-default hover:shadow-md'"
    role="button"
    tabindex="0"
    :aria-selected="props.selected ? 'true' : 'false'"
    @click="onSelect"
    @keydown.enter.prevent="onSelect"
    @focus="onFocus"
  >
    <div class="flex gap-3 p-3">
      <div class="h-40 w-35 shrink-0 overflow-hidden rounded-xl bg-gradient-to-br from-primary-50 to-primary-100 ring-1 ring-default grid place-items-center">
        <UIcon
          name="i-lucide-image"
          class="size-6 text-primary-400"
        />
      </div>

      <div class="min-w-0 flex-1">
        <div class="flex items-start justify-between gap-2">
          <p class="truncate text-sm font-semibold text-highlighted">
            {{ props.listing.title }}
          </p>

          <UButton
            icon="i-lucide-chevron-right"
            size="xs"
            color="neutral"
            variant="ghost"
            class="shrink-0 rounded-full"
            :to="`/terrains/${props.listing.id}`"
            aria-label="Voir les détails"
            @click.stop
          />
        </div>

        <p class="mt-1 flex items-center gap-1 text-xs text-muted">
          <UIcon
            name="i-lucide-map-pin"
            class="size-4 shrink-0"
          />
          <span class="truncate">{{ location }}</span>
        </p>

        <div class="mt-2 flex items-center justify-between gap-3">
          <p class="text-sm font-bold text-primary-700">
            {{ price }}
          </p>

          <div class="flex flex-wrap items-center justify-end gap-1.5">
            <UBadge
              color="neutral"
              variant="soft"
              size="xs"
            >
              {{ typeLabel }}
            </UBadge>
            <UBadge
              v-if="status"
              :color="status.color"
              variant="soft"
              size="xs"
            >
              {{ status.label }}
            </UBadge>
          </div>
        </div>

        <div class="mt-2 flex items-center justify-between gap-2 text-xs text-muted">
          <span class="inline-flex items-center gap-1">
            <UIcon
              name="i-lucide-ruler"
              class="size-4"
            />
            {{ area }}
          </span>

          <span
            v-if="!props.isAuthenticated"
            class="inline-flex items-center gap-1 text-dimmed"
          >
            <UIcon
              name="i-lucide-lock"
              class="size-3"
            />
            Accès limité
          </span>
        </div>
      </div>
    </div>
  </article>
</template>
