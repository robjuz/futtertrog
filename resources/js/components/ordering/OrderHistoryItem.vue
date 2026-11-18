<script setup lang="ts">
import { useProfileTranslations, useLocaleDate } from '@/lib/translations'
import { Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { useConfirmedAction, type OwnOrder } from '@/lib/ordering'
import ActionFeedback from './ActionFeedback.vue'
const t = useProfileTranslations()
const formatDate = useLocaleDate()
const props = defineProps<{ order: OwnOrder }>()
const emit = defineEmits<{ cancelled: [id: number]; started: [] }>()
const { pending, error, success, sessionExpired, execute } = useConfirmedAction()
async function cancel() {
  emit('started')
  if (await execute(props.order.deleteUrl, 'DELETE')) emit('cancelled', props.order.id)
}
</script>
<template>
  <li class="py-4">
    <p class="font-semibold">{{ order.quantity }} × {{ order.title }}</p>
    <p v-if="order.description" class="mt-2 text-stone-700">{{ order.description }}</p>
    <p class="mt-1 text-sm text-stone-700"><time :datetime="order.date">{{ formatDate(order.date) }}</time> · {{ order.subtotal }} · {{ t('Bestellstatus:') }} {{ order.status }}</p>
    <div v-if="order.canChange && order.quantity > 0" class="mt-3 flex flex-wrap items-center gap-3">
      <Link :href="`/meals?date=${order.date}`" class="rounded-md px-3 py-2 font-medium underline">{{ t('Auswahl ändern') }}</Link>
      <Button variant="outline" type="button" :disabled="pending" :aria-busy="pending" @click="cancel">{{ pending ? t('Wird storniert …') : t('Stornieren') }}</Button>
    </div>
    <p v-else-if="order.quantity === 0" class="mt-2 text-sm">{{ t('Storniert') }}</p>
    <ActionFeedback :error="error" :success="success" :session-expired="sessionExpired" />
  </li>
</template>
