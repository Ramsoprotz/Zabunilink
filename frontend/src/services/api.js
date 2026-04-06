import axios from 'axios'
import router from '../router'

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
}, (error) => {
  return Promise.reject(error)
})

// Abort requests when offline to fail fast
api.interceptors.request.use((config) => {
  if (!navigator.onLine) {
    const err = new Error('No internet connection')
    err.code = 'ERR_NETWORK'
    err.isOffline = true
    return Promise.reject(err)
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      router.push('/login')
    } else if (
      error.response?.status === 403 &&
      error.response?.data?.code === 'subscription_required' &&
      router.currentRoute.value.name !== 'Subscription'
    ) {
      router.push('/subscription')
    }
    return Promise.reject(error)
  }
)

export default api
