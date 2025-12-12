<template>
  <div class="space-y-2">
    <div v-if="loading" class="flex justify-center py-8">
      <p class="text-slate-500">Loading tasks...</p>
    </div>
    <div v-else-if="filteredTasks.length === 0" class="flex justify-center py-8">
      <p class="text-slate-500">{{ noTasksMessage }}</p>
    </div>
    <div v-for="task in filteredTasks" :key="task.id" class="relative">
      <TaskItem
        :task="task"
        @toggle="emit('toggle', task.id)"
        @edit="emit('edit', task.id, $event)"
        @delete="emit('delete', task.id)"
        @dragstart="emit('dragstart', task.id)"
        @dragover="$event.preventDefault()"
        @drop="emit('drop', task.id)"
      />
      <div
        v-if="deletingId === task.id"
        class="absolute inset-0 bg-white/90 rounded-lg flex items-center justify-center gap-2 z-10"
      >
        <p class="text-sm text-slate-900">Delete this task?</p>
        <button
          class="px-2 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700 transition"
          @click="emit('confirm-delete', task.id)"
        >
          Yes
        </button>
        <button
          class="px-2 py-1 text-xs bg-slate-300 text-slate-900 rounded hover:bg-slate-400 transition"
          @click="emit('cancel-delete')"
        >
          No
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Task } from '~/types'
import TaskItem from './TaskItem.vue'

interface Props {
  tasks: Task[]
  loading?: boolean
  deletingId?: number | null
  noTasksMessage?: string
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  deletingId: null,
  noTasksMessage: 'No tasks yet'
})

const emit = defineEmits<{
  toggle: [id: number]
  edit: [id: number, title: string]
  delete: [id: number]
  'confirm-delete': [id: number]
  'cancel-delete': []
  dragstart: [id: number]
  drop: [id: number]
}>()

const filteredTasks = computed(() => props.tasks)
</script>
