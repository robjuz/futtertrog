import { usePage } from '@inertiajs/vue3'

export function useProfileTranslations() {
  const page = usePage<{ translations: Record<string, string> }>()
  return (text: string): string => page.props.translations[text] ?? text
}
