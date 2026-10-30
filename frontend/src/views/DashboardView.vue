<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { http, mensajeDeError } from '@/api/http'
import { useAuthStore } from '@/stores/auth'
import { formatoPesos } from '@/utils/formato'
import type { EstadoFactura, Pagina, Orden, Resumen } from '@/types'

const auth = useAuthStore()
const resumen = ref<Resumen | null>(null)
const ordenesAbiertas = ref<number | null>(null)
const cargando = ref(true)
const error = ref('')

const tarjetas: { estado: EstadoFactura; titulo: string; estilo: string }[] = [
  { estado: 'pendiente', titulo: 'Pendientes de revisión', estilo: 'border-amber-400 bg-amber-50 text-amber-900' },
  { estado: 'aprobada', titulo: 'Aprobadas', estilo: 'border-green-500 bg-green-50 text-green-900' },
  { estado: 'rechazada', titulo: 'Rechazadas', estilo: 'border-red-500 bg-red-50 text-red-900' },
]

const pendientes = computed(() => resumen.value?.facturas.pendiente.cantidad ?? 0)
const saludo = computed(() => auth.user?.razon_social ?? 'Administración')

async function cargar() {
  cargando.value = true
  error.value = ''
  try {
    // Las dos consultas en paralelo
    const [r, o] = await Promise.all([
      http.get<Resumen>('/dashboard'),
      http.get<Pagina<Orden>>('/ordenes', { params: { estado: 'abierta' } }),
    ])
    resumen.value = r.data
    ordenesAbiertas.value = o.data.total
  } catch (e) {
    error.value = mensajeDeError(e)
  } finally {
    cargando.value = false
  }
}

cargar()
</script>

<template>
  <section>
    <h1 class="text-xl font-semibold">Hola, {{ saludo }} 👋</h1>
    <p class="mb-6 text-sm text-slate-500">Este es el resumen de tus facturas y órdenes.</p>

    <!-- Cargando -->
    <div v-if="cargando" class="grid gap-4 sm:grid-cols-3" aria-busy="true">
      <div v-for="n in 3" :key="n" class="h-28 animate-pulse rounded-lg bg-slate-200" />
    </div>

    <!-- Error -->
    <div v-else-if="error" class="rounded-md bg-red-50 p-4 text-sm text-red-700" role="alert">
      {{ error }}
      <button class="ml-2 underline" @click="cargar">Reintentar</button>
    </div>

    <template v-else-if="resumen">
      <!-- Acción principal según el rol -->
      <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-lg bg-blue-700 p-5 text-white">
        <div>
          <template v-if="auth.isAdmin">
            <p class="text-lg font-semibold">
              {{ pendientes === 0 ? 'No hay facturas para revisar 🎉' : `Tenés ${pendientes} ${pendientes === 1 ? 'factura' : 'facturas'} para revisar` }}
            </p>
            <p class="text-sm text-blue-100">Aprobá o rechazá los comprobantes que cargaron los proveedores.</p>
          </template>
          <template v-else>
            <p class="text-lg font-semibold">
              {{ ordenesAbiertas ? `Tenés ${ordenesAbiertas} ${ordenesAbiertas === 1 ? 'orden abierta' : 'órdenes abiertas'}` : 'No tenés órdenes abiertas' }}
            </p>
            <p class="text-sm text-blue-100">Cargá tus facturas desde el detalle de cada orden de compra.</p>
          </template>
        </div>
        <RouterLink
          :to="auth.isAdmin ? { name: 'facturas', query: { estado: 'pendiente' } } : { name: 'ordenes' }"
          class="rounded-md bg-white px-4 py-2 text-sm font-medium text-blue-800 hover:bg-blue-50"
        >
          {{ auth.isAdmin ? 'Revisar facturas' : 'Ver mis órdenes' }} →
        </RouterLink>
      </div>

      <!-- Tarjetas por estado: cada una lleva al listado filtrado -->
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Facturas</h2>
      <div class="grid gap-4 sm:grid-cols-3">
        <RouterLink
          v-for="t in tarjetas"
          :key="t.estado"
          :to="{ name: 'facturas', query: { estado: t.estado } }"
          :class="[t.estilo, 'block rounded-lg border-l-4 p-5 transition hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500']"
        >
          <p class="text-sm font-medium">{{ t.titulo }}</p>
          <p class="mt-1 text-3xl font-bold tabular-nums">{{ resumen.facturas[t.estado].cantidad }}</p>
          <p class="mt-1 text-sm tabular-nums opacity-80">{{ formatoPesos(resumen.facturas[t.estado].importe) }}</p>
        </RouterLink>
      </div>
    </template>
  </section>
</template>
