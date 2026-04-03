import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))
  const token = ref(localStorage.getItem('token') || null)

  const isAuthenticated = computed(() => !!token.value)
  const isPro = computed(() => ['Pro', 'Business'].includes(user.value?.active_subscription?.plan?.name))
  const isBusiness = computed(() => user.value?.active_subscription?.plan?.name === 'Business')
  const isAdmin = computed(() => user.value?.role === 'admin')

  async function login(credentials) {
    const { data } = await api.post('/login', credentials)
    token.value = data.token
    user.value = data.user
    localStorage.setItem('token', data.token)
    localStorage.setItem('user', JSON.stringify(data.user))
    return data
  }

  async function register(form) {
    const { data } = await api.post('/register', form)
    token.value = data.token
    user.value = data.user
    localStorage.setItem('token', data.token)
    localStorage.setItem('user', JSON.stringify(data.user))
    return data
  }

  async function logout() {
    try {
      await api.post('/logout')
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
  }

  async function fetchProfile() {
    const { data } = await api.get('/profile')
    user.value = data.data
    localStorage.setItem('user', JSON.stringify(data.data))
    return data.data
  }

  async function updateProfile(form) {
    const { data } = await api.put('/profile', form)
    user.value = data.data
    localStorage.setItem('user', JSON.stringify(data.data))
    return data.data
  }

  return {
    user,
    token,
    isAuthenticated,
    isPro,
    isBusiness,
    isAdmin,
    login,
    register,
    logout,
    fetchProfile,
    updateProfile,
  }
})
