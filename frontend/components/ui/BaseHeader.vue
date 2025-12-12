<script setup lang="ts">
// simple shell component that exposes left/right slots
</script>

<template>
  <header class="bg-white border-b border-slate-200 px-6 py-4 sticky top-0 z-40">
    <div class="max-w-3xl mx-auto flex justify-between items-center">
      <div>
        <slot name="left" />
      </div>
      <div>
        <slot name="right" />
      </div>
    </div>
  </header>
</template>
