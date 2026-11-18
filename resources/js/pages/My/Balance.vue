<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import OrderHistory from '@/components/ordering/OrderHistory.vue'
import DepositHistory from '@/components/ordering/DepositHistory.vue'
import type { Deposit, HistoryPage, OwnOrder } from '@/lib/ordering'
defineProps<{ balance: string; deposits: HistoryPage<Deposit>; orders: HistoryPage<OwnOrder> }>()
</script>
<template>
  <Head title="Mein Guthaben" /><h1 class="page-title mb-7">Mein Guthaben</h1>
  <section class="profile-card mb-5"><h2 class="section-title">Aktueller Saldo</h2><p class="mt-5 text-3xl font-semibold text-teal-900">{{ balance }}</p></section>
  <div class="grid items-start gap-5 md:grid-cols-2"><section class="profile-card"><h2 class="section-title">Buchungsverlauf</h2><DepositHistory :deposits="deposits" /></section><section class="profile-card"><h2 class="section-title">Bestellverlauf</h2><OrderHistory :orders="orders" label="Bestellverlauf" /></section></div>
</template>
