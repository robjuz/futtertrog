<script setup lang="ts">
import { useProfileTranslations } from '@/lib/translations'
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
const t = useProfileTranslations()
interface Settings {
  language: string | null
  mealPreferences: string | null
  mealAversion: string | null
  newOrderPossibilityNotification: boolean
  noOrderNotification: boolean
  noOrderForNextDayNotification: boolean
  noOrderForNextWeekNotification: boolean
  hideDashboardMealDescription: boolean
  hideOrderingMealDescription: boolean
  redirectToNextDay: boolean
}
const props = defineProps<{ settings: Settings; locales: string[] }>()
const form = useForm({ ...props.settings, language: props.settings.language ?? 'de' })
const toggles: { key: keyof Pick<Settings, 'newOrderPossibilityNotification' | 'noOrderNotification' | 'noOrderForNextDayNotification' | 'noOrderForNextWeekNotification' | 'hideDashboardMealDescription' | 'hideOrderingMealDescription' | 'redirectToNextDay'>; label: string }[] = [
  { key: 'newOrderPossibilityNotification', label: 'Über neue Bestellmöglichkeiten informieren' },
  { key: 'noOrderNotification', label: 'Erinnerung: keine Bestellung für heute' },
  { key: 'noOrderForNextDayNotification', label: 'Erinnerung: keine Bestellung für morgen' },
  { key: 'noOrderForNextWeekNotification', label: 'Erinnerung: keine Bestellung für nächste Woche' },
  { key: 'hideDashboardMealDescription', label: 'Gerichtbeschreibung auf der Übersicht ausblenden' },
  { key: 'hideOrderingMealDescription', label: 'Gerichtbeschreibung beim Bestellen ausblenden' },
  { key: 'redirectToNextDay', label: 'Zum nächsten Tag ohne Bestellung weiterleiten' },
]
</script>

<template>
  <Head :title="t('Einstellungen')" />
  <form class="profile-card space-y-7" @submit.prevent="form.patch('/profile/preferences', { preserveScroll: true })">
    <h2 class="text-xl font-semibold">{{ t('Persönliche Einstellungen') }}</h2>
    <div>
      <label for="language">{{ t('Sprache') }}</label>
      <select id="language" v-model="form.language"><option v-for="locale in locales" :key="locale" :value="locale">{{ locale === 'de' ? 'Deutsch' : locale === 'en' ? 'English' : locale }}</option></select>
    </div>
    <div>
      <label for="mealPreferences">{{ t('Vorlieben') }}</label>
      <p id="preferences-help" class="mb-2 text-sm text-stone-600">{{ t('Zutaten durch Komma trennen.') }}</p>
      <textarea id="mealPreferences" v-model="form.mealPreferences" rows="3" aria-describedby="preferences-help" />
    </div>
    <div>
      <label for="mealAversion">{{ t('Abneigungen') }}</label>
      <textarea id="mealAversion" v-model="form.mealAversion" rows="3" aria-describedby="preferences-help" />
    </div>
    <fieldset class="space-y-4"><legend class="mb-4 font-semibold">{{ t('Benachrichtigungen und Darstellung') }}</legend>
      <label v-for="toggle in toggles" :key="toggle.key" :for="toggle.key" class="flex items-start gap-3 font-normal">
        <input :id="toggle.key" v-model="form[toggle.key]" type="checkbox" class="mt-1 size-5 shrink-0 accent-teal-800">{{ t(toggle.label) }}
      </label>
    </fieldset>
    <ul v-if="Object.keys(form.errors).length" role="alert" class="field-error"><li v-for="(error, key) in form.errors" :key="key">{{ error }}</li></ul>
    <Button type="submit" :disabled="form.processing">{{ form.processing ? t('Wird gespeichert …') : t('Einstellungen speichern') }}</Button>
    <p class="text-sm"><a href="/settings" class="underline">{{ t('Bisherige Einstellungen und Kalenderexport öffnen') }}</a></p>
  </form>
</template>
