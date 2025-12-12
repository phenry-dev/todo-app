<template>
  <div class="min-h-screen flex items-center justify-center relative overflow-hidden">
    <!-- Login Card -->
    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-8 mx-4 backdrop-blur-sm">
      <!-- Logo -->
      <div class="flex justify-center mb-4">
        <img src="/icon.svg" alt="Logo" class="w-12 h-12" />
      </div>
      
      <h2 class="text-center text-3xl font-bold text-black mb-2">Sign In</h2>
      <p class="text-center text-sm text-black mb-6">Login to continue using this app</p>

      <form @submit.prevent="onSubmit" class="space-y-5">
        <div>
          <label class="block text-sm text-black mb-2">Email</label>
          <input 
            v-model="email" 
            type="email" 
            placeholder="matt@goteam.example" 
            class="w-full bg-white border border-gray-300 rounded-lg px-4 py-3 text-sm text-black focus:outline-none focus:ring-2 focus:ring-black" 
          />
        </div>

        <div>
          <div class="flex justify-between items-center mb-2">
            <label class="block text-sm text-black">Password</label>
            <a href="#" class="text-sm text-black hover:underline">Forgot your password?</a>
          </div>
          <input 
            v-model="password" 
            type="password" 
            placeholder="password" 
            class="w-full bg-white border border-gray-300 rounded-lg px-4 py-3 text-sm text-black focus:outline-none focus:ring-2 focus:ring-black" 
          />
        </div>

        <button 
          :disabled="loading" 
          class="w-full bg-black text-white rounded-lg py-3 text-sm font-medium hover:opacity-90 disabled:opacity-50 transition"
        >
          <span v-if="!loading">Login</span>
          <span v-else>Logging in...</span>
        </button>
      </form>

      <div v-if="error" class="text-red-600 text-sm mt-4 p-3 bg-red-50 rounded-lg">{{ error }}</div>

      <hr class="my-6 border-gray-200" />
      <p class="text-xs text-gray-500 text-center mb-2">Demo credentials:</p>
      <div class="text-xs text-gray-600 bg-gray-50 p-3 rounded-lg mb-3">
        <p><strong>Email:</strong> matt@goteam.example</p>
        <p><strong>Password:</strong> password</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '~/stores/auth'
import { LogIn } from 'lucide-vue-next'

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

const router = useRouter()
const auth = useAuthStore()

// Redirect to home if already authenticated
onMounted(() => {
  if (process.client) {
    auth.loadFromStorage()
    if (auth.token && auth.user) {
      router.push('/')
    }
  }
})

async function onSubmit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    // redirect to homepage or tasks (index)
    await router.push('/')
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Login failed'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
</style>
