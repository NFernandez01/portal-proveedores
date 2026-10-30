<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <section class="mx-auto max-w-md py-16 text-center">
    <p class="text-5xl" aria-hidden="true">🔒</p>
    <h1 class="mt-4 text-xl font-semibold">No tenés permiso para ver esta página</h1>
    <p class="mt-2 text-sm text-slate-500">
      Esta sección está disponible solo para otro tipo de usuario. Si creés que es un error, consultá con el administrador.
    </p>
    <RouterLink
      :to="{ name: 'dashboard' }"
      class="mt-6 inline-block rounded-md bg-blue-700 px-5 py-2 text-sm font-medium text-white hover:bg-blue-800"
    >
      Volver al inicio
    </RouterLink>
  </section>
</template>
