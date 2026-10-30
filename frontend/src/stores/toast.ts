import { ref } from 'vue'
import { defineStore } from 'pinia'

export type TipoToast = 'exito' | 'error' | 'info'
interface Toast { id: number; tipo: TipoToast; mensaje: string }

export const useToastStore = defineStore('toast', () => {
  const toasts = ref<Toast[]>([])
  let siguienteId = 1

  function mostrar(mensaje: string, tipo: TipoToast = 'exito', duracionMs = 4000) {
    const id = siguienteId++
    toasts.value.push({ id, tipo, mensaje })
    setTimeout(() => cerrar(id), duracionMs)
  }

  function cerrar(id: number) {
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }

  return { toasts, mostrar, cerrar }
})