<script setup lang="ts">
import { useProfileTranslations } from '@/lib/translations'
import { Link, usePage } from '@inertiajs/vue3'
const t = useProfileTranslations()
const page = usePage<{
  navigation: { home: string; orders: string; balance: string; admin: string | null }
  flash: { success: string | null; status: string | null }
}>()
const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? ''
const tabs = [
  { href: '/profile', label: 'Konto' },
  { href: '/profile/security', label: 'Sicherheit' },
  { href: '/profile/preferences', label: 'Einstellungen' },
]
</script>

<template>
  <div class="min-h-screen bg-stone-50 text-stone-900">
    <a href="#profile-content" class="sr-only focus:not-sr-only focus:block focus:p-4">{{ t('Zum Inhalt') }}</a>
    <header class="border-b border-stone-200 bg-white">
      <nav :aria-label="t('Hauptnavigation')" class="mx-auto flex max-w-5xl flex-wrap items-center gap-x-6 gap-y-3 px-5 py-5">
        <a :href="page.props.navigation.home" class="font-semibold text-teal-900">Futtertrog</a>
        <a href="/meals">{{ t('Bestellen') }}</a>
        <a :href="page.props.navigation.orders">{{ t('Meine Bestellungen') }}</a>
        <a :href="page.props.navigation.balance">{{ t('Mein Guthaben') }}</a>
        <a v-if="page.props.navigation.admin" :href="page.props.navigation.admin">{{ t('Administration') }}</a>
        <form action="/logout" method="post">
          <input type="hidden" name="_token" :value="csrfToken">
          <button type="submit" class="text-sm underline">{{ t('Abmelden') }}</button>
        </form>
      </nav>
    </header>
    <main id="profile-content" class="mx-auto max-w-3xl px-5 py-10 sm:py-14">
      <p class="mb-2 text-sm font-semibold uppercase tracking-widest text-teal-800">{{ t('Persönlicher Bereich') }}</p>
      <h1 class="mb-7 text-3xl font-semibold tracking-tight">{{ t('Mein Profil') }}</h1>
      <nav :aria-label="t('Profilbereiche')" class="mb-8 flex flex-wrap gap-2 border-b border-stone-300 pb-4">
        <Link v-for="tab in tabs" :key="tab.href" :href="tab.href"
          :aria-current="page.url.split('?')[0] === tab.href ? 'page' : undefined"
          class="rounded-md px-4 py-2 text-sm font-medium hover:bg-stone-200 aria-[current=page]:bg-teal-800 aria-[current=page]:text-white">{{ t(tab.label) }}</Link>
      </nav>
      <p v-if="page.props.flash.success || page.props.flash.status" role="status" class="mb-6 rounded-md border border-teal-300 bg-teal-50 p-4 text-teal-950">
        {{ page.props.flash.success || page.props.flash.status }}
      </p>
      <slot />
    </main>
  </div>
</template>
