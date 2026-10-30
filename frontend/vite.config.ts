import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [vue(), tailwindcss()],
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
  },
  server: {
    // En desarrollo, todo lo que empiece con /api se reenvia al backend de Docker
    proxy: { '/api': 'http://127.0.0.1:8080' },   // 127.0.0.1 y no localhost: evita el problema de IPv6 en Windows
  },
})