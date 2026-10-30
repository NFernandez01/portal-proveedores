<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { erroresPorCampo, http, mensajeDeError } from '@/api/http'
import { formatoPesos } from '@/utils/formato'
import { useToastStore } from '@/stores/toast'

const props = defineProps<{ ordenId: number; saldo: string }>()
const emit = defineEmits<{ cargada: [] }>()
const toast = useToastStore()

// Fecha local en formato YYYY-MM-DD. toISOString() usa UTC y después de las 21 h en Argentina daría "mañana".
const hoy = new Date().toLocaleDateString('en-CA')
const form = reactive({ tipo: 'A', punto_venta: '', numero: '', fecha: hoy, importe: '' })
const archivo = ref<File | null>(null)
const errores = ref<Record<string, string>>({})
const enviando = ref(false)
const inputArchivo = ref<HTMLInputElement | null>(null)

// El usuario puede escribir "1500,50" (formato argentino) o "1500.50"; internamente usamos punto
const importeNormalizado = computed(() => form.importe.replace(',', '.'))
const importeNumerico = computed(() => Number(importeNormalizado.value))

const tamanioArchivo = computed(() => (archivo.value ? (archivo.value.size / 1024 / 1024).toFixed(2) + ' MB' : ''))

function elegirArchivo(e: Event) {
  archivo.value = (e.target as HTMLInputElement).files?.[0] ?? null
}

/** Validación rápida en el front (el backend vuelve a validar todo) */
function validar(): boolean {
  const e: Record<string, string> = {}
  if (!/^\d{1,5}$/.test(form.punto_venta) || Number(form.punto_venta) < 1) e.punto_venta = 'Entre 1 y 99999.'
  if (!/^\d{1,8}$/.test(form.numero) || Number(form.numero) < 1) e.numero = 'Entre 1 y 99999999.'
  if (!/^\d+([.,]\d{1,2})?$/.test(form.importe) || importeNumerico.value <= 0) e.importe = 'Ej: 1500,50'
  else if (importeNumerico.value > Number(props.saldo)) e.importe = `Supera el saldo (${formatoPesos(props.saldo)}).`
  if (!archivo.value) e.archivo = 'Adjuntá el PDF.'
  else if (archivo.value.type !== 'application/pdf') e.archivo = 'Tiene que ser un PDF.'
  else if (archivo.value.size > 5 * 1024 * 1024) e.archivo = 'Máximo 5 MB.'
  errores.value = e
  return Object.keys(e).length === 0
}

async function enviar() {
  if (!validar()) return
  const datos = new FormData()
  datos.append('orden_id', String(props.ordenId))
  Object.entries(form).forEach(([k, v]) => datos.append(k, k === 'importe' ? importeNormalizado.value : v))
  datos.append('archivo', archivo.value as File)

  enviando.value = true
  try {
    await http.post('/facturas', datos)   // axios arma el multipart solo
    toast.mostrar('Factura cargada. Queda pendiente de aprobación.')
    Object.assign(form, { tipo: 'A', punto_venta: '', numero: '', fecha: hoy, importe: '' })
    archivo.value = null
    if (inputArchivo.value) inputArchivo.value.value = ''
    emit('cargada')
  } catch (e) {
    errores.value = erroresPorCampo(e)          // errores del back, debajo de cada campo
    toast.mostrar(mensajeDeError(e), 'error')
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <form class="grid gap-4 sm:grid-cols-2" novalidate @submit.prevent="enviar">
    <div>
      <label for="tipo" class="mb-1 block text-sm font-medium">Tipo</label>
      <select id="tipo" v-model="form.tipo" class="w-full rounded-md border border-slate-300 px-3 py-2">
        <option>A</option><option>B</option><option>C</option>
      </select>
    </div>
    <div>
      <label for="fecha" class="mb-1 block text-sm font-medium">Fecha</label>
      <input id="fecha" v-model="form.fecha" type="date" :max="hoy" class="w-full rounded-md border border-slate-300 px-3 py-2" />
      <p class="min-h-5 text-xs text-red-600">{{ errores.fecha }}</p>
    </div>
    <div>
      <label for="pv" class="mb-1 block text-sm font-medium">Punto de venta</label>
      <input id="pv" v-model.trim="form.punto_venta" inputmode="numeric" placeholder="00001"
        class="w-full rounded-md border px-3 py-2" :class="errores.punto_venta ? 'border-red-400' : 'border-slate-300'" />
      <p class="min-h-5 text-xs text-red-600">{{ errores.punto_venta }}</p>
    </div>
    <div>
      <label for="nro" class="mb-1 block text-sm font-medium">Número</label>
      <input id="nro" v-model.trim="form.numero" inputmode="numeric" placeholder="00001234"
        class="w-full rounded-md border px-3 py-2" :class="errores.numero ? 'border-red-400' : 'border-slate-300'" />
      <p class="min-h-5 text-xs text-red-600">{{ errores.numero }}</p>
    </div>
    <div>
      <label for="importe" class="mb-1 block text-sm font-medium">Importe</label>
      <input id="importe" v-model.trim="form.importe" inputmode="decimal" placeholder="1500,50"
        class="w-full rounded-md border px-3 py-2" :class="errores.importe ? 'border-red-400' : 'border-slate-300'" />
      <p class="min-h-5 text-xs text-slate-500" :class="{ '!text-red-600': errores.importe }">
        {{ errores.importe || `Saldo disponible: ${formatoPesos(saldo)}` }}
      </p>
    </div>
    <div>
      <label for="archivo" class="mb-1 block text-sm font-medium">PDF de la factura</label>
      <input id="archivo" ref="inputArchivo" type="file" accept="application/pdf" @change="elegirArchivo"
        class="w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-blue-700" />
      <p class="min-h-5 text-xs" :class="errores.archivo ? 'text-red-600' : 'text-slate-500'">
        {{ errores.archivo || (archivo ? `${archivo.name} · ${tamanioArchivo}` : 'Solo PDF, hasta 5 MB.') }}
      </p>
    </div>
    <div class="sm:col-span-2">
      <button type="submit" :disabled="enviando"
        class="rounded-md bg-blue-700 px-5 py-2 font-medium text-white hover:bg-blue-800 disabled:opacity-60">
        {{ enviando ? 'Cargando…' : 'Cargar factura' }}
      </button>
    </div>
  </form>
</template>