<script setup lang="ts">
const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue'])
function update(v: string) { emit('update:modelValue', v) }
</script>

<template>
  <div class="mb-6 relative">
    <div class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">
      <slot name="icon" />
    </div>
    <input
      :value="props.modelValue"
      @input="update($event.target.value)"
      :placeholder="props.placeholder"
      class="w-full pl-10 pr-4 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
      v-bind="$attrs"
    />
  </div>
</template>
