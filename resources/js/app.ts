import '../css/app.css'
import { createApp, h, type DefineComponent, type Component } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import ProfileLayout from './layouts/ProfileLayout.vue'

createInertiaApp({
  title: title => `${title} · Futtertrog`,
  resolve: async name => {
    const pages = import.meta.glob<{ default: DefineComponent & { layout?: Component } }>('./pages/**/*.vue')
    const page = await resolvePageComponent(`./pages/${name}.vue`, pages)
    page.default.layout = ProfileLayout
    return page.default
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) }).use(plugin).mount(el)
  },
})
