<template>
  <main>
    <HomeHero />

    <section class="py-12 sm:py-16 bg-elevated/30">
      <UContainer>
        <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
          <div>
            <h2 class="text-2xl font-semibold text-highlighted">
              Featured Terrains
            </h2>
            <p class="mt-2 text-sm text-muted">
              Discover hand‑picked terrains across Casablanca–Settat.
            </p>
          </div>

          <UButton
            label="View all"
            color="neutral"
            variant="outline"
            to="/sell"
            class="rounded-full"
          />
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <ThemeATerrainCard
            v-for="t in featuredTerrains"
            :key="t.id"
            :title="t.title"
            :description="t.description"
            :location="t.location"
            :price="t.price"
            :badge="t.badge"
            :to="t.to"
          />
        </div>
      </UContainer>
    </section>
  </main>
</template>

<script setup lang="ts">
import type { TerrainCard } from '~/types/models/terrain'

const featuredTerrains: TerrainCard[] = [
  {
    id: 't1',
    title: 'Terrain — Casablanca Center',
    description: 'Ideal for residential development',
    location: 'Casablanca',
    price: 'MAD 1,250,000',
    badge: 'Featured',
    to: '/sell/1'
  },
  {
    id: 't2',
    title: 'Terrain — Settat Roadside',
    description: 'Great visibility, easy access',
    location: 'Settat',
    price: 'MAD 980,000',
    badge: 'New',
    to: '/sell/2'
  },
  {
    id: 't3',
    title: 'Terrain — Coastal Opportunity',
    description: 'Perfect for tourism projects',
    location: 'El Jadida',
    price: 'MAD 2,300,000',
    badge: 'Hot',
    to: '/sell/3'
  },
  {
    id: 't4',
    title: 'Terrain — Growing Area',
    description: 'High potential neighborhood',
    location: 'Berrechid',
    price: 'MAD 740,000',
    badge: 'Featured',
    to: '/sell/4'
  },
  {
    id: 't5',
    title: 'Terrain — Industrial Zone',
    description: 'Suitable for warehouses and logistics',
    location: 'Nouaceur',
    price: 'MAD 3,100,000',
    badge: 'Verified',
    to: '/sell/5'
  },
  {
    id: 't6',
    title: 'Terrain — Quiet Residential',
    description: 'Low density, calm surroundings',
    location: 'Benslimane',
    price: 'MAD 860,000',
    badge: 'Featured',
    to: '/sell/6'
  }
]
</script>
