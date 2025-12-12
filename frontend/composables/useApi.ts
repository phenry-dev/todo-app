import { useRuntimeConfig } from '#app'
import { useAuthStore } from '~/stores/auth'

export type ApiResponse<T = any> = {
  data?: T
  message?: string
}

/**
 * Centralized API client composable.
 * Handles Authorization headers, response unwrapping, and error handling.
 */
export const useApi = () => {
  const config = useRuntimeConfig()
  const auth = useAuthStore()

  /**
   * Build request headers with Authorization token if available.
   */
  const headers = () => ({
    'Content-Type': 'application/json',
    ...(auth.token && { Authorization: `Bearer ${auth.token}` })
  })

  /**
   * Unwrap Laravel response: if response has `data` property, return it; otherwise return response.
   */
  const unwrapResponse = <T>(response: any): T => {
    if (response && typeof response === 'object' && 'data' in response) {
      return response.data as T
    }
    return response as T
  }

  /**
   * Make a GET request.
   */
  async function get<T>(endpoint: string, query?: Record<string, any>): Promise<T> {
    try {
      const response = await $fetch(
        `${config.public.apiBase}${endpoint}`,
        {
          method: 'GET',
          query,
          headers: headers()
        }
      )
      return unwrapResponse<T>(response)
    } catch (error: any) {
      console.error(`GET ${endpoint}:`, error)
      throw error
    }
  }

  /**
   * Extract validation error messages from Laravel 422 response.
   */
  function extractValidationErrors(error: any): string {
    // Handle different error response structures
    const errorData = error?.data || error?.response?._data || error
    
    if (errorData?.errors) {
      const errors = errorData.errors
      // Flatten all error messages into a single string
      const messages: string[] = []
      for (const field in errors) {
        if (Array.isArray(errors[field])) {
          messages.push(...errors[field])
        } else if (typeof errors[field] === 'string') {
          messages.push(errors[field])
        }
      }
      if (messages.length > 0) {
        return messages.join('. ')
      }
    }
    
    // Fallback to message if available
    return errorData?.message || error?.message || 'Validation failed'
  }

  /**
   * Make a POST request.
   */
  async function post<T>(endpoint: string, body: any): Promise<T> {
    try {
      const response = await $fetch(
        `${config.public.apiBase}${endpoint}`,
        {
          method: 'POST',
          body,
          headers: headers()
        }
      )
      return unwrapResponse<T>(response)
    } catch (error: any) {
      console.error(`POST ${endpoint}:`, error)
      // Enhance 422 errors with validation messages
      if (error?.status === 422 || error?.statusCode === 422) {
        const validationMessage = extractValidationErrors(error)
        error.validationMessage = validationMessage
      }
      throw error
    }
  }

  /**
   * Make a PUT request.
   */
  async function put<T>(endpoint: string, body: any): Promise<T> {
    try {
      const response = await $fetch(
        `${config.public.apiBase}${endpoint}`,
        {
          method: 'PUT',
          body,
          headers: headers()
        }
      )
      return unwrapResponse<T>(response)
    } catch (error: any) {
      console.error(`PUT ${endpoint}:`, error)
      // Enhance 422 errors with validation messages
      if (error?.status === 422 || error?.statusCode === 422) {
        const validationMessage = extractValidationErrors(error)
        error.validationMessage = validationMessage
      }
      throw error
    }
  }

  /**
   * Make a DELETE request.
   */
  async function delete_(endpoint: string): Promise<void> {
    try {
      await $fetch(
        `${config.public.apiBase}${endpoint}`,
        {
          method: 'DELETE',
          headers: headers()
        }
      )
    } catch (error: any) {
      console.error(`DELETE ${endpoint}:`, error)
      throw error
    }
  }

  return {
    get,
    post,
    put,
    delete: delete_,
    unwrapResponse
  }
}
