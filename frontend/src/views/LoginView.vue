<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { mensajeDeError } from '@/api/http'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const form = reactive({ email: '', password: '' })
const errores = reactive({ email: '', password: '' })
const errorGeneral = ref(route.query.expirada ? 'Tu sesión venció. Ingresá de nuevo.' : '')
const enviando = ref(false)

function validar(): boolean {
  errores.email = /^\S+@\S+\.\S+$/.test(form.email) ? '' : 'Ingresá un email válido.'
  errores.password = form.password ? '' : 'Ingresá tu contraseña.'
  return !errores.email && !errores.password
}

async function ingresar() {
  errorGeneral.value = ''
  if (!validar()) return

  enviando.value = true
  try {
    await auth.login(form.email, form.password)
    router.push((route.query.redirect as string) || { name: 'dashboard' })
  } catch (e) {
    errorGeneral.value = mensajeDeError(e)
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4">
    <form class="w-full max-w-sm rounded-xl bg-white p-8 shadow-sm ring-1 ring-slate-200" novalidate @submit.prevent="ingresar">
      <h1 class="mb-1 text-xl font-semibold text-slate-800">Portal de Proveedores</h1>
      <p class="mb-6 text-sm text-slate-500">Ingresá con tu cuenta</p>

      <p v-if="errorGeneral" class="mb-4 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">
        {{ errorGeneral }}
      </p>

      <label for="email" class="mb-1 block text-sm font-medium">Email</label>
      <input
        id="email" v-model.trim="form.email" type="email" autocomplete="username"
        class="w-full rounded-md border px-3 py-2 outline-none focus:ring-2 focus:ring-blue-500"
        :class="errores.email ? 'border-red-400' : 'border-slate-300'"
        :aria-invalid="!!errores.email" aria-describedby="email-error"
      />
      <p id="email-error" class="mt-1 min-h-5 text-xs text-red-600">{{ errores.email }}</p>

      <label for="password" class="mb-1 mt-2 block text-sm font-medium">Contraseña</label>
      <input
        id="password" v-model="form.password" type="password" autocomplete="current-password"
        class="w-full rounded-md border px-3 py-2 outline-none focus:ring-2 focus:ring-blue-500"
        :class="errores.password ? 'border-red-400' : 'border-slate-300'"
        :aria-invalid="!!errores.password" aria-describedby="password-error"
      />
      <p id="password-error" class="mt-1 min-h-5 text-xs text-red-600">{{ errores.password }}</p>

      <button
        type="submit" :disabled="enviando"
        class="mt-4 w-full rounded-md bg-blue-700 py-2 font-medium text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60"
      >
        {{ enviando ? 'Ingresando…' : 'Ingresar' }}
      </button>
    </form>
  </div>
</template>