import axios, { AxiosError } from 'axios'
import type { ErrorApi } from '@/types'

export const http = axios.create({
  baseURL: '/api',
  withCredentials: true,     // manda la cookie con el JWT
  timeout: 15000,
})

// Si la sesión vence en medio del uso, volvemos al login
http.interceptors.response.use(
  (res) => res,
  async (error: AxiosError) => {
    const url = error.config?.url ?? ''
    if (error.response?.status === 401 && !url.includes('/auth/login') && !url.includes('/me')) {
      const { default: router } = await import('@/router')
      const { useAuthStore } = await import('@/stores/auth')
      useAuthStore().user = null
      router.push({ name: 'login', query: { expirada: '1' } })
    }
    return Promise.reject(error)
  },
)

/** Saca un mensaje legible de cualquier error de axios */
export function mensajeDeError(e: unknown): string {
  const data = (e as AxiosError<ErrorApi>).response?.data
  return data?.error ?? 'No pudimos conectarnos con el servidor. Probá de nuevo.'
}

export function erroresPorCampo(e: unknown): Record<string, string> {
  return (e as AxiosError<ErrorApi>).response?.data?.errores ?? {}
}