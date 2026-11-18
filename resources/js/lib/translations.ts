import { usePage } from '@inertiajs/vue3'

export function useProfileTranslations() {
  const page = usePage<{ translations: Record<string, string> }>()
  return (text: string, replacements: Record<string, string | number> = {}): string => {
    let translated = page.props.translations[text] ?? text
    for (const [key, value] of Object.entries(replacements)) translated = translated.replaceAll(`:${key}`, String(value))
    return translated
  }
}

export function useLocaleDate() {
  const page = usePage<{ locale: string }>()
  return (date: string): string => new Intl.DateTimeFormat(page.props.locale, { dateStyle: 'medium' })
    .format(new Date(`${date.slice(0, 10)}T12:00:00`))
}
