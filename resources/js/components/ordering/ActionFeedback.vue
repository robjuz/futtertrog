<script setup lang="ts">
import { useProfileTranslations } from '@/lib/translations'
const t = useProfileTranslations()
defineProps<{ error: string; success: string; sessionExpired?: boolean }>()
</script>
<template>
  <div aria-live="polite" aria-atomic="true">
    <p v-if="success" role="status" class="mt-3 text-sm font-medium text-teal-900">{{ success }}</p>
    <p v-if="error" role="alert" class="field-error">{{ error }}</p>
    <p v-if="sessionExpired" class="mt-3 text-sm">{{ t('Nach der Anmeldung diese Seite neu laden, um die Sitzung zu erneuern.') }}</p>
    <a v-if="sessionExpired" href="/login" target="_blank" rel="noopener" class="mt-3 inline-block font-medium underline">{{ t('Erneut anmelden (neuer Tab)') }}</a>
  </div>
</template>
