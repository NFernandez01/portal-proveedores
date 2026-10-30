import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '../auth'
import { http } from '@/api/http'

// Reemplazamos el cliente HTTP real por funciones falsas (con tipo, lo exige el linter)
vi.mock('@/api/http', () => ({
  http: {
    get: vi.fn<(url: string) => Promise<unknown>>(),
    post: vi.fn<(url: string, body?: unknown) => Promise<unknown>>(),
  },
}))

describe('store de auth', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('guarda el usuario al hacer login', async () => {
    vi.mocked(http.post).mockResolvedValue({ data: { user: { id: 1, rol: 'admin', email: 'a@a.com' } } })
    const auth = useAuthStore()

    await auth.login('a@a.com', 'x')

    expect(auth.user?.email).toBe('a@a.com')
    expect(auth.isAdmin).toBe(true)
  })

  it('si /me falla, queda sin usuario pero marca la sesiÃ³n como verificada', async () => {
    vi.mocked(http.get).mockRejectedValue(new Error('401'))
    const auth = useAuthStore()

    await auth.verificarSesion()

    expect(auth.user).toBeNull()
    expect(auth.sesionVerificada).toBe(true)
  })
})