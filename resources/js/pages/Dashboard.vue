<script setup lang="ts">
import { useProfileTranslations } from '@/lib/translations'
import { Head, Link } from '@inertiajs/vue3'
import OrderHistory from '@/components/ordering/OrderHistory.vue'
import DepositHistory from '@/components/ordering/DepositHistory.vue'
import type { Deposit, HistoryPage, OwnOrder } from '@/lib/ordering'
const t = useProfileTranslations()
defineProps<{ balance: string; todayOrders: OwnOrder[]; futureOrders: HistoryPage<OwnOrder>; ordersHistory: HistoryPage<OwnOrder>; deposits: HistoryPage<Deposit> }>()
</script>
<template>
  <Head :title="t('Übersicht')" />
  <p class="eyebrow">{{ t('Persönlicher Bereich') }}</p><h1 class="page-title mb-7">{{ t('Übersicht') }}</h1>
  <div class="grid items-start gap-5 md:grid-cols-2">
    <section class="profile-card"><h2 class="section-title">{{ t('Heute bestellt') }}</h2><OrderHistory :orders="{ items: todayOrders, previous: null, next: null, current: 1 }" :label="t('Heutige Bestellungen')" /><Link href="/meals" class="mt-4 inline-block font-medium underline">{{ t('Essen auswählen') }}</Link></section>
    <section class="profile-card"><h2 class="section-title">{{ t('Mein Guthaben') }}</h2><p class="my-5 text-3xl font-semibold text-teal-900">{{ balance }}</p><Link href="/my/balance" class="font-medium underline">{{ t('Guthaben und Verläufe öffnen') }}</Link></section>
    <section id="future-meals" class="profile-card"><h2 class="section-title">{{ t('Künftige Bestellungen') }}</h2><OrderHistory :orders="futureOrders" :label="t('Künftige Bestellungen')" /></section>
    <section id="deposit-history" class="profile-card"><h2 class="section-title">{{ t('Buchungsverlauf') }}</h2><DepositHistory :deposits="deposits" /></section>
    <section id="order-history" class="profile-card md:col-span-2"><h2 class="section-title">{{ t('Bestellverlauf') }}</h2><OrderHistory :orders="ordersHistory" :label="t('Bestellverlauf')" /></section>
  </div>
</template>
