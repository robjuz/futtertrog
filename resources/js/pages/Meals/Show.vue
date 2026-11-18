<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import MealCard from '@/components/ordering/MealCard.vue'
import type { MealCardData } from '@/lib/ordering'
defineProps<{ meal: MealCardData }>()
</script>
<template>
  <Head :title="meal.title" /><h1 class="page-title mb-5">Gericht am {{ meal.date }}</h1><Link :href="`/meals?date=${meal.date}`" class="mb-5 inline-block underline">Alle Gerichte an diesem Tag</Link><div class="max-w-xl"><MealCard :meal="meal" /></div>
</template>
