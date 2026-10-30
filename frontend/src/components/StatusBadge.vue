<script lang="ts">
import { defineComponent, type PropType } from 'vue'

// Options API (estilo Vue 2): data, props, computed y methods en un objeto.
// El resto del proyecto usa Composition API; este componente muestra que manejo ambas.
export default defineComponent({
  name: 'StatusBadge',
  props: {
    estado: {
      type: String as PropType<'pendiente' | 'aprobada' | 'rechazada' | 'abierta' | 'facturada' | 'cerrada'>,
      required: true,
    },
  },
  computed: {
    clases(): string {
      const mapa: Record<string, string> = {
        pendiente: 'bg-amber-100 text-amber-800 ring-amber-200',
        aprobada: 'bg-green-100 text-green-800 ring-green-200',
        rechazada: 'bg-red-100 text-red-800 ring-red-200',
        abierta: 'bg-blue-100 text-blue-800 ring-blue-200',
        facturada: 'bg-violet-100 text-violet-800 ring-violet-200',
        cerrada: 'bg-slate-100 text-slate-700 ring-slate-200',
      }
      return mapa[this.estado] ?? 'bg-slate-100 text-slate-700 ring-slate-200'
    },
    texto(): string {
      return this.estado.charAt(0).toUpperCase() + this.estado.slice(1)
    },
  },
})
</script>

<template>
  <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1', clases]">
    {{ texto }}
  </span>
</template>