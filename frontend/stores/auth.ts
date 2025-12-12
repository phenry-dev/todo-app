import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { User, LoginResponse } from '~/types'
import { useApi } from '~/composables/useApi'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const api = useApi()

  /**
   * Load stored auth state from localStorage.
   */
  function loadFromStorage() {
    if (!process.client) return

    try {
      const stored = localStorage.getItem('auth')
      if (stored) {
        const { user: u, token: t } = JSON.parse(stored)
        user.value = u
        token.value = t
      }
    } catch (err) {
      console.error('Failed to load auth from localStorage:', err)
    }
  }

  /**
   * Persist auth state to localStorage.
   */
  function persist() {
    if (!process.client) return

    localStorage.setItem(
      'auth',
      JSON.stringify({
        user: user.value,
        token: token.value
      })
    )
  }

  /**
   * Login with email and password.
   */
  async function login(email: string, password: string, deviceName = 'web') {
    loading.value = true
    error.value = null

    try {
      const response = await api.post<LoginResponse>('/api/login', {
        email,
        password,
        device_name: deviceName
      })

      user.value = response.user
      token.value = response.token
      persist()

      return response
    } catch (err: any) {
      const message = err?.data?.message || err?.message || 'Login failed'
      error.value = message
      throw new Error(message)
    } finally {
      loading.value = false
    }
  }

  /**
   * Logout and clear auth state.
   */
  async function logout() {
    if (!token.value) {
      user.value = null
      return
    }

    try {
      await api.post('/api/logout', {})
    } catch (err) {
      console.error('Logout error:', err)
      // Continue logout even if request fails
    } finally {
      user.value = null
      token.value = null
      localStorage.removeItem('auth')
    }
  }

  /**
   * Check if user is authenticated.
   */
  function isAuthenticated(): boolean {
    return !!token.value && !!user.value
  }

  /**
   * Clear error state.
   */
  function clearError() {
    error.value = null
  }

  return {
    // State
    user,
    token,
    loading,
    error,

    // Methods
    loadFromStorage,
    login,
    logout,
    isAuthenticated,
    clearError
  }
})
