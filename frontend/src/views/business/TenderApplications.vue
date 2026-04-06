<template>
  <div class="space-y-6">
    <!-- Back + header -->
    <div class="flex items-center gap-3">
      <button class="neu-btn p-2" @click="router.back()">
        <svg class="w-5 h-5 text-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>
      <div>
        <h1 class="text-xl font-bold text-text line-clamp-1">
          {{ businessStore.currentTender?.title ?? $t('business.applications_title') }}
        </h1>
        <p class="text-xs text-text-light mt-0.5">{{ $t('business.applications_subtitle') }}</p>
      </div>
    </div>

    <!-- Stats row -->
    <div class="grid grid-cols-3 gap-4">
      <div class="neu-card-sm p-4 text-center">
        <div class="text-2xl font-bold text-text">{{ businessStore.applications.length }}</div>
        <div class="text-xs text-text-light mt-0.5">{{ $t('business.total') }}</div>
      </div>
      <div class="neu-card-sm p-4 text-center">
        <div class="text-2xl font-bold text-secondary">{{ countByStatus('shortlisted') }}</div>
        <div class="text-xs text-text-light mt-0.5">{{ $t('business.shortlisted') }}</div>
      </div>
      <div class="neu-card-sm p-4 text-center">
        <div class="text-2xl font-bold text-primary">{{ countByStatus('awarded') }}</div>
        <div class="text-xs text-text-light mt-0.5">{{ $t('business.awarded') }}</div>
      </div>
    </div>

    <!-- Filter tabs -->
    <div class="flex gap-2 flex-wrap">
      <button
        v-for="tab in filterTabs"
        :key="tab.value"
        :class="[
          'px-4 py-1.5 rounded-full text-sm font-medium transition-all duration-150',
          activeTab === tab.value
            ? 'neu-pressed text-primary font-semibold'
            : 'neu-btn text-text-light hover:text-text',
        ]"
        @click="activeTab = tab.value"
      >
        {{ tab.label }}
        <span v-if="tabCount(tab.value) > 0" class="ml-1 text-xs opacity-70">({{ tabCount(tab.value) }})</span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="businessStore.loading && businessStore.applications.length === 0" class="space-y-4">
      <div v-for="i in 3" :key="i" class="neu-card-sm h-32 animate-pulse bg-surface" />
    </div>

    <!-- Empty state -->
    <div v-else-if="filtered.length === 0" class="neu-card p-10 text-center">
      <svg class="w-14 h-14 mx-auto text-text-light/40 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0" />
      </svg>
      <p class="text-text-light font-medium">
        {{ activeTab === 'all' ? $t('business.no_applications') : $t('business.no_status_apps', { status: activeTab }) }}
      </p>
    </div>

    <!-- Application cards -->
    <div v-else class="space-y-4">
      <div v-for="app in filtered" :key="app.id" class="neu-card-sm p-5 space-y-4">
        <!-- Applicant info -->
        <div class="flex items-start justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center shrink-0">
              <span class="text-white font-bold text-sm">{{ initials(app.user?.name) }}</span>
            </div>
            <div>
              <p class="font-semibold text-text">{{ app.user?.name ?? 'Unknown' }}</p>
              <p class="text-xs text-text-light">{{ app.user?.business_name ?? app.user?.email }}</p>
            </div>
          </div>
          <span :class="statusBadgeClass(app.status)" class="capitalize text-xs shrink-0">
            {{ app.status }}
          </span>
        </div>

        <!-- Contact details -->
        <div class="flex flex-wrap gap-3 text-xs text-text-light">
          <span v-if="app.user?.email" class="flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            {{ app.user.email }}
          </span>
          <span v-if="app.user?.phone" class="flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.948V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
            {{ app.user.phone }}
          </span>
          <span class="flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            {{ $t('business.applied_date') }} {{ formatDate(app.created_at) }}
          </span>
        </div>

        <!-- Notes -->
        <div v-if="app.notes" class="bg-surface/60 rounded-xl p-3">
          <p class="text-xs font-medium text-text-light mb-1">{{ $t('business.applicant_notes') }}</p>
          <p class="text-sm text-text leading-relaxed">{{ app.notes }}</p>
        </div>

        <!-- Documents -->
        <div class="space-y-1.5">
          <p class="text-xs font-medium text-text-light uppercase tracking-wide">{{ $t('business.attached_docs') }}</p>
          <div v-if="app.documents && app.documents.length > 0" class="space-y-1.5">
            <a
              v-for="(doc, idx) in app.documents"
              :key="idx"
              :href="doc.url ?? doc"
              target="_blank"
              rel="noopener"
              class="neu-card-sm py-2 px-3 flex items-center gap-3 hover:ring-2 hover:ring-primary/20 transition-all"
            >
              <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                :class="(doc.mime ?? '').includes('pdf') ? 'bg-red-100' : 'bg-blue-100'">
                <svg class="w-4 h-4" :class="(doc.mime ?? '').includes('pdf') ? 'text-red-600' : 'text-blue-600'"
                  fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-primary truncate">{{ doc.name ?? `Document ${idx + 1}` }}</p>
                <p v-if="doc.size" class="text-xs text-text-light">{{ (doc.size / 1024).toFixed(0) }} KB</p>
              </div>
              <svg class="w-4 h-4 text-text-light shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
            </a>
          </div>
          <p v-else class="text-xs text-text-light/60 italic">No documents attached.</p>
        </div>

        <!-- Action buttons -->
        <div class="flex flex-wrap items-center gap-2 pt-1">
          <!-- Awarded — show badge + undo -->
          <template v-if="app.status === 'awarded'">
            <span class="neu-badge-success px-4 py-2 text-sm font-semibold">
              {{ $t('business.awarded_label') }}
            </span>
            <button
              class="neu-btn flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-text-light"
              :disabled="actionLoading === app.id"
              @click="updateStatus(app, 'shortlisted')"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
              </svg>
              {{ $t('business.undo_award') }}
            </button>
          </template>

          <!-- Rejected — show badge + reconsider -->
          <template v-else-if="app.status === 'rejected'">
            <span class="neu-badge-danger px-4 py-2 text-sm font-semibold">
              {{ $t('applications.status_rejected') }}
            </span>
            <button
              class="neu-btn flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-text-light"
              :disabled="actionLoading === app.id"
              @click="updateStatus(app, 'pending')"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
              </svg>
              {{ $t('business.reconsider') }}
            </button>
          </template>

          <!-- Active actions (pending / shortlisted) -->
          <template v-else>
            <!-- Shortlist: only if pending -->
            <button
              v-if="app.status === 'pending'"
              class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold text-white bg-amber-500 hover:bg-amber-600 transition-colors"
              :disabled="actionLoading === app.id"
              @click="updateStatus(app, 'shortlisted')"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M5 13l4 4L19 7" />
              </svg>
              {{ $t('business.shortlist_btn') }}
            </button>

            <!-- Unshortlist: back to pending -->
            <button
              v-if="app.status === 'shortlisted'"
              class="neu-btn flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-text-light"
              :disabled="actionLoading === app.id"
              @click="updateStatus(app, 'pending')"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
              </svg>
              {{ $t('business.unshortlist') }}
            </button>

            <!-- Award: only if shortlisted -->
            <button
              v-if="app.status === 'shortlisted'"
              class="neu-btn-primary flex items-center gap-1.5 px-4 py-2 text-sm font-semibold"
              :disabled="actionLoading === app.id"
              @click="openAwardModal(app)"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
              </svg>
              {{ $t('business.award_btn') }}
            </button>

            <!-- Reject: pending or shortlisted -->
            <button
              v-if="['pending', 'shortlisted'].includes(app.status)"
              class="neu-btn-danger flex items-center gap-1.5 px-4 py-2 text-sm font-semibold"
              :disabled="actionLoading === app.id"
              @click="updateStatus(app, 'rejected')"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              {{ $t('business.reject_btn') }}
            </button>
          </template>

          <svg v-if="actionLoading === app.id" class="w-5 h-5 animate-spin text-primary ml-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
          </svg>
        </div>

        <!-- Inline error -->
        <p v-if="actionError === app.id" class="text-xs text-red-500">Failed to update status. Please try again.</p>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="businessStore.applicationsPagination.last_page > 1" class="flex items-center justify-center gap-2">
      <button class="neu-btn px-4 py-2 text-sm" :disabled="businessStore.applicationsPagination.current_page <= 1" @click="goToPage(businessStore.applicationsPagination.current_page - 1)">
        Previous
      </button>
      <span class="text-sm text-text-light px-3">Page {{ businessStore.applicationsPagination.current_page }} of {{ businessStore.applicationsPagination.last_page }}</span>
      <button class="neu-btn px-4 py-2 text-sm" :disabled="businessStore.applicationsPagination.current_page >= businessStore.applicationsPagination.last_page" @click="goToPage(businessStore.applicationsPagination.current_page + 1)">
        Next
      </button>
    </div>

    <!-- Award Confirm Modal -->
    <Teleport to="body">
      <div
        v-if="awardModal.open"
        class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
        @click.self="awardModal.open = false"
      >
        <div class="neu-card max-w-sm w-full p-6 space-y-4">
          <h3 class="text-lg font-bold text-text">{{ $t('business.award_confirm_title') }}</h3>
          <p class="text-sm text-text-light leading-relaxed">
            {{ $t('business.award_confirm_desc') }}
          </p>
          <div class="flex gap-3">
            <button
              class="neu-btn-primary flex-1 py-2.5 font-semibold text-sm"
              :disabled="actionLoading === awardModal.app?.id"
              @click="confirmAward"
            >
              {{ actionLoading === awardModal.app?.id ? $t('common.processing') : $t('business.award_confirm_btn') }}
            </button>
            <button class="neu-btn-secondary flex-1 py-2.5 font-semibold text-sm" @click="awardModal.open = false">
              {{ $t('common.cancel') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useBusinessStore } from '../../stores/business'

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const businessStore = useBusinessStore()

const activeTab = ref('all')
const actionLoading = ref(null)
const actionError = ref(null)

const awardModal = reactive({ open: false, app: null })

const filterTabs = [
  { label: t('common.all'), value: 'all' },
  { label: t('applications.status_pending'), value: 'pending' },
  { label: t('business.shortlisted'), value: 'shortlisted' },
  { label: t('business.awarded'), value: 'awarded' },
  { label: t('applications.status_rejected'), value: 'rejected' },
]

const filtered = computed(() => {
  if (activeTab.value === 'all') return businessStore.applications
  return businessStore.applications.filter(a => a.status === activeTab.value)
})

function countByStatus(status) {
  return businessStore.applications.filter(a => a.status === status).length
}

function tabCount(tab) {
  if (tab === 'all') return businessStore.applications.length
  return countByStatus(tab)
}

function statusBadgeClass(status) {
  const map = {
    pending: 'neu-badge-warning',
    shortlisted: 'neu-badge-info',
    awarded: 'neu-badge-success',
    rejected: 'neu-badge-danger',
  }
  return map[status] ?? 'neu-badge'
}

function initials(name) {
  if (!name) return '?'
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  const dateLocale = locale.value === 'sw' ? 'sw-TZ' : 'en-TZ'
  return new Date(dateStr).toLocaleDateString(dateLocale, { day: 'numeric', month: 'short', year: 'numeric' })
}

function goToPage(page) {
  businessStore.fetchApplications(route.params.id, page)
}

function openAwardModal(app) {
  awardModal.app = app
  awardModal.open = true
}

async function confirmAward() {
  if (!awardModal.app) return
  await updateStatus(awardModal.app, 'awarded')
  awardModal.open = false
}

async function updateStatus(app, status) {
  actionLoading.value = app.id
  actionError.value = null
  try {
    await businessStore.updateApplication(route.params.id, app.id, { status })
  } catch {
    actionError.value = app.id
  } finally {
    actionLoading.value = null
  }
}

onMounted(async () => {
  const tenderId = route.params.id
  await Promise.all([
    businessStore.fetchTender(tenderId),
    businessStore.fetchApplications(tenderId),
  ])
})
</script>
