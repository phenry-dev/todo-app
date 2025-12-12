import { useRuntimeConfig } from '#app'
import { useAuthStore } from '~/stores/auth'

export type TaskResponse = {
  id: number
  statement: string
  is_completed: boolean
  due_date: string
  order: number
}

/**
 * Composable for task API calls.
 * Uses the auth token from Pinia store for authorization.
 */
export const useTaskApi = () => {
  const config = useRuntimeConfig()
  const auth = useAuthStore()

  const headers = () => ({
    Authorization: `Bearer ${auth.token}`,
    'Content-Type': 'application/json'
  })

  async function fetchTasks(date: string, query?: string): Promise<TaskResponse[]> {
    try {
      const resp = await $fetch(`${config.public.apiBase}/api/tasks`, {
        method: 'GET',
        query: { date, q: query },
        headers: headers()
      })

      // Laravel API returns resources wrapped under `data` for collections.
      if (resp && typeof resp === 'object' && Array.isArray((resp as any).data)) {
        return (resp as any).data as TaskResponse[]
      }

      // If API returns an array directly
      if (Array.isArray(resp)) return resp as TaskResponse[]

      return []
    } catch (error) {
      console.error('Failed to fetch tasks:', error)
      return []
    }
  }

  async function createTask(statement: string, dueDate: string): Promise<TaskResponse> {
    const resp = await $fetch(`${config.public.apiBase}/api/tasks`, {
      method: 'POST',
      body: { statement, due_date: dueDate },
      headers: headers()
    })

    // unwrap single resource
    return (resp && (resp as any).data) ? (resp as any).data : resp
  }

  async function updateTask(
    id: number,
    updates: { statement?: string; is_completed?: boolean }
  ): Promise<TaskResponse> {
    const resp = await $fetch(`${config.public.apiBase}/api/tasks/${id}`, {
      method: 'PUT',
      body: updates,
      headers: headers()
    })
    return (resp && (resp as any).data) ? (resp as any).data : resp
  }

  async function deleteTask(id: number): Promise<void> {
    await $fetch(`${config.public.apiBase}/api/tasks/${id}`, {
      method: 'DELETE',
      headers: headers()
    })
  }

  async function reorderTasks(
    date: string,
    tasks: Array<{ id: number; order: number }>
  ): Promise<void> {
    await $fetch(`${config.public.apiBase}/api/tasks/reorder`, {
      method: 'POST',
      body: { date, tasks },
      headers: headers()
    })
  }

  return {
    fetchTasks,
    createTask,
    updateTask,
    deleteTask,
    reorderTasks
  }
}
