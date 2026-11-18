<script setup lang="ts">
import { unref, watch } from 'vue'
import { useRemember } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import ActionFeedback from './ActionFeedback.vue'
import { useConfirmedAction, type OwnOrder, type MealCardData } from '@/lib/ordering'
const props = defineProps<{ meal: MealCardData; order?: OwnOrder }>()
const emit = defineEmits<{ confirmed: [order: OwnOrder | null]; started: [] }>()
const selection = unref(useRemember({ meal_id: props.order?.meal_id ?? props.meal.choices[0]?.id, quantity: props.order?.quantity ?? 1 }, `meal:${props.meal.id}:order:${props.order?.id ?? 'new'}`))
const { pending, error, success, sessionExpired, execute } = useConfirmedAction()
watch(() => props.order, (order, previous) => {
  if (order && (order.meal_id !== previous?.meal_id || order.quantity !== previous?.quantity)) { selection.meal_id = order.meal_id; selection.quantity = order.quantity }
})
async function submit(cancel = false) {
  emit('started')
  const result = await execute<OwnOrder>(cancel ? props.order!.deleteUrl : props.order?.updateUrl ?? props.meal.storeUrl,
    cancel ? 'DELETE' : props.order ? 'PUT' : 'POST', cancel ? undefined : { ...selection })
  if (!result) return
  if (!cancel && (!result.data || typeof result.data.id !== 'number' || typeof result.data.quantity !== 'number')) {
    error.value = 'Die Serverantwort bestätigt keine Bestellung. Bitte prüfen Sie die Übersicht.'
    return
  }
  emit('confirmed', cancel ? null : result.data)
}
</script>
<template>
  <form class="mt-5 space-y-4" :aria-label="`Bestellung für ${meal.title}`" :aria-busy="pending" @submit.prevent="submit()">
    <fieldset :disabled="pending || (order ? !order.canChange : !meal.canOrder)" class="space-y-4">
      <legend class="sr-only">Gericht und Menge</legend>
      <div>
        <label :for="`variant-${meal.id}-${order?.id ?? 'new'}`">Variante</label>
        <select :id="`variant-${meal.id}-${order?.id ?? 'new'}`" v-model="selection.meal_id" required>
          <option v-for="choice in meal.choices" :key="choice.id" :value="choice.id">{{ choice.title }} · {{ choice.price }}</option>
        </select>
      </div>
      <div>
        <label :for="`quantity-${meal.id}-${order?.id ?? 'new'}`">Menge</label>
        <input :id="`quantity-${meal.id}-${order?.id ?? 'new'}`" v-model.number="selection.quantity" type="number" min="1" max="10" required inputmode="numeric">
      </div>
      <div v-if="order ? order.canChange : meal.canOrder" class="flex flex-wrap gap-3">
        <Button type="submit">{{ pending ? 'Wird gesendet …' : order ? 'Bestellung ändern' : 'Bestellen' }}</Button>
        <Button v-if="order" type="button" variant="outline" @click="submit(true)">Stornieren</Button>
      </div>
    </fieldset>
    <p v-if="!(order ? order.canChange : meal.canOrder)" class="text-sm text-stone-700">Die Bestellfrist ist abgelaufen. Änderungen sind nicht mehr möglich.</p>
    <ActionFeedback :error="error" :success="success" :session-expired="sessionExpired" />
  </form>
</template>
