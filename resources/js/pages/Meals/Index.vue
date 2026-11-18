<script setup lang="ts">
import { useProfileTranslations } from '@/lib/translations'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { Button } from '@/components/ui/button'
import MealCard from '@/components/ordering/MealCard.vue'
import ActionFeedback from '@/components/ordering/ActionFeedback.vue'
import { useConfirmedAction, type MealCardData } from '@/lib/ordering'
const t = useProfileTranslations()
const props = defineProps<{
  meals: MealCardData[]; date: string; provider: string; providers: { id: string; name: string }[]
  days: { date: string; label: string; ordered: boolean; notificationDisabled: boolean }[]
  previousWeek: string; nextWeek: string | null; notificationDisabled: boolean; notificationUrl: string
}>()
const dateInput = ref(props.date)
const providerInput = ref(props.provider)
const notificationDisabled = ref(props.notificationDisabled)
watch(() => [props.date, props.provider], () => { dateInput.value = props.date; providerInput.value = props.provider; notificationDisabled.value = props.notificationDisabled })
const { pending, error, success, sessionExpired, execute } = useConfirmedAction()
const url = (date: string) => `/meals?${new URLSearchParams({ date, provider: props.provider })}`
function filter() {
  router.get('/meals', { date: dateInput.value, provider: providerInput.value }, { preserveScroll: true })
}
async function toggleNotification() {
  if (await execute(props.notificationUrl, notificationDisabled.value ? 'DELETE' : 'POST', { date: props.date })) {
    notificationDisabled.value = !notificationDisabled.value
    success.value = notificationDisabled.value ? t('Erinnerung für diesen Tag ausgeschaltet.') : t('Erinnerung für diesen Tag eingeschaltet.')
  }
}
</script>
<template>
  <Head :title="t('Bestellen')" />
  <div class="mb-7"><p class="eyebrow">{{ t('Ihr Mittagessen') }}</p><h1 class="page-title">{{ t('Bestellen') }}</h1><p class="mt-3 text-stone-600">{{ t('Tag wählen, Gericht finden und die Bestellung bestätigen lassen.') }}</p></div>
  <section :aria-label="t('Tagesauswahl und Filter')" class="profile-card mb-7">
    <nav :aria-label="t('Wochenauswahl')" class="mb-5 flex items-center justify-between gap-4 text-sm font-semibold">
      <Link :href="url(previousWeek)" preserve-scroll class="rounded-md border border-stone-400 px-3 py-3">{{ t('Vorherige Woche') }}</Link>
      <Link v-if="nextWeek" :href="url(nextWeek)" preserve-scroll class="rounded-md border border-stone-400 px-3 py-3">{{ t('Nächste Woche') }}</Link>
    </nav>
    <nav :aria-label="t('Tag wählen')" class="mb-6 grid grid-cols-4 gap-2 sm:grid-cols-7">
      <Link v-for="day in days" :key="day.date" :href="url(day.date)" preserve-scroll :aria-current="day.date === date ? 'date' : undefined"
        class="rounded-lg border border-stone-300 px-2 py-3 text-center text-sm hover:bg-stone-100 aria-[current=date]:border-teal-800 aria-[current=date]:bg-teal-800 aria-[current=date]:text-white">
        <span class="block font-semibold">{{ day.label }}</span><span v-if="day.ordered" class="mt-1 block text-xs">{{ t('Bestellt') }}</span><span v-if="day.notificationDisabled" class="mt-1 block text-xs">{{ t('Erinnerung aus') }}</span>
      </Link>
    </nav>
    <form class="grid items-end gap-4 sm:grid-cols-[1fr_1fr_auto]" @submit.prevent="filter">
      <div><label for="meal-date">{{ t('Datum') }}</label><input id="meal-date" v-model="dateInput" type="date" required></div>
      <div><label for="provider">{{ t('Anbieter') }}</label><select id="provider" v-model="providerInput"><option value="">{{ t('Alle Anbieter') }}</option><option v-for="option in providers" :key="option.id" :value="option.id">{{ option.name }}</option></select></div>
      <Button type="submit">{{ t('Auswahl anzeigen') }}</Button>
    </form>
    <div class="mt-5 border-t border-stone-200 pt-4">
      <Button type="button" variant="outline" :disabled="pending" :aria-pressed="notificationDisabled" @click="toggleNotification">{{ pending ? t('Wird gespeichert …') : notificationDisabled ? t('Erinnerung für diesen Tag einschalten') : t('Erinnerung für diesen Tag ausschalten') }}</Button>
      <ActionFeedback :error="error" :success="success" :session-expired="sessionExpired" />
      <p class="mt-3 text-sm"><Link href="/profile/preferences" class="underline">{{ t('Benachrichtigungen und Vorlieben im Profil') }}</Link></p>
    </div>
  </section>
  <div v-if="meals.length" class="grid items-start gap-5 md:grid-cols-2 xl:grid-cols-3"><MealCard v-for="meal in meals" :key="`${date}-${meal.id}`" :meal="meal" /></div>
  <p v-else role="status" class="profile-card">{{ t('Für diese Auswahl sind keine Gerichte vorhanden.') }}</p>
</template>
