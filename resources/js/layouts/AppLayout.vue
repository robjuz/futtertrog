<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
const page = usePage<{
  navigation: { home: string; meals: string; orders: string; balance: string; profile: string; admin: string | null; inertia: boolean }
  flash: { success: string | null; status: string | null }
}>()
const offline = ref(!navigator.onLine)
const navigating = ref(false)
const error = ref('')
const sessionExpired = ref(false)
const updated = ref(false)
const main = ref<HTMLElement>()
const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? ''
const updateConnection = () => { offline.value = !navigator.onLine }
const workerUpdate = () => { updated.value = true }
const removeListeners: (() => void)[] = []
onMounted(() => {
  window.addEventListener('online', updateConnection)
  window.addEventListener('offline', updateConnection)
  window.addEventListener('futtertrog:update', workerUpdate)
  removeListeners.push(
    router.on('before', () => {
      error.value = ''; sessionExpired.value = false
      if (offline.value) { error.value = 'Offline: Diese Seite kann gerade nicht geladen werden. Bitte versuchen Sie es nach Wiederherstellung der Verbindung erneut.'; return false }
    }),
    router.on('start', () => { navigating.value = true }),
    router.on('finish', () => { navigating.value = false }),
    router.on('networkError', event => {
      event.preventDefault(); error.value = 'Die Seite wurde nicht geladen. Prüfen Sie Ihre Verbindung und versuchen Sie es erneut.'
    }),
    router.on('httpException', event => {
      event.preventDefault()
      const response = event.detail.response
      const loginForm = response.status === 200 && typeof response.data === 'string'
        && new DOMParser().parseFromString(response.data, 'text/html').querySelector('form[action$="/login"]')
      sessionExpired.value = [401, 419].includes(response.status) || !!loginForm
      error.value = sessionExpired.value ? 'Ihre Sitzung ist abgelaufen. Bitte melden Sie sich erneut an.' : 'Die Seite konnte nicht geladen werden. Bitte versuchen Sie es erneut.'
    }),
    router.on('navigate', async event => {
      if (event.detail.type === 'visit') { await nextTick(); main.value?.focus({ preventScroll: true }) }
    }),
  )
})
onUnmounted(() => {
  removeListeners.forEach(remove => remove())
  window.removeEventListener('online', updateConnection)
  window.removeEventListener('offline', updateConnection)
  window.removeEventListener('futtertrog:update', workerUpdate)
})
const reload = () => window.location.reload()
const navClass = 'rounded-md px-2 py-2 text-sm font-medium hover:bg-stone-100 aria-[current=page]:bg-teal-800 aria-[current=page]:text-white'
</script>
<template>
  <div class="min-h-screen bg-stone-50 text-stone-900">
    <a href="#app-content" class="sr-only focus:not-sr-only focus:block focus:p-4">Zum Inhalt</a>
    <header class="sticky top-0 z-10 border-b border-stone-200 bg-white">
      <nav aria-label="Hauptnavigation" class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-3 gap-y-2 px-4 py-4 sm:px-6">
        <component :is="page.props.navigation.inertia ? Link : 'a'" :href="page.props.navigation.home" class="mr-3 text-lg font-semibold text-teal-900">Futtertrog</component>
        <component :is="page.props.navigation.inertia ? Link : 'a'" v-for="item in [
          { href: page.props.navigation.home, label: 'Übersicht' }, { href: page.props.navigation.meals, label: 'Bestellen' },
          { href: page.props.navigation.orders, label: 'Meine Bestellungen' }, { href: page.props.navigation.balance, label: 'Mein Guthaben' },
        ]" :key="item.href" :href="item.href" :class="navClass" :aria-current="page.url.split('?')[0] === item.href ? 'page' : undefined">{{ item.label }}</component>
        <Link :href="page.props.navigation.profile" :class="navClass" :aria-current="page.url.startsWith('/profile') ? 'page' : undefined">Mein Profil</Link>
        <a v-if="page.props.navigation.admin" :href="page.props.navigation.admin" :class="navClass">Administration</a>
        <form action="/logout" method="post" class="sm:ml-auto">
          <input type="hidden" name="_token" :value="csrfToken"><button type="submit" :class="navClass">Abmelden</button>
        </form>
      </nav>
    </header>
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
      <p v-if="offline" role="alert" class="mt-4 rounded-lg border border-amber-600 bg-amber-50 p-4 text-amber-950">Sie sind offline. Bestellungen werden nicht gesendet oder vorgemerkt. Angezeigte Daten können veraltet sein.</p>
      <p v-if="updated" role="status" class="mt-4 rounded-lg border border-teal-500 bg-teal-50 p-4">Eine neue Version ist verfügbar. <button type="button" class="font-semibold underline" @click="reload">Seite neu laden</button></p>
      <div aria-live="polite" aria-atomic="true" class="text-sm">
        <p v-if="navigating" role="status" class="fixed bottom-4 left-4 z-20 rounded-lg border border-teal-400 bg-white px-4 py-3 text-teal-950 shadow-sm">Seite wird geladen …</p>
        <p v-if="error" role="alert" class="field-error">{{ error }} <a v-if="sessionExpired" href="/login" target="_blank" rel="noopener" class="underline">Erneut anmelden (neuer Tab)</a></p>
      </div>
    </div>
    <main id="app-content" ref="main" tabindex="-1" class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
      <p v-if="page.props.flash.success || page.props.flash.status" role="status" class="mb-6 rounded-lg border border-teal-300 bg-teal-50 p-4 text-teal-950">{{ page.props.flash.success || page.props.flash.status }}</p>
      <slot />
    </main>
  </div>
</template>
