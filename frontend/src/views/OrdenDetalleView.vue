<script setup lang="ts">
import { ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { http, mensajeDeError } from '@/api/http'
import { useAuthStore } from '@/stores/auth'
import FacturaForm from '@/components/FacturaForm.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { formatoComprobante, formatoFecha, formatoPesos } from '@/utils/formato'
import type { Orden } from '@/types'

const props = defineProps<{ id: string }>()   // viene de la ruta gracias a props: true
const auth = useAuthStore()
const orden = ref<Orden | null>(null)
const cargando = ref(true)
const error = ref('')

async function cargar() {
  cargando.value = true
  error.value = ''
  try {
    orden.value = (await http.get<Orden>(`/ordenes/${props.id}`)).data
  } catch (e) {
    error.value = mensajeDeError(e)   // si es de otro proveedor: "La orden no existe."
  } finally {
    cargando.value = false
  }
}

watch(() => props.id, cargar, { immediate: true })
</script>

<template>
  <section>
    <RouterLink :to="{ name: 'ordenes' }" class="mb-4 inline-block text-sm text-blue-700 hover:underline">
      ← Volver a órdenes
    </RouterLink>

    <!-- Cargando (solo la primera vez; al recargar se mantiene lo que había) -->
    <div v-if="cargando && !orden" class="space-y-3" aria-busy="true">
      <div class="h-28 animate-pulse rounded-lg bg-slate-200" />
      <div class="h-48 animate-pulse rounded-lg bg-slate-200" />
    </div>

    <!-- Error (incluye "La orden no existe." si es de otro proveedor) -->
    <div v-else-if="error" class="rounded-md bg-red-50 p-4 text-sm text-red-700" role="alert">
      {{ error }}
      <button class="ml-2 underline" @click="cargar">Reintentar</button>
    </div>

    <template v-else-if="orden">
      <!-- Cabecera -->
      <div class="mb-6 rounded-lg bg-white p-5 ring-1 ring-slate-200">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h1 class="text-xl font-semibold">Orden {{ orden.numero }}</h1>
            <p class="text-sm text-slate-500">{{ orden.razon_social }} · {{ formatoFecha(orden.fecha) }}</p>
          </div>
          <StatusBadge :estado="orden.estado" />
        </div>
        <dl class="mt-4 grid grid-cols-2 gap-4 sm:max-w-md">
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Total</dt>
            <dd class="text-lg tabular-nums">{{ formatoPesos(orden.total) }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Saldo pendiente</dt>
            <dd class="text-lg font-semibold tabular-nums text-blue-800">{{ formatoPesos(orden.saldo) }}</dd>
          </div>
        </dl>
      </div>

      <!-- Facturas de la orden -->
      <h2 class="mb-3 text-lg font-semibold">Facturas</h2>

      <p v-if="!orden.facturas?.length" class="mb-6 rounded-md bg-white p-6 text-center text-sm text-slate-500 ring-1 ring-slate-200">
        Todavía no hay facturas para esta orden.
      </p>

      <div v-else class="mb-6 overflow-x-auto rounded-lg bg-white ring-1 ring-slate-200">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-left text-slate-600">
            <tr>
              <th class="px-4 py-3">Comprobante</th>
              <th class="px-4 py-3">Fecha</th>
              <th class="px-4 py-3 text-right">Importe</th>
              <th class="px-4 py-3">Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="f in orden.facturas" :key="f.id" class="border-t border-slate-100 align-top">
              <td class="px-4 py-3 font-medium">{{ formatoComprobante(f) }}</td>
              <td class="px-4 py-3">{{ formatoFecha(f.fecha) }}</td>
              <td class="px-4 py-3 text-right tabular-nums">{{ formatoPesos(f.importe) }}</td>
              <td class="px-4 py-3">
                <StatusBadge :estado="f.estado" />
                <p v-if="f.motivo_rechazo" class="mt-1 text-xs text-red-700">Motivo: {{ f.motivo_rechazo }}</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Formulario: solo proveedores y solo si queda saldo -->
      <div v-if="!auth.isAdmin && Number(orden.saldo) > 0" class="rounded-lg bg-white p-5 ring-1 ring-slate-200">
        <h2 class="mb-4 text-lg font-semibold">Cargar factura</h2>
        <FacturaForm :orden-id="orden.id" :saldo="orden.saldo" @cargada="cargar" />
      </div>
      <p v-else-if="!auth.isAdmin" class="rounded-md bg-green-50 p-4 text-sm text-green-800">
        Esta orden ya está facturada por completo.
      </p>
    </template>
  </section>
</template>
