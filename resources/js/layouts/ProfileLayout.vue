<script setup lang="ts">
import { useProfileTranslations } from '@/lib/translations'
import { Link, usePage } from '@inertiajs/vue3'
const t = useProfileTranslations()
const page = usePage()
const tabs = [
  { href: '/profile', label: 'Konto' },
  { href: '/profile/security', label: 'Sicherheit' },
  { href: '/profile/preferences', label: 'Einstellungen' },
]
</script>
<template>
  <div id="profile-content" class="mx-auto max-w-3xl">
    <p class="mb-2 text-sm font-semibold uppercase tracking-widest text-teal-800">{{ t('Persönlicher Bereich') }}</p>
    <h1 class="mb-7 text-3xl font-semibold tracking-tight">{{ t('Mein Profil') }}</h1>
    <nav :aria-label="t('Profilbereiche')" class="mb-8 flex flex-wrap gap-2 border-b border-stone-300 pb-4">
      <Link v-for="tab in tabs" :key="tab.href" :href="tab.href" :aria-current="page.url.split('?')[0] === tab.href ? 'page' : undefined"
        class="rounded-md px-4 py-3 text-sm font-medium hover:bg-stone-200 aria-[current=page]:bg-teal-800 aria-[current=page]:text-white">{{ t(tab.label) }}</Link>
    </nav>
    <slot />
  </div>
</template>
