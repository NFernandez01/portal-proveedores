import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { http } from '@/api/http'
import type { Usuario } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<Usuario | null>(null)
  const sesionVerificada = ref(false)

  const isAdmin = computed(() => user.value?.rol === 'admin')

  async function login(email: string, password: string) {
    const { data } = await http.post<{ user: Usuario }>('/auth/login', { email, password })
    user.value = data.user
  }

  /** Al recargar la página, preguntamos al backend si la cookie sigue siendo válida */
  async function verificarSesion() {
    try {
      const { data } = await http.get<{ user: Usuario }>('/me')
      user.value = data.user
    } catch {
      user.value = null
    } finally {
      sesionVerificada.value = true
    }
  }

  async function logout() {
    await http.post('/auth/logout')
    user.value = null
  }

  return { user, sesionVerificada, isAdmin, login, verificarSesion, logout }
})