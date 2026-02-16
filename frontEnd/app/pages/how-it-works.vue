<script setup lang="ts">
definePageMeta({ title: 'Comment ça marche' })

const steps = [
  {
    number: '01',
    title: 'Recherchez',
    description: 'Explorez notre carte interactive pour trouver des terrains dans la région Casablanca-Settat. Utilisez les filtres pour affiner votre recherche.',
    icon: 'i-lucide-search'
  },
  {
    number: '02',
    title: 'Analysez',
    description: 'Consultez les analyses financières détaillées, les prix au m², et les informations sur chaque terrain.',
    icon: 'i-lucide-chart-bar'
  },
  {
    number: '03',
    title: 'Contactez',
    description: 'Entrez en contact avec les propriétaires ou nos experts pour obtenir plus d\'informations.',
    icon: 'i-lucide-message-circle'
  },
  {
    number: '04',
    title: 'Achetez',
    description: 'Finalisez votre achat en toute sécurité avec l\'accompagnement de nos experts immobiliers.',
    icon: 'i-lucide-check-circle'
  }
]
</script>

<template>
  <main class="bg-white">
    <!-- Hero Section -->
    <section class="bg-gradient-to-b from-blue-50 to-white py-16 sm:py-24">
      <UContainer>
        <div class="mx-auto max-w-3xl text-center">
          <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl lg:text-5xl">
            Comment ça marche
          </h1>
          <p class="mt-4 text-lg text-gray-600">
            Trouvez votre terrain idéal en quelques étapes simples
          </p>
        </div>
      </UContainer>
    </section>

    <!-- Steps Section -->
    <section class="py-16 sm:py-24">
      <UContainer>
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
          <div
            v-for="step in steps"
            :key="step.number"
            class="relative"
          >
            <div class="flex flex-col items-center text-center">
              <div class="flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 text-[#1A7BFD]">
                <UIcon
                  :name="step.icon"
                  class="h-8 w-8"
                />
              </div>
              <span class="mt-4 text-sm font-semibold text-[#1A7BFD]">
                Étape {{ step.number }}
              </span>
              <h3 class="mt-2 text-xl font-semibold text-gray-900">
                {{ step.title }}
              </h3>
              <p class="mt-3 text-sm text-gray-600">
                {{ step.description }}
              </p>
            </div>
          </div>
        </div>
      </UContainer>
    </section>

    <!-- Features Section -->
    <section class="bg-gray-50 py-16 sm:py-24">
      <UContainer>
        <div class="mx-auto max-w-3xl text-center">
          <h2 class="text-2xl font-bold text-gray-900 sm:text-3xl">
            Pourquoi choisir Ardocco ?
          </h2>
          <p class="mt-4 text-gray-600">
            Une plateforme moderne pour simplifier votre recherche de terrain
          </p>
        </div>

        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-[#1A7BFD]">
              <UIcon
                name="i-lucide-map"
                class="h-6 w-6"
              />
            </div>
            <h3 class="mt-4 text-lg font-semibold text-gray-900">
              Carte Interactive
            </h3>
            <p class="mt-2 text-sm text-gray-600">
              Visualisez tous les terrains disponibles sur une carte intuitive avec les prix affichés.
            </p>
          </div>

          <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-[#1A7BFD]">
              <UIcon
                name="i-lucide-calculator"
                class="h-6 w-6"
              />
            </div>
            <h3 class="mt-4 text-lg font-semibold text-gray-900">
              Analyse Financière
            </h3>
            <p class="mt-2 text-sm text-gray-600">
              Obtenez des analyses détaillées sur le prix au m², la rentabilité potentielle et plus.
            </p>
          </div>

          <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-[#1A7BFD]">
              <UIcon
                name="i-lucide-shield-check"
                class="h-6 w-6"
              />
            </div>
            <h3 class="mt-4 text-lg font-semibold text-gray-900">
              Accompagnement Expert
            </h3>
            <p class="mt-2 text-sm text-gray-600">
              Bénéficiez de l'expertise de nos professionnels pour sécuriser votre investissement.
            </p>
          </div>
        </div>
      </UContainer>
    </section>

    <!-- CTA Section -->
    <section class="py-16 sm:py-24">
      <UContainer>
        <div class="rounded-3xl bg-gradient-to-r from-[#1A7BFD] to-[#1565d8] px-8 py-12 text-center sm:px-12 sm:py-16">
          <h2 class="text-2xl font-bold text-white sm:text-3xl">
            Prêt à trouver votre terrain ?
          </h2>
          <p class="mt-4 text-blue-100">
            Commencez votre recherche dès maintenant
          </p>
          <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
            <UButton
              label="Explorer les terrains"
              color="neutral"
              size="lg"
              to="/terrains"
              icon="i-lucide-search"
              class="rounded-full font-semibold bg-white text-primary-700 hover:bg-white/90"
            />
            <UButton
              label="Nous contacter"
              variant="outline"
              size="lg"
              to="/contact"
              icon="i-lucide-mail"
              class="rounded-full font-semibold text-white ring-white/30 hover:bg-white/10"
            />
          </div>
        </div>
      </UContainer>
    </section>
  </main>
</template>
