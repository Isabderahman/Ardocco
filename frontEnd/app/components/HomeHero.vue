<script setup lang="ts">
const props = withDefaults(defineProps<{
  title?: string
  subtitle?: string
  mapId?: string
  suggestions?: string[]
}>(), {
  title: 'Find Your Terrain',
  subtitle: 'Thousands of investors and land seekers like you have found their ideal terrain.',
  mapId: 'home-hero-map',
  suggestions: () => ([
    'Casablanca',
    'Settat',
    'El Jadida',
    'Berrechid',
    'Nouaceur'
  ])
})

const route = useRoute()
const query = ref(typeof route.query.q === 'string' ? route.query.q : '')
const searching = ref(false)

async function onSearch() {
  searching.value = true
  try {
    const q = query.value.trim()
    await navigateTo({ path: '/terrains', query: q ? { q } : {} })
  } finally {
    searching.value = false
  }
}

function onSuggestion(value: string) {
  query.value = value
  void onSearch()
}
</script>

<template>
  <section class="relative overflow-hidden h-[320px] sm:h-[420px] md:h-[480px] lg:h-[560px]">
    <div class="absolute inset-0 pointer-events-none">
      <CasablancaSettatMap
        :map-id="props.mapId"
        height="100%"
        :zoom="9"
        :show-controls="false"
        :show-legend="false"
        :show-zoom-control="false"
        :fit-to-region="false"
      />
    </div>
    <div class="absolute inset-0 bg-slate-800/65" />

    <UContainer class="relative py-16 sm:py-24">
      <div class="mx-auto max-w-6xl text-center">
        <h1 class="text-3xl tracking-wider font-semibold tracking-tight text-white sm:text-5xl">
          {{ props.title }}
        </h1>
        <p class="mt-3 text-md text-white/80 font-semibold sm:text-base">
          {{ props.subtitle }}
        </p>

        <form
          class="mx-auto mt-8 flex w-full max-w-5xl items-center gap-2 rounded-full bg-white/95 p-2 shadow-xl ring-1 ring-black/10 backdrop-blur"
          @submit.prevent="onSearch"
        >
          <UInput
            v-model="query"
            size="xl"
            variant="none"
            placeholder="Search available terrains ..."
            leading-icon="i-lucide-search"
            class="flex-1"
            :ui="{
              base: 'bg-transparent ring-0 focus-visible:ring-0 rounded-full'
            }"
          />
          <UButton
            type="submit"
            color="primary"
            size="xl"
            class="rounded-full px-8"
            :loading="searching"
          >
            Search
          </UButton>
        </form>

        <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
          <UButton
            v-for="s in props.suggestions"
            :key="s"
            size="sm"
            color="neutral"
            variant="outline"
            class="rounded-full bg-white/5 text-white hover:bg-white/10 text-md font-semibold px-4 cursor-pointer"
            @click="onSuggestion(s)"
          >
            {{ s }}
          </UButton>
        </div>
      </div>
    </UContainer>
  </section>
</template>
