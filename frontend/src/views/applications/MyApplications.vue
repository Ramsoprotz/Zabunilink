<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-text">{{ $t('applications.title') }}</h1>
        <p class="text-text-light text-sm mt-1">{{ $t('applications.subtitle') }}</p>
      </div>
      <router-link
        v-if="authStore.isPro"
        to="/tenders"
        class="neu-btn-primary px-4 py-2.5 rounded-xl text-sm font-semibold"
      >
        New Application
      </router-link>
    </div>

    <!-- Status filter -->
    <div class="flex flex-wrap gap-2">
      <button
        v-for="status in statuses"
        :key="status.value"
        class="neu-btn px-4 py-2 text-xs font-medium"
        :class="selectedStatus === status.value ? 'neu-btn-active text-primary' : 'text-text-light'"
        @click="filterByStatus(status.value)"
      >
        {{ status.label }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12 text-text-light">{{ $t('applications.loading') }}</div>

    <!-- Error -->
    <div v-else-if="error" class="neu-card-sm text-center text-danger text-sm py-8">
      {{ error }}
      <button class="neu-btn px-4 py-2 text-sm text-primary font-medium ml-3" @click="fetchApplications">{{ $t('common.retry') }}</button>
    </div>

    <!-- Applications list -->
    <div v-else-if="applications.length" class="space-y-4">
      <div
        v-for="app in applications"
        :key="app.id"
        class="neu-card cursor-pointer hover:scale-[1.005] transition-transform duration-200"
        @click="$router.push(`/applications/${app.id}`)"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-text text-sm line-clamp-1">{{ app.tender?.title || 'Tender Application' }}</h3>
            <p class="text-xs text-text-light mt-1">{{ app.tender?.organization }}</p>
            <p class="text-xs text-text-light mt-1">
              {{ $t('applications.applied') }}: {{ formatDate(app.created_at) }}
            </p>
          </div>
          <span
            class="px-3 py-1 rounded-full text-xs font-semibold flex-shrink-0"
            :class="statusBadgeClass(app.status)"
          >
            {{ formatStatus(app.status) }}
          </span>
        </div>
        <div v-if="app.tender?.deadline" class="mt-3 pt-3 border-t border-surface-dark/30 text-xs text-text-light">
          Tender deadline: {{ formatDate(app.tender.deadline) }}
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="neu-card text-center py-16">
      <svg class="w-16 h-16 mx-auto text-text-light mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
      </svg>
      <h3 class="text-lg font-semibold text-text mb-1">{{ $t('applications.no_applications') }}</h3>
      <p class="text-text-light text-sm mb-4">
        {{ authStore.isPro ? $t('applications.no_applications_desc') : 'Upgrade to Pro to apply for tenders.' }}
      </p>
      <router-link
        :to="authStore.isPro ? '/tenders' : '/subscription'"
        class="neu-btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold inline-block"
      >
        {{ authStore.isPro ? $t('dashboard.browse_tenders') : $t('common.upgrade') }}
      </router-link>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="flex items-center justify-center gap-2">
      <button class="neu-btn px-4 py-2 text-sm" :disabled="pagination.current_page <= 1" @click="goToPage(pagination.current_page - 1)">
        Previous
      </button>
      <span class="text-sm text-text-light px-3">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
      <button class="neu-btn px-4 py-2 text-sm" :disabled="pagination.current_page >= pagination.last_page" @click="goToPage(pagination.current_page + 1)">
        Next
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'

const { t, locale } = useI18n()
const authStore = useAuthStore()

const applications = ref([])
const pagination = ref({})
const loading = ref(true)
const error = ref('')
const selectedStatus = ref('')

const statuses = [
  { value: '', label: t('common.all') },
  { value: 'pending', label: t('applications.status_pending') },
  { value: 'in_progress', label: t('applications.status_in_progress') },
  { value: 'submitted', label: t('applications.status_submitted') },
  { value: 'won', label: t('applications.status_won') },
  { value: 'lost', label: t('applications.status_lost') },
]

function formatDate(dateStr) {
  if (!dateStr) return t('common.na')
  const dateLocale = locale.value === 'sw' ? 'sw-TZ' : 'en-GB'
  return new Date(dateStr).toLocaleDateString(dateLocale, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

function formatStatus(status) {
  if (!status) return t('applications.status_pending')
  return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

function statusBadgeClass(status) {
  const map = {
    pending: 'bg-warning/10 text-warning',
    in_progress: 'bg-info/10 text-info',
    submitted: 'bg-primary/10 text-primary',
    won: 'bg-success/10 text-success',
    lost: 'bg-danger/10 text-danger',
  }
  return map[status] || 'bg-warning/10 text-warning'
}

function filterByStatus(status) {
  selectedStatus.value = status
  fetchApplications(1)
}

function goToPage(page) {
  fetchApplications(page)
}

async function fetchApplications(page = 1) {
  loading.value = true
  error.value = ''
  try {
    const params = { page, per_page: 15 }
    if (selectedStatus.value) params.status = selectedStatus.value
    const { data } = await api.get('/applications', { params })
    applications.value = data.data || []
    pagination.value = data.meta || {}
  } catch {
    error.value = 'Failed to load applications.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchApplications)
</script>
