<template>
  <form @submit.prevent="handleSubmit" class="relative">
    <textarea
      v-model="title"
      type="text"
      :placeholder="hasTasks ? 'What else do you need to do?' : 'Write the task you plan to do today here...'"
      rows="3"
      class="w-full px-4 py-3 pr-12 rounded-lg border border-gray-300 text-sm text-black focus:outline-none focus:ring-2 focus:ring-black resize-none"
      :disabled="loading"
    />
    <button
      type="submit"
      :disabled="loading || !title.trim()"
      class="absolute bottom-3 right-3 w-8 h-8 bg-black text-white rounded-full flex items-center justify-center hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed transition"
      title="Submit task"
    >
      <ArrowUp :size="16" />
    </button>
  </form>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { ArrowUp } from 'lucide-vue-next'

interface Props {
  loading?: boolean
  hasTasks?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  hasTasks: false,
})

const emit = defineEmits<{
  submit: [title: string]
}>()

const title = ref('')

function handleSubmit() {
  if (title.value.trim()) {
    emit('submit', title.value.trim())
    title.value = ''
  }
}
</script>
