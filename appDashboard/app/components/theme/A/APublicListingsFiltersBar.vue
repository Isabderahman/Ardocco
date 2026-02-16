<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import type { PublicListingsFilters } from '~/types/models/listing'

type Option = { label: string, value: string }

const props = withDefaults(defineProps<{
  modelValue: PublicListingsFilters
  terrainTypes: Option[]
  provinceOptions: Option[]
  provinces: string[]
  activeCount: number
  disabled?: boolean
}>(), {
  provinces: () => [],
  disabled: false
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: PublicListingsFilters): void
  (e: 'update:provinces', value: string[]): void
  (e: 'reset'): void
}>()

const drawerOpen = ref(false)

function updateFilters(patch: Partial<PublicListingsFilters>) {
  emit('update:modelValue', { ...props.modelValue, ...patch })
}

const localSearch = ref(props.modelValue.q || '')
watch(
  () => props.modelValue.q,
  (value) => {
    const next = value || ''
    if (next !== localSearch.value) localSearch.value = next
  }
)

const debouncedSearchUpdate = useDebounceFn((value: string) => {
  updateFilters({ q: value })
}, 350)

watch(localSearch, (value) => {
  debouncedSearchUpdate(value)
})

function clearSearch() {
  localSearch.value = ''
  updateFilters({ q: '' })
}

const typeTerrain = computed({
  get: () => props.modelValue.type_terrain,
  set: (value: string) => updateFilters({ type_terrain: value || '' })
})

const prixMin = computed({
  get: () => props.modelValue.prix_min,
  set: (value: string) => updateFilters({ prix_min: value || '' })
})

const prixMax = computed({
  get: () => props.modelValue.prix_max,
  set: (value: string) => updateFilters({ prix_max: value || '' })
})

const superficieMin = computed({
  get: () => props.modelValue.superficie_min,
  set: (value: string) => updateFilters({ superficie_min: value || '' })
})

const superficieMax = computed({
  get: () => props.modelValue.superficie_max,
  set: (value: string) => updateFilters({ superficie_max: value || '' })
})

const rentabiliteMin = computed({
  get: () => props.modelValue.rentabilite_min,
  set: (value: string) => updateFilters({ rentabilite_min: value || '' })
})

const provincesModel = computed({
  get: () => props.provinces,
  set: (value: string[]) => emit('update:provinces', value)
})
</script>

