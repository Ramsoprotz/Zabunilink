<template>
  <div class="space-y-6">
    <!-- Page header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-text">{{ $t('business.dashboard_title') }}</h1>
        <p class="text-sm text-text-light mt-1">{{ $t('business.dashboard_subtitle') }}</p>
      </div>
      <button class="neu-btn-primary flex items-center gap-2 px-5 py-2.5 text-sm font-semibold" @click="router.push('/business/post')">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ $t('business.post_tender') }}
      </button>
    </div>

    <!-- Stats row -->
    <div v-if="businessStore.loading && !statsLoaded" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="i in 4" :key="i" class="neu-card-sm h-24 animate-pulse bg-surface" />
    </div>
    <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="neu-card-sm p-4 flex flex-col gap-1">
        <span class="text-xs text-text-light font-medium uppercase tracking-wide">{{ $t('business.total_posted') }}</span>
        <span class="text-3xl font-bold text-text">{{ businessStore.stats.total_posted ?? 0 }}</span>
        <span class="text-xs text-text-light">{{ $t('business.tenders_label') }}</span>
      </div>
      <div class="neu-card-sm p-4 flex flex-col gap-1">
        <span class="text-xs text-text-light font-medium uppercase tracking-wide">{{ $t('business.applications_received') }}</span>
        <span class="text-3xl font-bold text-secondary">{{ businessStore.stats.total_applications_received ?? 0 }}</span>
        <span class="text-xs text-text-light">{{ $t('business.received_label') }}</span>
      </div>
      <div class="neu-card-sm p-4 flex flex-col gap-1">
        <span class="text-xs text-text-light font-medium uppercase tracking-wide">{{ $t('business.awarded') }}</span>
        <span class="text-3xl font-bold text-primary">{{ businessStore.stats.total_awarded ?? 0 }}</span>
        <span class="text-xs text-text-light">{{ $t('business.contracts_label') }}</span>
      </div>
      <div class="neu-card-sm p-4 flex flex-col gap-1">
        <span class="text-xs text-text-light font-medium uppercase tracking-wide">{{ $t('business.active') }}</span>
        <span class="text-3xl font-bold text-blue-500">{{ businessStore.stats.active_tenders ?? 0 }}</span>
        <span class="text-xs text-text-light">{{ $t('business.open_label') }}</span>
      </div>
    </div>

    <!-- Recent tenders -->
    <div>
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-text">{{ $t('business.recent_tenders') }}</h2>
        <button class="text-sm text-primary font-medium hover:underline" @click="router.push('/business/tenders')">
          {{ $t('common.view_all') }}
        </button>
      </div>

      <!-- Loading skeleton -->
      <div v-if="businessStore.loading && !tendersLoaded" class="space-y-3">
        <div v-for="i in 3" :key="i" class="neu-card-sm h-20 animate-pulse bg-surface" />
      </div>

      <!-- Empty state -->
      <div v-else-if="recentTenders.length === 0" class="neu-card p-10 text-center">
        <svg class="w-14 h-14 mx-auto text-text-light/40 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p class="text-text-light font-medium">{{ $t('business.no_tenders') }}</p>
        <p class="text-sm text-text-light/70 mt-1 mb-5">{{ $t('business.no_tenders_desc') }}</p>
        <button class="neu-btn-primary px-6 py-2.5 text-sm font-semibold" @click="router.push('/business/post')">
          {{ $t('business.post_first') }}
        </button>
      </div>

      <!-- Tender list -->
      <div v-else class="space-y-3">
        <div
          v-for="tender in recentTenders"
          :key="tender.id"
          class="neu-card-sm p-4 cursor-pointer hover:scale-[1.01] transition-transform duration-150"
          @click="router.push(`/business/tenders/${tender.id}/applications`)"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
              <h3 class="font-semibold text-text truncate">{{ tender.title }}</h3>
              <p class="text-xs text-text-light mt-0.5">{{ tender.organization }}</p>
            </div>
            <span :class="statusBadgeClass(tender.status)" class="neu-badge shrink-0 capitalize">
              {{ tender.status }}
            </span>
          </div>
          <div class="flex items-center gap-4 mt-3 text-xs text-text-light">
            <span class="flex items-center gap-1">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              {{ $t('business.deadline_label') }} {{ formatDate(tender.deadline) }}
            </span>
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
        </div>
      </div>
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

const statsLoaded = ref(false)
const tendersLoaded = ref(false)

const recentTenders = computed(() => businessStore.myTenders.slice(0, 5))

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

onMounted(async () => {
  await Promise.all([
    businessStore.fetchStats().then(() => { statsLoaded.value = true }),
    businessStore.fetchMyTenders().then(() => { tendersLoaded.value = true }),
  ])
})
</script>
