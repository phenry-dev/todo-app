import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Task, TaskApiResponse, UpdateTaskPayload, ReorderTaskRequest } from '~/types'
import { useApi } from '~/composables/useApi'

export const useTaskStore = defineStore('task', () => {
  const tasks = ref<Task[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const api = useApi()

  /**
   * Map backend TaskApiResponse to frontend Task.
   */
  const mapTask = (r: TaskApiResponse): Task => ({
    id: r.id,
    title: r.statement,
    done: r.is_completed,
    date: r.due_date.split('T')[0], // Extract YYYY-MM-DD from ISO string
    order: r.order,
    createdAt: r.created_at
  })

  /**
   * Get tasks for a specific date, sorted by order.
   */
  const tasksByDate = computed(() => (date: string) => {
    return tasks.value
      .filter((t) => t.date === date)
      .sort((a, b) => a.order - b.order)
  })

  /**
   * Search tasks by title (case-insensitive), optionally filtered by date.
   */
  const searchTasks = computed(() => (query: string, date?: string) => {
    const q = query.toLowerCase()
    let filtered = tasks.value.filter((t) =>
      t.title.toLowerCase().includes(q)
    )
    if (date) {
      filtered = filtered.filter((t) => t.date === date)
    }
    return filtered.sort((a, b) => a.order - b.order)
  })

  /**
   * Fetch tasks from backend for a specific date.
   */
  async function fetchTasks(date: string, query?: string) {
    loading.value = true
    error.value = null

    try {
      const responses = await api.get<TaskApiResponse[]>('/api/tasks', {
        date,
        ...(query && { q: query })
      })

      tasks.value = (Array.isArray(responses) ? responses : []).map(mapTask)
    } catch (err: any) {
      error.value = err?.message || 'Failed to fetch tasks'
      console.error('fetchTasks error:', err)
    } finally {
      loading.value = false
    }
  }

  /**
   * Add a new task.
   */
  async function addTask(title: string, date: string) {
    try {
      const res = await api.post<TaskApiResponse>('/api/tasks', {
        statement: title,
        due_date: date
      })

      const task = mapTask(res)
      tasks.value.push(task)
      return task
    } catch (err: any) {
      // Extract validation error message if it's a 422 error
      error.value = err?.validationMessage || err?.data?.message || err?.message || 'Failed to add task'
      console.error('addTask error:', err)
      throw err
    }
  }

  /**
   * Update an existing task.
   */
  async function updateTask(id: number, updates: UpdateTaskPayload) {
    try {
      const body: { statement?: string; is_completed?: boolean } = {}
      if (updates.title) body.statement = updates.title
      if (updates.done !== undefined) body.is_completed = updates.done

      const res = await api.put<TaskApiResponse>(`/api/tasks/${id}`, body)
      const task = tasks.value.find((t) => t.id === id)
      if (task) {
        task.title = res.statement
        task.done = res.is_completed
      }
    } catch (err: any) {
      // Extract validation error message if it's a 422 error
      error.value = err?.validationMessage || err?.data?.message || err?.message || 'Failed to update task'
      console.error('updateTask error:', err)
      throw err
    }
  }

  /**
   * Toggle task completion status.
   */
  async function toggleDone(id: number) {
    const task = tasks.value.find((t) => t.id === id)
    if (!task) return

    const newState = !task.done
    try {
      await updateTask(id, { done: newState })
    } catch (err) {
      // Revert on error
      task.done = !newState
      throw err
    }
  }

  /**
   * Delete a task.
   */
  async function deleteTask(id: number) {
    try {
      await api.delete(`/api/tasks/${id}`)
      const idx = tasks.value.findIndex((t) => t.id === id)
      if (idx !== -1) {
        tasks.value.splice(idx, 1)
      }
    } catch (err: any) {
      error.value = err?.message || 'Failed to delete task'
      console.error('deleteTask error:', err)
      throw err
    }
  }

  /**
   * Reorder tasks for a specific date.
   */
  async function reorderTasks(date: string, ids: number[]) {
    try {
      const payload: ReorderTaskRequest = {
        date,
        tasks: ids.map((id, idx) => ({ id, order: idx }))
      }

      await api.post('/api/tasks/reorder', payload)

      // Update local order
      ids.forEach((id, idx) => {
        const task = tasks.value.find((t) => t.id === id)
        if (task) task.order = idx
      })
    } catch (err: any) {
      error.value = err?.message || 'Failed to reorder tasks'
      console.error('reorderTasks error:', err)
      throw err
    }
  }

  /**
   * Clear error message.
   */
  function clearError() {
    error.value = null
  }

  return {
    // State
    tasks,
    loading,
    error,

    // Computed
    tasksByDate,
    searchTasks,

    // Methods
    fetchTasks,
    addTask,
    updateTask,
    toggleDone,
    deleteTask,
    reorderTasks,
    clearError
  }
})

