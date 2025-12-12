<template>
  <div
    class="group flex items-center gap-3 px-4 py-3 bg-white border border-gray-200 rounded-lg mb-3 hover:shadow-sm transition"
    draggable="true"
    @dragstart="emit('dragstart')"
  >
    <!-- Checkbox -->
    <button
      class="flex-shrink-0 w-5 h-5 rounded-full border-2 border-gray-300 hover:border-gray-400 transition flex items-center justify-center"
      :class="{ 'bg-black border-black': task.done }"
      @click="emit('toggle')"
      :title="`Mark task as ${task.done ? 'incomplete' : 'complete'}`"
    >
      <Check v-if="task.done" :size="12" class="text-white" />
    </button>

    <!-- Title (editable) -->
    <div class="flex-1 min-w-0">
      <div v-if="!isEditing" class="text-sm text-black" :class="{ 'line-through text-gray-400': task.done }">
        {{ task.title }}
      </div>
      <input
        v-else
        v-model="editTitle"
        type="text"
        class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-black"
        @keyup.enter="saveEdit"
        @keyup.escape="cancelEdit"
        @blur="saveEdit"
        autofocus
      />
    </div>

    <!-- Actions -->
    <div class="flex-shrink-0 flex gap-1">
      <button
        v-if="!isEditing"
        @click="startEdit"
        class="p-1 text-gray-500 hover:text-black transition opacity-0 group-hover:opacity-100"
        title="Edit task"
      >
        <Edit2 :size="16" />
      </button>
      <button
        v-if="isEditing"
        @click="saveEdit"
        class="p-1 text-green-600 hover:text-green-700 transition"
        title="Save"
      >
        <Check :size="16" />
      </button>
      <button
        v-if="isEditing"
        @click="cancelEdit"
        class="p-1 text-gray-500 hover:text-black transition"
        title="Cancel"
      >
        <X :size="16" />
      </button>
      <button
        v-if="!isEditing"
        @click="emit('delete')"
        class="p-1 text-gray-500 hover:text-red-600 transition"
        title="Delete task"
      >
        <Trash2 :size="16" />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Check, Edit2, X, Trash2 } from 'lucide-vue-next'
import type { Task } from '~/types'

interface Props {
  task: Task
}

const props = defineProps<Props>()

const emit = defineEmits<{
  toggle: []
  edit: [title: string]
  delete: []
  dragstart: []
}>()

const isEditing = ref(false)
const editTitle = ref('')

function startEdit() {
  editTitle.value = props.task.title
  isEditing.value = true
}

function saveEdit() {
  if (editTitle.value.trim() && editTitle.value !== props.task.title) {
    emit('edit', editTitle.value.trim())
  }
  isEditing.value = false
}

function cancelEdit() {
  isEditing.value = false
  editTitle.value = ''
}
</script>
