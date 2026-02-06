<script setup lang="ts">
import type { ColumnDef } from '@tanstack/vue-table'
import type { TerrainTableRow } from '~/types/models/terrain'

definePageMeta({
  layout: 'dashboard',
  title: 'My Terrains'
})

const data: TerrainTableRow[] = [
  { name: 'Terrain name', status: 'Active', updated: 'Apr 10, 2025' },
  { name: 'Terrain name', status: 'Pending', updated: 'Apr 12, 2025' }
]

const columns: Array<ColumnDef<TerrainTableRow>> = [
  { accessorKey: 'name', header: 'Terrain name' },
  { accessorKey: 'status', header: 'Status' },
  { accessorKey: 'updated', header: 'Updated' }
]
</script>

<template>
  <ThemeACard
    title="My Terrains"
    description="Manage your listings."
  >
    <ThemeATable
      :data="data"
      :columns="columns"
    />
  </ThemeACard>
</template>
