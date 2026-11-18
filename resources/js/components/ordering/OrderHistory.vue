<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import type { OwnOrder, HistoryPage } from '@/lib/ordering'
import OrderHistoryItem from './OrderHistoryItem.vue'
import Pagination from './Pagination.vue'
const props = defineProps<{ orders: HistoryPage<OwnOrder>; label: string }>()
const rows = ref([...props.orders.items])
const confirmation = ref('')
const feedback = ref<HTMLElement>()
watch(() => props.orders.items, value => { rows.value = [...value] })
async function cancelled(id: number) {
  rows.value = rows.value.filter(item => item.id !== id)
  confirmation.value = 'Storno vom Server bestätigt.'
  await nextTick()
  feedback.value?.focus({ preventScroll: true })
  router.reload()
}
</script>
<template>
  <ul v-if="rows.length" class="divide-y divide-stone-200"><OrderHistoryItem v-for="order in rows" :key="order.id" :order="order" @cancelled="cancelled" @started="confirmation = ''" /></ul>
  <p v-else class="py-4 text-stone-600">Keine Bestellungen vorhanden.</p>
  <p ref="feedback" tabindex="-1" role="status" aria-live="polite" class="mt-3 font-medium text-teal-900">{{ confirmation }}</p>
  <Pagination v-bind="orders" :label="label" />
</template>
