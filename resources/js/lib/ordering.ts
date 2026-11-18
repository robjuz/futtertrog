import { ref } from 'vue'
import { useProfileTranslations } from './translations'

export interface OwnOrder {
  id: number; meal_id: number; title: string; date: string; quantity: number
  description: string | null; price: string; subtotal: string; status: string; canChange: boolean; updateUrl: string; deleteUrl: string
}
export interface MealCardData {
  id: number; title: string; date: string; provider: string; description: string | null; image: string | null
  preferred: boolean; avoided: boolean; info: { calories: number | null; allergens: string[]; tags: string[]; deposit: string | null }
  choices: { id: number; title: string; price: string }[]; orders: OwnOrder[]; canOrder: boolean; storeUrl: string
}
export interface HistoryPage<T> { items: T[]; previous: string | null; next: string | null; current: number }
export interface Deposit { id: number; value: string; comment: string; date: string }

/** A response, never a queued request or an optimistic state, confirms a change. */
export function useConfirmedAction() {
  const t = useProfileTranslations()
  const pending = ref(false)
  const error = ref('')
  const success = ref('')
  const sessionExpired = ref(false)
  async function execute<T>(url: string, method: string, data?: Record<string, unknown>): Promise<{ data: T | null } | null> {
    if (pending.value) return null
    error.value = ''; success.value = ''; sessionExpired.value = false
    if (!navigator.onLine) {
      error.value = t('Sie sind offline. Es wurde nichts gesendet. Bitte stellen Sie die Verbindung wieder her.')
      return null
    }
    pending.value = true
    const controller = new AbortController()
    const timeout = window.setTimeout(() => controller.abort(), 20000)
    try {
      const response = await fetch(url, {
        method, credentials: 'same-origin', redirect: 'error', signal: controller.signal,
        headers: {
          Accept: 'application/json', 'Content-Type': 'application/json',
          'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('; ').find(cookie => cookie.startsWith('XSRF-TOKEN='))?.slice('XSRF-TOKEN='.length) ?? ''),
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: data ? JSON.stringify(data) : undefined,
      })
      if ([401, 419].includes(response.status)) {
        sessionExpired.value = true
        throw new Error(t('Ihre Sitzung ist abgelaufen. Bitte melden Sie sich erneut an; Ihre Auswahl bleibt hier erhalten.'))
      }
      if (response.status === 403) throw new Error(t('Die Bestellfrist ist abgelaufen oder diese Änderung ist nicht erlaubt. Bitte prüfen Sie die aktuelle Übersicht.'))
      if (response.status === 422) {
        const body = await response.json()
        throw new Error(Object.values(body.errors ?? {}).flat().join(' ') || t('Bitte prüfen Sie Ihre Auswahl und Menge.'))
      }
      if (!response.ok) throw new Error(t('Die Änderung wurde nicht bestätigt. Bitte prüfen Sie die Übersicht und versuchen Sie es erneut.'))
      const result = response.status === 204 ? null : await response.json()
      return { data: result as T | null }
    } catch (failure) {
      error.value = failure instanceof TypeError || (failure instanceof DOMException && failure.name === 'AbortError')
        ? t('Keine Serverbestätigung erhalten. Prüfen Sie die Übersicht vor einer Wiederholung. Ihre Auswahl bleibt erhalten.')
        : failure instanceof Error ? failure.message : t('Keine Serverbestätigung erhalten. Bitte erneut versuchen.')
      return null
    } finally {
      window.clearTimeout(timeout)
      pending.value = false
    }
  }
  return { pending, error, success, sessionExpired, execute }
}
