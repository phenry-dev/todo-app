<script setup lang="ts">
import { computed } from 'vue'
const props = defineProps({
  variant: { type: String as () => 'primary' | 'secondary', default: 'secondary' },
  type: { type: String as () => 'button' | 'submit' | 'reset', default: 'button' },
})
const classes = computed(() => {
  if (props.variant === 'primary') {
    return 'px-3 py-1 text-xs rounded transition bg-slate-900 text-white'
  }
  return 'px-3 py-1 text-xs rounded transition bg-white border border-slate-300 text-slate-600 hover:border-slate-400'
})
</script>

<template>
  <button :type="props.type" :class="classes" v-bind="$attrs" @click="$emit('click')">
    <slot />
  </button>
</template>
