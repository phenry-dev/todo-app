<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '~/stores/auth'
import { useTaskStore } from '~/stores/task'
import DateSidebar from '~/components/DateSidebar.vue'
import { toLocalISO } from '~/utils/date'

const auth = useAuthStore()
const taskStore = useTaskStore()
const router = useRouter()

if (process.client) {
  auth.loadFromStorage()
  // Only redirect on initial load if not authenticated
  if (!auth.token) {
    router.replace('/login')
  }
}

const selectedDate = ref(toLocalISO(new Date()))
const searchQuery = ref('')
const deletingId = ref<number | null>(null)
const draggingId = ref<number | null>(null)
const sortBy = ref<'order' | 'priority'>('order')

// Debounce timer for search
let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null

// Fetch tasks when date changes
watch(selectedDate, async (newDate) => {
  // Clear any pending search debounce
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
    searchDebounceTimer = null
  }
  await taskStore.fetchTasks(newDate, searchQuery.value.trim() || undefined)
}, { immediate: false })

// Debounced search - fetch from backend when user types
watch(searchQuery, async (newQuery) => {
  // Clear previous debounce timer
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
  }
  
  // Debounce: wait 300ms after user stops typing before making API call
  searchDebounceTimer = setTimeout(async () => {
    await taskStore.fetchTasks(selectedDate.value, newQuery.trim() || undefined)
  }, 300)
}, { immediate: false })

onMounted(async () => {
  await taskStore.fetchTasks(selectedDate.value, searchQuery.value.trim() || undefined)
})

const tasksForDate = computed(() => taskStore.tasksByDate(selectedDate.value))

// Tasks are already filtered by backend, so use them directly
const filteredTasks = computed(() => {
  return tasksForDate.value
})

async function handleAddTask(title: string) {
  try {
    await taskStore.addTask(title, selectedDate.value)
    taskStore.clearError()
  } catch (err) {
    // error is shown via taskStore.error
  }
}

async function handleToggleTask(id: number) {
  try {
    await taskStore.toggleDone(id)
    taskStore.clearError()
  } catch (err) {
    // error is shown
  }
}

async function handleEditTask(id: number, title: string) {
  try {
    await taskStore.updateTask(id, { title })
    taskStore.clearError()
  } catch (err) {
    // error is shown
  }
}

async function handleDeleteTask(id: number) {
  try {
    await taskStore.deleteTask(id)
    deletingId.value = null
    taskStore.clearError()
  } catch (err) {
    // error is shown
  }
}

async function logout() {
  await auth.logout()
  // Use replace to avoid adding to history and ensure redirect happens
  await router.replace('/login')
}

function onDragStart(id: number) {
  draggingId.value = id
}

function onDrop(targetId: number) {
  if (!draggingId.value || draggingId.value === targetId) {
    draggingId.value = null
    return
  }
  const ids = tasksForDate.value.map((t) => t.id).filter((i) => i !== draggingId.value)
  const targetIndex = ids.findIndex((i) => i === targetId)
  const insertAt = targetIndex === -1 ? ids.length : targetIndex
  ids.splice(insertAt, 0, draggingId.value)
  taskStore.reorderTasks(selectedDate.value, ids).catch(() => {
    // error is shown
  })
  draggingId.value = null
}
</script>

<template>
  <div class="min-h-screen relative overflow-hidden">
    <!-- Blurred Mountain Background -->
    <!-- Main App Window -->
    <div class="relative min-h-screen flex flex-col">
      <!-- Header -->
      <header class="bg-white border-b border-gray-200 px-6 py-4 sticky top-0 z-40">
        <div class="flex items-center justify-between">
          <!-- Logo -->
          <img src="/icon.svg" alt="Logo" class="w-12 h-12" />

          <!-- Search Bar -->
          <div class="flex-1 max-w-md mx-8">
            <div class="relative">
              <Search :size="16" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search"
                class="w-full pl-10 pr-4 py-2 bg-white border border-gray-300 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-black"
              />
            </div>
          </div>

          <!-- Profile Icon -->
          <button
            @click="logout"
            class="w-8 h-8 bg-black rounded-full flex items-center justify-center text-white hover:opacity-90 transition"
            title="Logout"
          >
            <User :size="16" />
          </button>
        </div>
      </header>

      <!-- Main Content Area -->
      <div class="flex flex-1 overflow-hidden">
        <!-- Sidebar -->
        <DateSidebar
          :selected-date="selectedDate"
          @update:date="(newDate) => (selectedDate = newDate)"
        />

        <!-- Content -->
        <main class="flex-1 bg-white rounded-tl-2xl overflow-y-auto shadow-lg">
          <div class="p-8">
            <!-- Error Alert -->
            <div
              v-if="taskStore.error"
              class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600"
            >
              {{ taskStore.error }}
            </div>

            <!-- Task Input Form (shown when no tasks) -->
            <div v-if="filteredTasks.length === 0 && !taskStore.loading" class="mb-8 flex flex-col items-center">
              <h2 class="text-2xl font-bold text-black mb-4">What do you have in mind?</h2>
              <div class="w-full max-w-2xl">
                <TaskForm
                  :loading="taskStore.loading"
                  :has-tasks="false"
                  @submit="handleAddTask"
                />
              </div>
            </div>

            <!-- Task List -->
            <TaskList
              v-if="filteredTasks.length > 0 || !taskStore.loading"
              :tasks="filteredTasks"
              :loading="taskStore.loading"
              :deleting-id="deletingId"
              no-tasks-message="No tasks for this date"
              @toggle="handleToggleTask"
              @edit="(id, title) => handleEditTask(id, title)"
              @delete="(id) => (deletingId = id)"
              @confirm-delete="handleDeleteTask"
              @cancel-delete="() => (deletingId = null)"
              @dragstart="(id) => onDragStart(id)"
              @drop="(id) => onDrop(id)"
            />

            <!-- Task Input Form (shown at bottom when tasks exist) -->
            <div v-if="filteredTasks.length > 0" class="mt-8">
              <TaskForm
                :loading="taskStore.loading"
                :has-tasks="true"
                @submit="handleAddTask"
              />
            </div>

            <div v-else class="text-center py-8 text-gray-500">
             
            </div>
          </div>
        </main>
      </div>
    </div>
  </div>
</template>