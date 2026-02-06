<script setup lang="ts">
import type { ColumnDef } from '@tanstack/vue-table'
import type { FavoritesTableRow } from '~/types/models/favorites'

definePageMeta({
  layout: 'dashboard',
  title: 'My favorites'
})

const data: FavoritesTableRow[] = [
  { name: 'Terrain name', seller: 'sellermail@gmail.com', added: 'Apr 10, 2025' },
  { name: 'Terrain name', seller: 'sellermail@gmail.com', added: 'Apr 10, 2025' },
  { name: 'Terrain name', seller: 'sellermail@gmail.com', added: 'Apr 10, 2025' }
]

const columns: Array<ColumnDef<FavoritesTableRow>> = [
  { accessorKey: 'name', header: 'Terrain name' },
  { accessorKey: 'seller', header: 'Seller' },
  { accessorKey: 'added', header: 'Added' }
]
</script>

<template>
  <ThemeACard title="My favorites">
    <ThemeATable
      :data="data"
      :columns="columns"
    />
  </ThemeACard>
</template>
