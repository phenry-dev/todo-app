<template>
  <div class="min-h-screen">
    <NuxtRouteAnnouncer />
    <NuxtPage />
  </div>
</template>

<script setup>
const config = useRuntimeConfig()
if (process.client) console.log('runtime public.apiBase =', config.public.apiBase)
</script>
