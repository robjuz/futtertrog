<script setup lang="ts">
import { useProfileTranslations } from '@/lib/translations'
import { Link } from '@inertiajs/vue3'
const t = useProfileTranslations()
defineProps<{ previous: string | null; next: string | null; current: number; label: string }>()
</script>
<template>
  <nav v-if="previous || next" :aria-label="label" class="mt-5 flex flex-wrap items-center gap-4 text-sm">
    <Link v-if="previous" :href="previous" preserve-scroll class="rounded-md border border-stone-400 px-4 py-3">{{ t('Zurück') }}</Link>
    <span>{{ t('Seite :page', { page: current }) }}</span>
    <Link v-if="next" :href="next" preserve-scroll class="rounded-md border border-stone-400 px-4 py-3">{{ t('Weiter') }}</Link>
  </nav>
</template>
