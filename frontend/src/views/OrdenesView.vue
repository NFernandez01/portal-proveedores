<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { http, mensajeDeError } from '@/api/http'
import { formatoFecha, formatoPesos } from '@/utils/formato'
import { useAuthStore } from '@/stores/auth'
import type { EstadoOrden, Orden, Pagina } from '@/types'

const auth = useAuthStore()
const estado = ref<EstadoOrden | ''>('')
const pagina = ref(1)
const datos = ref<Pagina<Orden> | null>(null)
const cargando = ref(false)
const error = ref('')

const totalPaginas = computed(() => (datos.value ? Math.max(1, Math.ceil(datos.value.total / datos.value.porPagina)) : 1))

async function cargar() {
  cargando.value = true
  error.value = ''
  try {
    const { data } = await http.get<Pagina<Orden>>('/ordenes', {
      params: { pagina: pagina.value, estado: estado.value || undefined },
    })
    datos.value = data
  } catch (e) {
    error.value = mensajeDeError(e)
  } finally {
    cargando.value = false
  }
}

// Al cambiar el filtro volvemos a la página 1; al cambiar la página, recargamos
watch(estado, () => { pagina.value = 1; cargar() })
watch(pagina, cargar)
cargar()
</script>

<template>
  <section>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <h1 class="text-xl font-semibold">Órdenes de compra</h1>
      <label class="text-sm">
        <span class="mr-2 text-slate-600">Estado</span>
        <select v-model="estado" class="rounded-md border border-slate-300 bg-white px-2 py-1.5">
          <option value="">Todos</option>
          <option value="abierta">Abierta</option>
          <option value="facturada">Facturada</option>
          <option value="cerrada">Cerrada</option>
        </select>
      </label>
    </div>

    <!-- Cargando: skeleton -->
    <div v-if="cargando && !datos" class="space-y-2" aria-busy="true">
      <div v-for="n in 5" :key="n" class="h-12 animate-pulse rounded-md bg-slate-200" />
    </div>

    <!-- Error -->
    <div v-else-if="error" class="rounded-md bg-red-50 p-4 text-sm text-red-700" role="alert">
      {{ error }}
      <button class="ml-2 underline" @click="cargar">Reintentar</button>
    </div>

    <!-- Vacío -->
    <p v-else-if="datos && datos.items.length === 0" class="rounded-md bg-white p-8 text-center text-slate-500 ring-1 ring-slate-200">
      No hay órdenes {{ estado ? `en estado "${estado}"` : '' }}.
    </p>

    <!-- Datos -->
    <div v-else-if="datos" class="overflow-x-auto rounded-lg bg-white ring-1 ring-slate-200" :class="{ 'opacity-60': cargando }">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-600">
          <tr>
            <th class="px-4 py-3">Orden</th>
            <th v-if="auth.isAdmin" class="px-4 py-3">Proveedor</th>
            <th class="px-4 py-3">Fecha</th>
            <th class="px-4 py-3 text-right">Total</th>
            <th class="px-4 py-3 text-right">Saldo</th>
            <th class="px-4 py-3">Estado</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="o in datos.items" :key="o.id" class="border-t border-slate-100 hover:bg-slate-50">
            <td class="px-4 py-3">
              <RouterLink :to="{ name: 'orden', params: { id: o.id } }" class="font-medium text-blue-700 hover:underline">
                {{ o.numero }}
              </RouterLink>
            </td>
            <td v-if="auth.isAdmin" class="px-4 py-3">{{ o.razon_social }}</td>
            <td class="px-4 py-3">{{ formatoFecha(o.fecha) }}</td>
            <td class="px-4 py-3 text-right tabular-nums">{{ formatoPesos(o.total) }}</td>
            <td class="px-4 py-3 text-right tabular-nums">{{ formatoPesos(o.saldo) }}</td>
            <td class="px-4 py-3 capitalize">{{ o.estado }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Paginación -->
    <nav v-if="datos && totalPaginas > 1" class="mt-4 flex items-center justify-end gap-2 text-sm" aria-label="Paginación">
      <button class="rounded border px-3 py-1 disabled:opacity-40" :disabled="pagina === 1" @click="pagina--">Anterior</button>
      <span>Página {{ pagina }} de {{ totalPaginas }}</span>
      <button class="rounded border px-3 py-1 disabled:opacity-40" :disabled="pagina === totalPaginas" @click="pagina++">Siguiente</button>
    </nav>
  </section>
</template>