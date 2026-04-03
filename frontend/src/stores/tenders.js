import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api'

export const useTenderStore = defineStore('tenders', () => {
  const tenders = ref([])
  const tender = ref(null)
  const favorites = ref([])
  const categories = ref([])
  const locations = ref([])
  const pagination = ref({})
  const loading = ref(false)

  async function fetchTenders(params = {}) {
    loading.value = true
    try {
      const { data } = await api.get('/tenders', { params })
      tenders.value = data.data
      pagination.value = data.meta || {}
    } finally {
      loading.value = false
    }
  }

  async function fetchTender(id) {
    loading.value = true
    try {
      const { data } = await api.get(`/tenders/${id}`)
      tender.value = data.data
      return data.data
    } finally {
      loading.value = false
    }
  }

  const favoritesPagination = ref({})

  async function fetchFavorites(page = 1) {
    const { data } = await api.get('/favorites', { params: { page, per_page: 15 } })
    favorites.value = data.data
    favoritesPagination.value = data.meta || {}
  }

  async function toggleFavorite(tenderId) {
    const exists = favorites.value.find((f) => f.tender_id === tenderId)
    if (exists) {
      await api.delete(`/favorites/${tenderId}`)
      favorites.value = favorites.value.filter((f) => f.tender_id !== tenderId)
    } else {
      const { data } = await api.post('/favorites', { tender_id: tenderId })
      favorites.value.push(data.data)
    }
  }

  async function fetchCategories() {
    const { data } = await api.get('/categories')
    categories.value = data.data
  }

  async function fetchLocations() {
    const { data } = await api.get('/locations')
    locations.value = data.data
  }

  function isFavorited(tenderId) {
    return favorites.value.some((f) => f.tender_id === tenderId)
  }

  return {
    tenders,
    tender,
    favorites,
    favoritesPagination,
    categories,
    locations,
    pagination,
    loading,
    fetchTenders,
    fetchTender,
    fetchFavorites,
    toggleFavorite,
    fetchCategories,
    fetchLocations,
    isFavorited,
  }
})
