import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
  plugins: [
    vue(),
    vueDevTools(),

    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['favicon.ico'],

      manifest: {
        id: '/',
        name: 'LeanOn Bot',
        short_name: 'LeanOn Bot',
        description: 'AI-powered student mental wellness platform',
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