<template>
  <ThemeACard :ui="{ body: 'p-4' }">
    <!-- Mobile -->
    <div class="lg:hidden space-y-3">
      <div class="flex items-start gap-2">
        <div class="flex-1">
          <label class="sr-only" for="terrain-search-mobile">
            Rechercher
          </label>
          <UInput
            id="terrain-search-mobile"
            v-model="localSearch"
            :disabled="props.disabled"
            placeholder="Rechercher un quartier, une commune…"
            icon="i-lucide-search"
            size="md"
            class="w-full"
          >
            <template #trailing>
              <UButton
                v-if="localSearch"
                icon="i-lucide-x"
                size="xs"
                color="neutral"
                variant="ghost"
                class="rounded-full"
                aria-label="Effacer la recherche"
                @click="clearSearch"
              />
            </template>
          </UInput>
        </div>

        <UDrawer
          v-model:open="drawerOpen"
          title="Filtres"
          :ui="{ content: 'max-h-[86vh]' }"
        >
          <UButton
            color="neutral"
            variant="outline"
            size="md"
            icon="i-lucide-sliders-horizontal"
            :disabled="props.disabled"
            class="shrink-0"
            aria-label="Ouvrir les filtres"
          >
            <span class="hidden sm:inline">Filtres</span>
            <span
              v-if="props.activeCount"
              class="ml-1 inline-flex size-5 items-center justify-center rounded-full bg-primary-500 text-white text-xs font-bold"
              aria-hidden="true"
            >
              {{ props.activeCount }}
            </span>
          </UButton>

          <template #body>
            <div class="space-y-5">
              <div>
                <label class="block text-sm font-medium text-highlighted mb-1" for="terrain-type-mobile">Type</label>
                <USelect
                  id="terrain-type-mobile"
                  v-model="typeTerrain"
                  :items="props.terrainTypes"
                  placeholder="Tous les types"
                  size="md"
                  class="w-full"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-highlighted mb-1" for="terrain-provinces-mobile">Provinces</label>
                <USelectMenu
                  id="terrain-provinces-mobile"
                  v-model="provincesModel"
                  :items="props.provinceOptions"
                  value-key="value"
                  multiple
                  placeholder="Toutes les provinces"
                  size="md"
                  class="w-full"
                />
              </div>

              <div>
                <p class="text-sm font-medium text-highlighted mb-2">Prix (MAD)</p>
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="sr-only" for="terrain-price-min-mobile">Prix min</label>
                    <UInput
                      id="terrain-price-min-mobile"
                      v-model="prixMin"
                      :disabled="props.disabled"
                      placeholder="Min"
                      size="md"
                      class="w-full"
                      inputmode="numeric"
                      autocomplete="off"
                    />
                  </div>
                  <div>
                    <label class="sr-only" for="terrain-price-max-mobile">Prix max</label>
                    <UInput
                      id="terrain-price-max-mobile"
                      v-model="prixMax"
                      :disabled="props.disabled"
                      placeholder="Max"
                      size="md"
                      class="w-full"
                      inputmode="numeric"
                      autocomplete="off"
                    />
                  </div>
                </div>
              </div>

              <div>
                <p class="text-sm font-medium text-highlighted mb-2">Surface (m²)</p>
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="sr-only" for="terrain-area-min-mobile">Surface min</label>
                    <UInput
                      id="terrain-area-min-mobile"
                      v-model="superficieMin"
                      :disabled="props.disabled"
                      placeholder="Min"
                      size="md"
                      class="w-full"
                      inputmode="numeric"
                      autocomplete="off"
                    />
                  </div>
                  <div>
                    <label class="sr-only" for="terrain-area-max-mobile">Surface max</label>
                    <UInput
                      id="terrain-area-max-mobile"
                      v-model="superficieMax"
                      :disabled="props.disabled"
                      placeholder="Max"
                      size="md"
                      class="w-full"
                      inputmode="numeric"
                      autocomplete="off"
                    />
                  </div>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-highlighted mb-1" for="terrain-roi-mobile">Rentabilité min (%)</label>
                <UInput
                  id="terrain-roi-mobile"
                  v-model="rentabiliteMin"
                  :disabled="props.disabled"
                  placeholder="Ex: 10"
                  size="md"
                  class="w-full"
                  inputmode="numeric"
                  autocomplete="off"
                />
              </div>
            </div>
          </template>

          <template #footer>
            <div class="flex items-center justify-between gap-2">
              <UButton
                label="Réinitialiser"
                color="neutral"
                variant="outline"
                :disabled="props.disabled"
                @click="emit('reset')"
              />
              <UButton
                label="Voir les résultats"
                color="primary"
                :disabled="props.disabled"
                @click="drawerOpen = false"
              />
            </div>
          </template>
        </UDrawer>
      </div>
    </div>

    <!-- Desktop -->
    <div class="hidden lg:block space-y-3">
      <div class="grid grid-cols-12 gap-3">
        <div class="col-span-5">
          <label class="sr-only" for="terrain-search-desktop">
            Rechercher
          </label>
          <UInput
            id="terrain-search-desktop"
            v-model="localSearch"
            :disabled="props.disabled"
            placeholder="Rechercher un quartier, une commune…"
            icon="i-lucide-search"
            size="md"
            class="w-full"
          >
            <template #trailing>
              <UButton
                v-if="localSearch"
                icon="i-lucide-x"
                size="xs"
                color="neutral"
                variant="ghost"
                class="rounded-full"
                aria-label="Effacer la recherche"
                @click="clearSearch"
              />
            </template>
          </UInput>
        </div>

        <div class="col-span-2">
          <label class="sr-only" for="terrain-type-desktop">Type</label>
          <USelect
            id="terrain-type-desktop"
            v-model="typeTerrain"
            :items="props.terrainTypes"
            placeholder="Tous les types"
            size="md"
            class="w-full"
          />
        </div>

        <div class="col-span-3">
          <label class="sr-only" for="terrain-provinces-desktop">Provinces</label>
          <USelectMenu
            id="terrain-provinces-desktop"
            v-model="provincesModel"
            :items="props.provinceOptions"
            value-key="value"
            multiple
            placeholder="Toutes les provinces"
            size="md"
            class="w-full"
          />
        </div>

        <div class="col-span-2 flex items-center justify-end">
          <UButton
            label="Réinitialiser"
            icon="i-lucide-rotate-ccw"
            color="neutral"
            variant="outline"
            :disabled="props.disabled"
            class="w-full"
            @click="emit('reset')"
          />
        </div>
      </div>

      <div class="grid grid-cols-12 gap-3">
        <div class="col-span-3">
          <label class="sr-only" for="terrain-price-min-desktop">Prix min</label>
          <UInput
            id="terrain-price-min-desktop"
            v-model="prixMin"
            :disabled="props.disabled"
            placeholder="Prix min (MAD)"
            size="md"
            class="w-full"
            inputmode="numeric"
            autocomplete="off"
          />
        </div>

        <div class="col-span-3">
          <label class="sr-only" for="terrain-price-max-desktop">Prix max</label>
          <UInput
            id="terrain-price-max-desktop"
            v-model="prixMax"
            :disabled="props.disabled"
            placeholder="Prix max (MAD)"
            size="md"
            class="w-full"
            inputmode="numeric"
            autocomplete="off"
          />
        </div>

        <div class="col-span-2">
          <label class="sr-only" for="terrain-area-min-desktop">Surface min</label>
          <UInput
            id="terrain-area-min-desktop"
            v-model="superficieMin"
            :disabled="props.disabled"
            placeholder="Surface min"
            size="md"
            class="w-full"
            inputmode="numeric"
            autocomplete="off"
          />
        </div>

        <div class="col-span-2">
          <label class="sr-only" for="terrain-area-max-desktop">Surface max</label>
          <UInput
            id="terrain-area-max-desktop"
            v-model="superficieMax"
            :disabled="props.disabled"
            placeholder="Surface max"
            size="md"
            class="w-full"
            inputmode="numeric"
            autocomplete="off"
          />
        </div>

        <div class="col-span-2">
          <label class="sr-only" for="terrain-roi-desktop">Rentabilité min</label>
          <UInput
            id="terrain-roi-desktop"
            v-model="rentabiliteMin"
            :disabled="props.disabled"
            placeholder="Rentabilité min (%)"
            size="md"
            class="w-full"
            inputmode="numeric"
            autocomplete="off"
          />
        </div>
      </div>
    </div>
  </ThemeACard>
</template>
