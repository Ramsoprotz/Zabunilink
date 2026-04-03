import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api'

export const useBusinessStore = defineStore('business', () => {
  const myTenders = ref([])
  const tendersPagination = ref({})
  const currentTender = ref({})
  const applications = ref([])
  const applicationsPagination = ref({})
  const stats = ref({})
  const loading = ref(false)

  async function fetchMyTenders(page = 1, perPage = 15) {
    loading.value = true
    try {
      const { data } = await api.get('/tenderee/tenders', {
        params: { page, per_page: perPage },
      })
      myTenders.value = data.data ?? data
      tendersPagination.value = data.meta || {}
    } finally {
      loading.value = false
    }
  }

  async function fetchTender(id) {
    loading.value = true
    try {
      const { data } = await api.get(`/tenderee/tenders/${id}`)
      currentTender.value = data.data ?? data
      return currentTender.value
    } finally {
      loading.value = false
    }
  }

  async function createTender(formData) {
    loading.value = true
    try {
      const { data } = await api.post('/tenderee/tenders', formData)
      myTenders.value.unshift(data.data ?? data)
      return data.data ?? data
    } finally {
      loading.value = false
    }
  }

  async function updateTender(id, formData) {
    loading.value = true
    try {
      const { data } = await api.put(`/tenderee/tenders/${id}`, formData)
      const updated = data.data ?? data
      const idx = myTenders.value.findIndex(t => t.id === id)
      if (idx !== -1) myTenders.value[idx] = updated
      currentTender.value = updated
      return updated
    } finally {
      loading.value = false
    }
  }

  async function deleteTender(id) {
    loading.value = true
    try {
      await api.delete(`/tenderee/tenders/${id}`)
      myTenders.value = myTenders.value.filter(t => t.id !== id)
    } finally {
      loading.value = false
    }
  }

  async function fetchApplications(tenderId, page = 1, perPage = 20) {
    loading.value = true
    try {
      const { data } = await api.get(`/tenderee/tenders/${tenderId}/applications`, {
        params: { page, per_page: perPage },
      })
      applications.value = data.data ?? data
      applicationsPagination.value = data.meta || {}
      return applications.value
    } finally {
      loading.value = false
    }
  }

  async function updateApplication(tenderId, appId, formData) {
    loading.value = true
    try {
      const { data } = await api.put(`/tenderee/tenders/${tenderId}/applications/${appId}`, formData)
      const updated = data.data ?? data
      const idx = applications.value.findIndex(a => a.id === appId)
      if (idx !== -1) applications.value[idx] = updated
      return updated
    } finally {
      loading.value = false
    }
  }

  async function uploadTenderDocuments(tenderId, files) {
    const form = new FormData()
    files.forEach(f => form.append('documents[]', f))
    const { data } = await api.post(`/tenderee/tenders/${tenderId}/documents`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    if (currentTender.value?.id === tenderId) currentTender.value.documents = data.documents
    return data
  }

  async function deleteTenderDocument(tenderId, path) {
    const { data } = await api.delete(`/tenderee/tenders/${tenderId}/documents`, { data: { path } })
    if (currentTender.value?.id === tenderId) currentTender.value.documents = data.documents
    return data
  }

  async function fetchStats() {
    loading.value = true
    try {
      const { data } = await api.get('/tenderee/stats')
      stats.value = data.data ?? data
      return stats.value
    } finally {
      loading.value = false
    }
  }

  return {
    myTenders,
    tendersPagination,
    currentTender,
    applications,
    applicationsPagination,
    stats,
    loading,
    fetchMyTenders,
    fetchTender,
    createTender,
    updateTender,
    deleteTender,
    fetchApplications,
    updateApplication,
    fetchStats,
    uploadTenderDocuments,
    deleteTenderDocument,
  }
})
