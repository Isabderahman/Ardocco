<script setup lang="ts">
import type { ColumnDef } from '@tanstack/vue-table'
import { h } from 'vue'
import type { DashboardFavoriteRow, DashboardReview } from '~/types/models/dashboard'

definePageMeta({
  layout: 'dashboard',
  title: 'Dashboard'
})

const favorites: DashboardFavoriteRow[] = [
  {
    listing: { title: 'Lorem ipsum dolor sit amet', price: '$750' },
    status: 'April 10, 2025',
    date: 'April 10, 2025'
  },
  {
    listing: { title: 'Lorem ipsum dolor sit amet', price: '$850' },
    status: 'April 10, 2025',
    date: 'April 10, 2025'
  },
  {
    listing: { title: 'Lorem ipsum dolor sit amet', price: '$500' },
    status: 'April 10, 2025',
    date: 'April 10, 2025'
  }
]

const columns: Array<ColumnDef<DashboardFavoriteRow>> = [
  {
    accessorKey: 'listing',
    header: 'Listing',
    cell: ({ getValue }) => {
      const listing = getValue() as DashboardFavoriteRow['listing']
      return h('div', { class: 'flex items-center gap-3' }, [
        h('div', { class: 'size-12 rounded-md bg-elevated' }),
        h('div', { class: 'min-w-0' }, [
          h('p', { class: 'truncate text-sm font-semibold text-highlighted' }, listing.title),
          h('p', { class: 'text-sm text-primary' }, listing.price)
        ])
      ])
    }
  },
  { accessorKey: 'status', header: 'Status' },
  { accessorKey: 'date', header: 'Date' },
  {
    id: 'action',
    header: 'Action',
    cell: () => h('div', { class: 'flex flex-col items-start gap-1' }, [
      h('button', { type: 'button', class: 'text-xs text-muted hover:text-highlighted' }, 'Edit'),
      h('button', { type: 'button', class: 'text-xs text-muted hover:text-highlighted' }, 'Sold'),
      h('button', { type: 'button', class: 'text-xs text-muted hover:text-highlighted' }, 'Delete')
    ])
  }
]

const reviews: DashboardReview[] = [
  {
    name: 'Lorem Ipsum',
    time: '3 day ago',
    message: 'Lorem ipsum dolor sit amet, consectetur incididunt. Lorem mollit esse lorem elit.'
  },
  {
    name: 'Lorem Ipsum',
    time: '1 day ago',
    message: 'Lorem ipsum dolor sit amet, consectetur incididunt. Lorem mollit esse lorem elit.'
  },
  {
    name: 'Lorem Ipsum',
    time: '5 day ago',
    message: 'Lorem ipsum dolor sit amet, consectetur incididunt. Lorem mollit esse lorem elit.'
  }
]
</script>

<template>
  <div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <ThemeAStatCard
        label="Your listing"
        value="32"
        helper="/50 remaining"
        icon="i-lucide-layout-grid"
      />
      <ThemeAStatCard
        label="Pending"
        value="02"
        icon="i-lucide-clock-3"
      />
      <ThemeAStatCard
        label="Favorites"
        value="06"
        icon="i-lucide-heart"
      />
      <ThemeAStatCard
        label="Reviews"
        value="1.483"
        icon="i-lucide-message-square-text"
      />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
      <ThemeACard
        title="My Favorites"
        class="lg:col-span-2"
      >
        <ThemeATable
          :data="favorites"
          :columns="columns"
          :ui="{
            th: 'bg-slate-900 text-white text-xs font-semibold uppercase tracking-wide py-3',
            td: 'p-4 text-sm text-muted'
          }"
        />
      </ThemeACard>

      <ThemeACard title="Recent Reviews">
        <div class="space-y-4">
          <div
            v-for="r in reviews"
            :key="`${r.name}-${r.time}`"
            class="flex items-start gap-3"
          >
            <UAvatar
              :text="r.name.slice(0, 1)"
              size="sm"
            />
            <div class="min-w-0">
              <div class="flex items-center justify-between gap-2">
                <p class="text-sm font-semibold text-highlighted">
                  {{ r.name }}
                </p>
                <p class="text-xs text-muted">
                  {{ r.time }}
                </p>
              </div>
              <p class="mt-1 text-sm text-muted">
                {{ r.message }}
              </p>
            </div>
          </div>
        </div>
      </ThemeACard>
    </div>
  </div>
</template>
