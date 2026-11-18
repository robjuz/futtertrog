<script setup lang="ts">
import { useProfileTranslations } from '@/lib/translations'
import { Head } from '@inertiajs/vue3'
import OrderHistory from '@/components/ordering/OrderHistory.vue'
import type { OwnOrder, HistoryPage } from '@/lib/ordering'
const t = useProfileTranslations()
defineProps<{ orders: HistoryPage<OwnOrder>; calendarUrl: string }>()
</script>
<template>
  <Head :title="t('Meine Bestellungen')" /><h1 class="page-title mb-7">{{ t('Meine Bestellungen') }}</h1>
  <section class="profile-card"><h2 class="section-title">{{ t('Aktuelle Bestellungen und Verlauf') }}</h2><a :href="calendarUrl" class="my-4 inline-block font-medium underline">{{ t('Als Kalender herunterladen (iCal)') }}</a><OrderHistory :orders="orders" :label="t('Eigene Bestellungen')" /></section>
</template>
