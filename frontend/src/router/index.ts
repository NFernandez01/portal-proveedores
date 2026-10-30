import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

declare module 'vue-router' {
  interface RouteMeta {
    publica?: boolean
    rol?: 'admin' | 'proveedor'
    titulo?: string
  }
}

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', name: 'login', component: () => import('@/views/LoginView.vue'), meta: { publica: true, titulo: 'Ingresar' } },
    {
      path: '/',
      component: () => import('@/layouts/AppLayout.vue'),
      children: [
        { path: '', name: 'dashboard', component: () => import('@/views/DashboardView.vue'), meta: { titulo: 'Inicio' } },
        { path: 'ordenes', name: 'ordenes', component: () => import('@/views/OrdenesView.vue'), meta: { titulo: 'Órdenes de compra' } },
        { path: 'ordenes/:id', name: 'orden', component: () => import('@/views/OrdenDetalleView.vue'), props: true, meta: { titulo: 'Detalle de orden' } },
        { path: 'facturas', name: 'facturas', component: () => import('@/views/FacturasView.vue'), meta: { titulo: 'Facturas' } },
        { path: 'sin-permiso', name: 'forbidden', component: () => import('@/views/ForbiddenView.vue'), meta: { titulo: 'Sin permiso' } },
      ],
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  if (!auth.sesionVerificada) await auth.verificarSesion()

  if (!to.meta.publica && !auth.user) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
  if (to.name === 'login' && auth.user) {
    return { name: 'dashboard' }
  }
  if (to.meta.rol && auth.user?.rol !== to.meta.rol) {
    return { name: 'forbidden' }
  }
})

router.afterEach((to) => {
  document.title = `${to.meta.titulo ?? 'Portal'} · Portal de Proveedores`
})

export default router