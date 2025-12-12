/**
 * Frontend type definitions for task management.
 */

/**
 * Backend task response from `/api/tasks`
 */
export interface TaskApiResponse {
  id: number
  statement: string
  is_completed: boolean
  due_date: string // ISO 8601 string
  order: number
  created_at?: string
}

/**
 * Frontend task state after mapping from API
 */
export interface Task {
  id: number
  title: string
  done: boolean
  date: string // YYYY-MM-DD
  order: number
  createdAt?: string
}

/**
 * User object
 */
export interface User {
  id: number
  name?: string
  email: string
}

/**
 * Login request/response
 */
export interface LoginRequest {
  email: string
  password: string
  device_name?: string
}

export interface LoginResponse {
  user: User
  token: string
}

/**
 * Reorder task request
 */
export interface ReorderTaskRequest {
  date: string
  tasks: Array<{ id: number; order: number }>
}

/**
 * Store task mutation updates
 */
export interface UpdateTaskPayload {
  title?: string
  done?: boolean
}

/**
 * Error response shape
 */
export interface ErrorResponse {
  message: string
  errors?: Record<string, string[]>
}
