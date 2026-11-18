<script setup lang="ts">
import { useProfileTranslations } from '@/lib/translations'
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
const t = useProfileTranslations()

defineProps<{
  localCredentialStatus: 'legacy' | 'enabled' | 'disabled'
  identities: { provider: string }[]
  providers: { name: string; url: string }[]
}>()
const form = useForm({ current_password: '', password: '', password_confirmation: '' })
const confirmation = useForm({})
function changePassword() {
  form.put('/profile/password', { preserveScroll: true, onFinish: () => form.reset() })
}
</script>

<template>
  <Head :title="t('Sicherheit')" />
  <div class="space-y-6">
    <section class="profile-card">
      <h2 class="text-xl font-semibold">{{ t('Passwort') }}</h2>
      <template v-if="localCredentialStatus === 'enabled'">
        <p class="mt-2 text-stone-600">{{ t('Nach der Änderung bleiben Sie hier angemeldet. Andere Sitzungen werden abgemeldet.') }}</p>
        <form class="mt-6 space-y-5" @submit.prevent="changePassword">
          <div>
            <label for="current_password">{{ t('Aktuelles Passwort') }}</label>
            <input id="current_password" v-model="form.current_password" type="password" autocomplete="current-password" required :aria-invalid="!!form.errors.current_password" aria-describedby="current-error">
            <p id="current-error" role="alert" class="field-error">{{ form.errors.current_password }}</p>
          </div>
          <div>
            <label for="password">{{ t('Neues Passwort (mindestens 12 Zeichen)') }}</label>
            <input id="password" v-model="form.password" type="password" autocomplete="new-password" minlength="12" required :aria-invalid="!!form.errors.password" aria-describedby="password-error">
            <p id="password-error" role="alert" class="field-error">{{ form.errors.password }}</p>
          </div>
          <div>
            <label for="password_confirmation">{{ t('Neues Passwort bestätigen') }}</label>
            <input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" minlength="12" required>
          </div>
          <Button type="submit" :disabled="form.processing">{{ form.processing ? t('Wird geändert …') : t('Passwort ändern') }}</Button>
        </form>
      </template>
      <template v-else>
        <p class="mt-3 text-stone-700">{{ localCredentialStatus === 'legacy' ? t('Ihre bisherige Passwortanmeldung muss noch bestätigt werden.') : t('Für dieses Konto ist noch kein lokales Passwort eingerichtet.') }}</p>
        <p class="mt-2 text-stone-600">{{ t('Fordern Sie einen einmaligen Link an Ihre gespeicherte E-Mail-Adresse an und setzen Sie anschließend ein neues Passwort. Ohne Zugriff auf die Adresse hilft der bestehende Support.') }}</p>
        <form class="mt-5" @submit.prevent="confirmation.post('/profile/password/confirmation', { preserveScroll: true })">
          <Button type="submit" :disabled="confirmation.processing">{{ t('Bestätigungslink anfordern') }}</Button>
        </form>
      </template>
    </section>
    <section class="profile-card">
      <h2 class="text-xl font-semibold">{{ t('Externe Anmeldemethoden') }}</h2>
      <ul v-if="identities.length" class="mt-4 space-y-2"><li v-for="(identity, index) in identities" :key="index" class="capitalize">{{ identity.provider }} · {{ t('verknüpft') }}</li></ul>
      <p v-else class="mt-3 text-stone-600">{{ t('Noch keine externe Anmeldemethode verknüpft.') }}</p>
      <p class="mt-4 text-sm text-stone-600">{{ t('Eine neue Verknüpfung benötigt zusätzlich eine frische Bestätigung per E-Mail.') }}</p>
      <div class="mt-4 flex flex-wrap gap-3">
        <Button v-for="provider in providers" :key="provider.name" as="a" :href="provider.url" variant="outline">{{ provider.name }} {{ t('verknüpfen') }}</Button>
      </div>
    </section>
  </div>
</template>
