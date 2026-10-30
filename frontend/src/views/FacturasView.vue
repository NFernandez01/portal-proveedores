<script setup lang="ts">
import { ref, watch } from 'vue'
import { http, mensajeDeError } from '@/api/http'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { formatoComprobante, formatoFecha, formatoPesos } from '@/utils/formato'
import type { EstadoFactura, Factura } from '@/types'

const auth = useAuthStore()
const toast = useToastStore()
const filtro = ref<EstadoFactura | ''>('pendiente')
const facturas = ref<Factura[]>([])
const cargando = ref(false)
const error = ref('')

// Diálogo: qué factura y qué acción
const accion = ref<{ factura: Factura; estado: 'aprobada' | 'rechazada' } | null>(null)
const procesando = ref(false)

async function cargar() {
  cargando.value = true
  error.value = ''
  try {
    facturas.value = (await http.get<Factura[]>('/facturas', { params: { estado: filtro.value || undefined } })).data
  } catch (e) {
    error.value = mensajeDeError(e)
  } finally {
    cargando.value = false
  }
}

async function confirmar(motivo: string) {
  if (!accion.value) return
  procesando.value = true
  try {
    await http.patch(`/facturas/${accion.value.factura.id}/estado`, { estado: accion.value.estado, motivo })
    toast.mostrar(accion.value.estado === 'aprobada' ? 'Factura aprobada.' : 'Factura rechazada.')
    accion.value = null
    await cargar()
  } catch (e) {
    toast.mostrar(mensajeDeError(e), 'error')   // ej: "ya fue procesada" (409)
  } finally {
    procesando.value = false
  }
}

function descargar(f: Factura) {
  window.location.href = `/api/facturas/${f.id}/archivo`   // la cookie viaja sola
}

watch(filtro, cargar, { immediate: true })
</script>

<template>
  <section>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <h1 class="text-xl font-semibold">Facturas</h1>
      <label class="text-sm">
        <span class="mr-2 text-slate-600">Mostrar</span>
        <select v-model="filtro" class="rounded-md border border-slate-300 bg-white px-2 py-1.5">
          <option value="pendiente">Pendientes</option>
          <option value="aprobada">Aprobadas</option>
          <option value="rechazada">Rechazadas</option>
          <option value="">Todas</option>
        </select>
      </label>
    </div>

    <!-- Cargando -->
    <div v-if="cargando && facturas.length === 0" class="space-y-2" aria-busy="true">
      <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-md bg-slate-200" />
    </div>

    <!-- Error -->
    <div v-else-if="error" class="rounded-md bg-red-50 p-4 text-sm text-red-700" role="alert">
      {{ error }}
      <button class="ml-2 underline" @click="cargar">Reintentar</button>
    </div>

    <!-- Vacío -->
    <p v-else-if="facturas.length === 0" class="rounded-md bg-white p-8 text-center text-slate-500 ring-1 ring-slate-200">
      {{ filtro === 'pendiente' ? 'No hay facturas pendientes 🎉' : 'No hay facturas para mostrar.' }}
    </p>

    <template v-else>
      <!-- Escritorio: tabla -->
      <div class="hidden overflow-x-auto rounded-lg bg-white ring-1 ring-slate-200 md:block" :class="{ 'opacity-60': cargando }">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-left text-slate-600">
            <tr>
              <th v-if="auth.isAdmin" class="px-4 py-3">Proveedor</th>
              <th class="px-4 py-3">Orden</th>
              <th class="px-4 py-3">Comprobante</th>
              <th class="px-4 py-3">Fecha</th>
              <th class="px-4 py-3 text-right">Importe</th>
              <th class="px-4 py-3">Estado</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="f in facturas" :key="f.id" class="border-t border-slate-100 align-top">
              <td v-if="auth.isAdmin" class="px-4 py-3">{{ f.razon_social }}</td>
              <td class="px-4 py-3">{{ f.orden_numero }}</td>
              <td class="px-4 py-3 font-medium">{{ formatoComprobante(f) }}</td>
              <td class="px-4 py-3">{{ formatoFecha(f.fecha) }}</td>
              <td class="px-4 py-3 text-right tabular-nums">{{ formatoPesos(f.importe) }}</td>
              <td class="px-4 py-3">
                <StatusBadge :estado="f.estado" />
                <p v-if="f.motivo_rechazo" class="mt-1 max-w-xs text-xs text-red-700">{{ f.motivo_rechazo }}</p>
              </td>
              <td class="px-4 py-3">
                <div class="flex justify-end gap-2">
                  <button class="rounded border border-slate-300 px-2 py-1 text-xs hover:bg-slate-50" @click="descargar(f)">PDF</button>
                  <template v-if="auth.isAdmin && f.estado === 'pendiente'">
                    <button class="rounded bg-green-600 px-2 py-1 text-xs text-white hover:bg-green-700"
                      @click="accion = { factura: f, estado: 'aprobada' }">Aprobar</button>
                    <button class="rounded bg-red-600 px-2 py-1 text-xs text-white hover:bg-red-700"
                      @click="accion = { factura: f, estado: 'rechazada' }">Rechazar</button>
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile: tarjetas -->
      <ul class="space-y-3 md:hidden" :class="{ 'opacity-60': cargando }">
        <li v-for="f in facturas" :key="f.id" class="rounded-lg bg-white p-4 ring-1 ring-slate-200">
          <div class="flex items-start justify-between gap-2">
            <div>
              <p class="font-medium">{{ formatoComprobante(f) }}</p>
              <p class="text-xs text-slate-500">
                <span v-if="auth.isAdmin">{{ f.razon_social }} · </span>{{ f.orden_numero }} · {{ formatoFecha(f.fecha) }}
              </p>
            </div>
            <StatusBadge :estado="f.estado" />
          </div>
          <p class="mt-2 text-lg font-semibold tabular-nums">{{ formatoPesos(f.importe) }}</p>
          <p v-if="f.motivo_rechazo" class="mt-1 text-xs text-red-700">Motivo: {{ f.motivo_rechazo }}</p>
          <div class="mt-3 flex gap-2">
            <button class="flex-1 rounded border border-slate-300 py-2 text-sm" @click="descargar(f)">PDF</button>
            <template v-if="auth.isAdmin && f.estado === 'pendiente'">
              <button class="flex-1 rounded bg-green-600 py-2 text-sm text-white"
                @click="accion = { factura: f, estado: 'aprobada' }">Aprobar</button>
              <button class="flex-1 rounded bg-red-600 py-2 text-sm text-white"
                @click="accion = { factura: f, estado: 'rechazada' }">Rechazar</button>
            </template>
          </div>
        </li>
      </ul>
    </template>

    <ConfirmDialog
      :abierto="!!accion"
      :titulo="accion?.estado === 'aprobada' ? '¿Aprobar factura?' : '¿Rechazar factura?'"
      :mensaje="accion ? `${accion.factura.razon_social} · ${formatoComprobante(accion.factura)} · ${formatoPesos(accion.factura.importe)}` : ''"
      :peligro="accion?.estado === 'rechazada'"
      :pedir-motivo="accion?.estado === 'rechazada'"
      :texto-confirmar="accion?.estado === 'aprobada' ? 'Aprobar' : 'Rechazar'"
      :procesando="procesando"
      @confirmar="confirmar"
      @cancelar="accion = null"
    />
  </section>
</template>
