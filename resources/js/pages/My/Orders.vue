<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import OrderHistory from '@/components/ordering/OrderHistory.vue'
import type { OwnOrder, HistoryPage } from '@/lib/ordering'
defineProps<{ orders: HistoryPage<OwnOrder>; calendarUrl: string }>()
</script>
<template>
  <Head title="Meine Bestellungen" /><h1 class="page-title mb-7">Meine Bestellungen</h1>
  <section class="profile-card"><h2 class="section-title">Aktuelle Bestellungen und Verlauf</h2><a :href="calendarUrl" class="my-4 inline-block font-medium underline">Als Kalender herunterladen (iCal)</a><OrderHistory :orders="orders" label="Eigene Bestellungen" /></section>
</template>
