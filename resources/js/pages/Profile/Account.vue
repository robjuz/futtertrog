<script setup lang="ts">
import { useProfileTranslations } from '@/lib/translations'
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
const t = useProfileTranslations()
const props = defineProps<{ account: { name: string; email: string } }>()
const form = useForm({ name: props.account.name })
</script>

<template>
  <Head :title="t('Konto')" />
  <section class="profile-card">
    <h2 class="text-xl font-semibold">{{ t('Kontodaten') }}</h2>
    <p class="mt-2 text-stone-600">{{ t('So erscheint Ihr Name in Futtertrog.') }}</p>
    <form class="mt-6 space-y-5" @submit.prevent="form.patch('/profile', { preserveScroll: true })">
      <div>
        <label for="name">{{ t('Name') }}</label>
        <input id="name" v-model="form.name" name="name" autocomplete="name" maxlength="255" required :aria-invalid="!!form.errors.name" aria-describedby="name-error">
        <p id="name-error" role="alert" class="field-error">{{ form.errors.name }}</p>
      </div>
      <div>
        <p class="font-medium">{{ t('E-Mail-Adresse') }}</p>
        <p class="mt-1 break-words">{{ account.email }}</p>
        <p class="mt-1 text-sm text-stone-600">{{ t('Die E-Mail-Adresse kann hier nicht geändert werden.') }}</p>
      </div>
      <Button type="submit" :disabled="form.processing">{{ form.processing ? t('Wird gespeichert …') : t('Namen speichern') }}</Button>
    </form>
  </section>
</template>
