<script setup lang="ts">
import type { Deposit, HistoryPage } from '@/lib/ordering'
import Pagination from './Pagination.vue'
defineProps<{ deposits: HistoryPage<Deposit> }>()
</script>
<template>
  <ul v-if="deposits.items.length" class="divide-y divide-stone-200">
    <li v-for="deposit in deposits.items" :key="deposit.id" class="py-4">
      <p class="font-semibold">{{ deposit.value }}</p><p class="mt-1">{{ deposit.comment }}</p><time :datetime="deposit.date" class="text-sm text-stone-600">{{ deposit.date }}</time>
    </li>
  </ul>
  <p v-else class="py-4 text-stone-600">Keine Buchungen vorhanden.</p>
  <Pagination v-bind="deposits" label="Buchungsverlauf" />
</template>
