<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-text">{{ $t('business.my_tenders_title') }}</h1>
        <p class="text-sm text-text-light mt-1">{{ $t('business.my_tenders_subtitle') }}</p>
      </div>
      <button class="neu-btn-primary flex items-center gap-2 px-5 py-2.5 text-sm font-semibold" @click="router.push('/business/post')">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ $t('business.post_tender') }}
      </button>
    </div>

    <!-- Search & filter -->
    <div class="flex flex-col sm:flex-row gap-3">
      <div class="relative flex-1 flex items-center">
        <svg class="absolute right-3 w-4 h-4 text-text-light pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="search"
          type="text"
          class="neu-input w-full pr-9"
          :placeholder="$t('business.search_placeholder')"
        />
      </div>
    </div>

    <!-- Status filter tabs -->
    <div class="flex gap-2 flex-wrap">
      <button
        v-for="tab in statusTabs"
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
        <span v-if="tabCount(tab.value) > 0" class="ml-1.5 text-xs opacity-70">({{ tabCount(tab.value) }})</span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="businessStore.loading && businessStore.myTenders.length === 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div v-for="i in 4" :key="i" class="neu-card h-44 animate-pulse bg-surface" />
    </div>

    <!-- Empty state -->
    <div v-else-if="filtered.length === 0" class="neu-card p-10 text-center">
      <svg class="w-14 h-14 mx-auto text-text-light/40 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="text-text-light font-medium">
        {{ search ? $t('business.no_match') : $t('business.no_category') }}
      </p>
      <button v-if="!search" class="neu-btn-primary mt-4 px-6 py-2.5 text-sm font-semibold" @click="router.push('/business/post')">
        {{ $t('business.post_first') }}
      </button>
    </div>

    <!-- Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div
        v-for="tender in filtered"
        :key="tender.id"
        class="neu-card p-5 flex flex-col gap-3"
      >
        <!-- Top row -->
        <div class="flex items-start justify-between gap-3">
          <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-text leading-snug line-clamp-2">{{ tender.title }}</h3>
            <p class="text-xs text-text-light mt-0.5">{{ tender.organization }}</p>
          </div>
          <span :class="statusBadgeClass(tender.status)" class="shrink-0 capitalize text-xs">
            {{ tender.status }}
          </span>
        </div>

        <!-- Meta row -->
        <div class="flex flex-wrap gap-2">
          <span v-if="tender.category" class="neu-badge-primary text-xs px-2 py-0.5">
            {{ tender.category?.name }}
          </span>
          <span v-if="tender.location" class="neu-badge text-xs px-2 py-0.5">
            <svg class="w-3 h-3 inline mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            </svg>
            {{ tender.location?.name }}
          </span>
        </div>

        <!-- Deadline -->
        <div :class="['flex items-center gap-1.5 text-xs font-medium', isDeadlineSoon(tender.deadline) ? 'text-red-500' : 'text-text-light']">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          {{ $t('business.deadline_label') }} {{ formatDate(tender.deadline) }}
          <span v-if="isDeadlineSoon(tender.deadline)" class="ml-1 font-bold">({{ $t('business.deadline_soon') }})</span>
        </div>

        <!-- Stats row -->
        <div class="flex items-center gap-4 text-xs text-text-light">
          <span class="flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0" />
            </svg>
            {{ tender.applications_count ?? 0 }} {{ $t('business.apps_label') }}
          </span>
          <span class="flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            {{ tender.views_count ?? 0 }} {{ $t('business.views_label') }}
          </span>
        </div>

        <!-- Action buttons -->
        <div class="flex items-center gap-2 mt-auto pt-1">
          <button
            class="neu-btn-primary flex-1 py-2 text-xs font-semibold flex items-center justify-center gap-1.5"
            @click="router.push(`/business/tenders/${tender.id}/applications`)"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            {{ $t('business.manage') }}
          </button>
          <button
            class="neu-btn-secondary flex-1 py-2 text-xs font-semibold flex items-center justify-center gap-1.5"
            @click="router.push(`/business/tenders/${tender.id}/edit`)"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            {{ $t('common.edit') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="businessStore.tendersPagination.last_page > 1" class="flex items-center justify-center gap-2">
      <button
        class="neu-btn px-4 py-2 text-sm"
        :disabled="businessStore.tendersPagination.current_page <= 1"
        @click="goToPage(businessStore.tendersPagination.current_page - 1)"
      >
        Previous
      </button>
      <span class="text-sm text-text-light px-2">Page {{ businessStore.tendersPagination.current_page }} of {{ businessStore.tendersPagination.last_page }}</span>
      <button
        class="neu-btn px-4 py-2 text-sm"
        :disabled="businessStore.tendersPagination.current_page >= businessStore.tendersPagination.last_page"
        @click="goToPage(businessStore.tendersPagination.current_page + 1)"
      >
        Next
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useBusinessStore } from '../../stores/business'

const { t, locale } = useI18n()
const router = useRouter()
const businessStore = useBusinessStore()

const search = ref('')
const activeTab = ref('all')

const statusTabs = [
  { label: t('common.all'), value: 'all' },
  { label: t('business.status_open'), value: 'open' },
  { label: t('business.status_closed'), value: 'closed' },
  { label: t('business.status_awarded'), value: 'awarded' },
]

const filtered = computed(() => {
  let list = businessStore.myTenders
  if (activeTab.value !== 'all') list = list.filter(t => t.status === activeTab.value)
  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    list = list.filter(t =>
      t.title?.toLowerCase().includes(q) ||
      t.organization?.toLowerCase().includes(q)
    )
  }
  return list
})

function tabCount(tab) {
  if (tab === 'all') return businessStore.myTenders.length
  return businessStore.myTenders.filter(t => t.status === tab).length
}

function goToPage(page) {
  businessStore.fetchMyTenders(page)
}

function statusBadgeClass(status) {
  const map = {
    open: 'neu-badge-success',
    closed: 'neu-badge-danger',
    awarded: 'neu-badge-info',
  }
  return map[status] ?? 'neu-badge'
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  const dateLocale = locale.value === 'sw' ? 'sw-TZ' : 'en-TZ'
  return new Date(dateStr).toLocaleDateString(dateLocale, { day: 'numeric', month: 'short', year: 'numeric' })
}

function isDeadlineSoon(dateStr) {
  if (!dateStr) return false
  const diff = new Date(dateStr) - new Date()
  return diff > 0 && diff < 7 * 24 * 60 * 60 * 1000
}

onMounted(() => businessStore.fetchMyTenders())
</script>
