<script setup lang="ts">
import type { ColumnDef } from '@tanstack/vue-table'
import type { SavedSearchRow } from '~/types/models/saved-search'

definePageMeta({
  layout: 'dashboard',
  title: 'My save searches',
  middleware: 'auth'
})

const data: SavedSearchRow[] = [
  { query: 'Casablanca • MAD 500k–1M', created: 'Apr 10, 2025' },
  { query: 'Settat • Industrial', created: 'Apr 12, 2025' },
  { query: 'El Jadida • Coastal', created: 'Apr 15, 2025' }
]

const columns: Array<ColumnDef<SavedSearchRow>> = [
  { accessorKey: 'query', header: 'Search' },
  { accessorKey: 'created', header: 'Created' }
]
</script>

<template>
  <ThemeACard title="My save searches">
    <ThemeATable
      :data="data"
      :columns="columns"
    />
  </ThemeACard>
</template>
