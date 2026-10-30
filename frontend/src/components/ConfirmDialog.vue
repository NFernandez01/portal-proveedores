<script setup lang="ts">
import { nextTick, ref, watch } from 'vue'

const props = defineProps<{
  abierto: boolean
  titulo: string
  mensaje?: string
  textoConfirmar?: string
  peligro?: boolean
  pedirMotivo?: boolean
  procesando?: boolean
}>()
const emit = defineEmits<{ confirmar: [motivo: string]; cancelar: [] }>()

const motivo = ref('')
const errorMotivo = ref('')
const dialogo = ref<HTMLDivElement | null>(null)

watch(() => props.abierto, async (abierto) => {
  if (abierto) {
    motivo.value = ''
    errorMotivo.value = ''
    await nextTick()
    dialogo.value?.querySelector<HTMLElement>('textarea, button')?.focus()   // foco al abrir
  }
})

function confirmar() {
  if (props.pedirMotivo && !motivo.value.trim()) {
    errorMotivo.value = 'Indicá el motivo.'
    return
  }
  emit('confirmar', motivo.value.trim())
}
</script>

<template>
  <div
    v-if="abierto"
    class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 px-4"
    @keydown.esc="emit('cancelar')"
  >
    <div
      ref="dialogo"
      class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl"
      role="dialog" aria-modal="true" aria-labelledby="dlg-titulo"
    >
      <h2 id="dlg-titulo" class="text-lg font-semibold">{{ titulo }}</h2>
      <p v-if="mensaje" class="mt-2 text-sm text-slate-600">{{ mensaje }}</p>

      <div v-if="pedirMotivo" class="mt-4">
        <label for="motivo" class="mb-1 block text-sm font-medium">Motivo</label>
        <textarea id="motivo" v-model="motivo" rows="3" maxlength="500"
          class="w-full rounded-md border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-blue-500" />
        <p class="min-h-5 text-xs text-red-600">{{ errorMotivo }}</p>
      </div>

      <div class="mt-6 flex justify-end gap-2">
        <button class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50" @click="emit('cancelar')">
          Cancelar
        </button>
        <button
          :disabled="procesando"
          :class="[peligro ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-700 hover:bg-blue-800',
                   'rounded-md px-4 py-2 text-sm font-medium text-white disabled:opacity-60']"
          @click="confirmar"
        >{{ procesando ? 'Procesando…' : (textoConfirmar ?? 'Confirmar') }}</button>
      </div>
    </div>
  </div>
</template>