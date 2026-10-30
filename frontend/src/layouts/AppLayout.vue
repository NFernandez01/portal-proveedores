<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const menuAbierto = ref(false)

// "exacto": Inicio es la ruta "/" y, sin coincidencia exacta, quedaría activo en TODAS las páginas
const links = [
  { to: { name: 'dashboard' }, texto: 'Inicio', exacto: true },
  { to: { name: 'ordenes' }, texto: 'Órdenes de compra', exacto: false },
  { to: { name: 'facturas' }, texto: 'Facturas', exacto: false },
]
const claseActiva = 'bg-blue-100 font-medium text-blue-800'

async function salir() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-800">
    <header class="sticky top-0 z-10 border-b border-slate-200 bg-white">
      <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
        <div class="flex items-center gap-3">
          <button
            class="rounded p-2 hover:bg-slate-100 md:hidden"
            :aria-expanded="menuAbierto"
            aria-label="Abrir menú"
            @click="menuAbierto = !menuAbierto"
          >☰</button>
          <span class="font-semibold text-blue-800">Portal de Proveedores</span>
        </div>
        <div class="flex items-center gap-4 text-sm">
          <span class="hidden text-right sm:block">
            <span class="block font-medium">{{ auth.user?.razon_social ?? 'Administración' }}</span>
            <span class="block text-xs text-slate-500">{{ auth.user?.email }}</span>
          </span>
          <button class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-100" @click="salir">
            Salir
          </button>
        </div>
      </div>
    </header>

    <div class="mx-auto flex max-w-6xl gap-6 px-4 py-6">
      <nav
        :class="[menuAbierto ? 'block' : 'hidden', 'w-full md:block md:w-52 md:shrink-0']"
        aria-label="Navegación principal"
      >
        <ul class="space-y-1">
          <li v-for="link in links" :key="link.texto">
            <RouterLink
              :to="link.to"
              class="block rounded-md px-3 py-2 hover:bg-blue-50"
              :active-class="link.exacto ? '' : claseActiva"
              :exact-active-class="claseActiva"
              @click="menuAbierto = false"
            >{{ link.texto }}</RouterLink>
          </li>
        </ul>
      </nav>

      <main class="min-w-0 flex-1">
        <RouterView />
      </main>
    </div>
  </div>
</template>