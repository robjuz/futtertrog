import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [
    // Package 3 adds resources/css/admin.css as its own panel input.
    laravel({ input: ['resources/js/app.ts'], refresh: true }),
    vue(),
    tailwindcss(),
  ],
  resolve: { alias: { '@': fileURLToPath(new URL('./resources/js', import.meta.url)) } },
})
