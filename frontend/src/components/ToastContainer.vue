<script setup lang="ts">
import { useToastStore } from '@/stores/toast'

const store = useToastStore()
const estilos = {
  exito: 'bg-green-600',
  error: 'bg-red-600',
  info: 'bg-slate-700',
}
</script>

<template>
  <div class="fixed bottom-4 right-4 z-50 flex w-80 flex-col gap-2" aria-live="polite">
    <TransitionGroup name="toast">
      <div
        v-for="t in store.toasts" :key="t.id"
        :class="[estilos[t.tipo], 'flex items-start justify-between gap-3 rounded-lg px-4 py-3 text-sm text-white shadow-lg']"
        role="status"
      >
        <span>{{ t.mensaje }}</span>
        <button class="opacity-80 hover:opacity-100" aria-label="Cerrar notificación" @click="store.cerrar(t.id)">✕</button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(8px); }
</style>