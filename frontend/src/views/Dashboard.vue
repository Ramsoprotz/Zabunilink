<template>
  <div class="space-y-6">
    <!-- Welcome -->
    <div>
      <h1 class="text-2xl font-bold text-text">
        {{ $t('dashboard.welcome') }} {{ authStore.user?.name?.split(' ')[0] }}
      </h1>
      <p class="text-text-light text-sm mt-1">{{ $t('dashboard.subtitle') }}</p>
    </div>

    <!-- Stats cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="neu-card-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs text-text-light uppercase tracking-wide">{{ $t('dashboard.active_tenders') }}</p>
            <p class="text-2xl font-bold text-primary mt-1">{{ stats.activeTenders }}</p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="neu-card-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs text-text-light uppercase tracking-wide">{{ $t('dashboard.my_favorites') }}</p>
            <p class="text-2xl font-bold text-danger mt-1">{{ stats.favorites }}</p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-danger/10 flex items-center justify-center">
            <svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="neu-card-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs text-text-light uppercase tracking-wide">{{ $t('dashboard.my_applications') }}</p>
            <p class="text-2xl font-bold text-info mt-1">{{ stats.applications }}</p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-info/10 flex items-center justify-center">
            <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
        </div>
      </div>

      <div class="neu-card-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs text-text-light uppercase tracking-wide">{{ $t('dashboard.subscription') }}</p>
            <p class="text-lg font-bold mt-1" :class="{
              'text-secondary': authStore.isBusiness,
              'text-primary': authStore.isPro && !authStore.isBusiness,
              'text-text-light': !authStore.isPro,
            }">
              {{ subscriptionLabel }}
            </p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-secondary/10 flex items-center justify-center">
            <svg class="w-5 h-5 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent tenders -->
    <div>
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-text">{{ $t('dashboard.recent_tenders') }}</h2>
        <router-link to="/tenders" class="text-sm text-primary font-medium hover:underline">
          {{ $t('common.view_all') }}
        </router-link>
      </div>

      <div v-if="loadingTenders" class="text-center py-8 text-text-light">{{ $t('dashboard.loading_tenders') }}</div>

      <div v-else-if="recentTenders.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="tender in recentTenders"
          :key="tender.id"
          class="neu-card-sm cursor-pointer hover:scale-[1.01] transition-transform duration-200"
          @click="$router.push(`/tenders/${tender.id}`)"
        >
          <div class="flex items-start justify-between mb-2">
            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
              {{ tender.category?.name || $t('tenders.general') }}
            </span>
            <span
              class="px-2 py-0.5 rounded-full text-xs font-medium"
              :class="tender.type === 'government' ? 'bg-info/10 text-info' : 'bg-accent/10 text-accent'"
            >
              {{ tender.type === 'government' ? $t('tenders.govt') : $t('tenders.private') }}
            </span>
          </div>
          <h3 class="font-semibold text-text text-sm line-clamp-2 mb-1">{{ tender.title }}</h3>
          <p class="text-xs text-text-light mb-3">{{ tender.organization }}</p>
          <div class="flex items-center justify-between text-xs text-text-light">
            <span>{{ tender.location?.name || $t('common.tanzania') }}</span>
            <span class="text-danger font-medium">
              {{ $t('dashboard.due') }} {{ formatDate(tender.deadline) }}
            </span>
          </div>
        </div>
      </div>

      <div v-else class="neu-card-sm text-center py-8 text-text-light">
        {{ $t('dashboard.no_tenders') }}
      </div>
    </div>

    <!-- Quick links -->
    <div>
      <h2 class="text-lg font-semibold text-text mb-4">{{ $t('dashboard.quick_actions') }}</h2>
      <div class="flex flex-wrap gap-3">
        <router-link to="/tenders" class="neu-btn px-5 py-2.5 text-sm font-medium text-text">
          {{ $t('dashboard.browse_tenders') }}
        </router-link>
        <router-link to="/favorites" class="neu-btn px-5 py-2.5 text-sm font-medium text-text">
          {{ $t('dashboard.my_favorites') }}
        </router-link>
        <router-link to="/subscription" class="neu-btn px-5 py-2.5 text-sm font-medium text-text">
          {{ $t('common.upgrade') }}
        </router-link>
        <router-link to="/profile" class="neu-btn px-5 py-2.5 text-sm font-medium text-text">
          {{ $t('dashboard.edit_profile') }}
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useTenderStore } from '../stores/tenders'
import api from '../services/api'

const { t, locale } = useI18n()
const authStore = useAuthStore()
const tenderStore = useTenderStore()

const subscriptionLabel = computed(() => {
  const planName = authStore.user?.active_subscription?.plan?.name
  return planName || 'Basic'
})

const recentTenders = ref([])
const loadingTenders = ref(true)
const stats = ref({
  activeTenders: 0,
  favorites: 0,
  applications: 0,
})

function formatDate(dateStr) {
  if (!dateStr) return t('common.na')
  const dateLocale = locale.value === 'sw' ? 'sw-TZ' : 'en-GB'
  return new Date(dateStr).toLocaleDateString(dateLocale, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

onMounted(async () => {
  try {
    // Fetch recent tenders
    await tenderStore.fetchTenders({ per_page: 5 })
    recentTenders.value = tenderStore.tenders.slice(0, 5)

    // Fetch favorites count
    await tenderStore.fetchFavorites()
    stats.value.favorites = tenderStore.favorites.length

    // Fetch stats
    stats.value.activeTenders = tenderStore.pagination.total || tenderStore.tenders.length

    // Fetch applications count
    try {
      const { data } = await api.get('/applications')
      stats.value.applications = data.data?.length || data.meta?.total || 0
    } catch {
      stats.value.applications = 0
    }
  } catch {
    // Silently handle errors
  } finally {
    loadingTenders.value = false
  }
})
</script>
