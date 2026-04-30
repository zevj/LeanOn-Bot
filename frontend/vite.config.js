import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import { VitePWA } from 'vite-plugin-pwa' // ✅ ADD THIS

export default defineConfig({
  plugins: [
    vue(),
    vueDevTools(),

    // ✅ ADD THIS BLOCK
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['favicon.ico'],

      manifest: {
        id: '/', // optional but removes warning
        name: 'My Vue App',
        short_name: 'VueApp',
        description: 'My Vue PWA App',
        start_url: '/',
        display: 'standalone',
        background_color: '#ffffff',
        theme_color: '#ffffff',

        icons: [
          {
            src: '/leanOnBot-pwa-192x192.png',
            sizes: '192x192',
            type: 'image/png'
          },
          {
            src: '/leanOnBot-pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png'
          }
        ]
      }
    })
  ],

  server: {
    host: 'localhost',
    port: 5173
  },

  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
})