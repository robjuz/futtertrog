<script setup lang="ts">
import { ref, watch, nextTick, unref, reactive } from 'vue'
import { router, useRemember } from '@inertiajs/vue3'
import OrderEditor from './OrderEditor.vue'
import type { MealCardData, OwnOrder } from '@/lib/ordering'
const props = defineProps<{ meal: MealCardData }>()
const orders = ref([...props.meal.orders])
const notice = unref(useRemember(reactive({ message: '' }), `meal-status:${props.meal.id}`))
const heading = ref<HTMLElement>()
watch(() => props.meal.orders, value => { orders.value = [...value] })
async function confirmed(order: OwnOrder | null, previous?: OwnOrder) {
  orders.value = orders.value.filter(item => item.id !== previous?.id && item.id !== order?.id)
  if (order) orders.value.push(order)
  notice.message = order ? 'Bestellung vom Server bestätigt.' : 'Storno vom Server bestätigt.'
  await nextTick()
  heading.value?.focus({ preventScroll: true })
  router.reload()
}
</script>
<template>
  <article class="profile-card h-full" :aria-labelledby="`meal-title-${meal.id}`" :data-meal-id="meal.id">
    <p class="mb-2 text-sm font-semibold text-teal-800">{{ meal.provider }}</p>
    <h2 ref="heading" tabindex="-1" :id="`meal-title-${meal.id}`" class="text-xl font-semibold">{{ meal.title }}</h2>
    <p v-if="meal.preferred || meal.avoided" class="mt-2 text-sm text-stone-700">{{ meal.avoided ? 'Enthält eine Ihrer Abneigungen' : 'Passt zu Ihren Vorlieben' }}</p>
    <img v-if="meal.image" :src="meal.image" alt="" loading="lazy" class="mt-4 max-h-48 w-full rounded-lg object-cover">
    <p v-if="meal.description" class="mt-4 text-stone-700">{{ meal.description }}</p>
    <p v-if="meal.info.calories" class="mt-3 text-sm">{{ meal.info.calories }} kcal</p>
    <details v-if="meal.info.allergens.length" class="mt-3 text-sm"><summary>Allergene</summary><p>{{ meal.info.allergens.join(', ') }}</p></details>
    <div v-for="order in orders" :key="order.id" class="mt-5 rounded-lg border border-teal-300 bg-teal-50 p-4">
      <p class="font-semibold">{{ order.quantity }} × {{ order.title }}</p>
      <p class="mt-1 text-sm">Bestellstatus: {{ order.status }} · {{ order.subtotal }}</p>
      <OrderEditor :meal="meal" :order="order" @confirmed="confirmed($event, order)" @started="notice.message = ''" />
    </div>
    <OrderEditor v-if="!orders.length" :meal="meal" @confirmed="confirmed($event)" @started="notice.message = ''" />
    <p role="status" aria-live="polite" class="mt-3 text-sm font-medium text-teal-900">{{ notice.message }}</p>
  </article>
</template>
