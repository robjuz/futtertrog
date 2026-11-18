<script setup lang="ts">
import { useProfileTranslations, useLocaleDate } from '@/lib/translations'
import { Head, Link } from '@inertiajs/vue3'
import MealCard from '@/components/ordering/MealCard.vue'
import type { MealCardData } from '@/lib/ordering'
const t = useProfileTranslations()
const formatDate = useLocaleDate()
defineProps<{ meal: MealCardData }>()
</script>
<template>
  <Head :title="meal.title" /><h1 class="page-title mb-5">{{ t('Gericht am :date', { date: formatDate(meal.date) }) }}</h1><Link :href="`/meals?date=${meal.date}`" class="mb-5 inline-block underline">{{ t('Alle Gerichte an diesem Tag') }}</Link><div class="max-w-xl"><MealCard :meal="meal" /></div>
</template>
