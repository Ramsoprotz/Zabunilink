<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-text">{{ $t('tenders.title') }}</h1>
      <p class="text-text-light text-sm mt-1">{{ $t('tenders.subtitle') }}</p>
    </div>

    <!-- Search & Filters -->
    <div class="neu-card space-y-4">
      <!-- Search bar -->
      <div class="relative flex items-center">
        <svg class="absolute right-4 w-5 h-5 text-text-light pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="filters.search"
          type="text"
          class="neu-input pr-12"
          :placeholder="$t('tenders.search_placeholder')"
          @input="debouncedSearch"
        />
      </div>

      <!-- Filter row -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <select v-model="filters.category_id" class="neu-input text-sm" @change="applyFilters">
          <option value="">{{ $t('tenders.all_categories') }}</option>
          <option v-for="cat in tenderStore.categories" :key="cat.id" :value="cat.id">
            {{ cat.name }}
          </option>
        </select>

        <select v-model="filters.location_id" class="neu-input text-sm" @change="applyFilters">
          <option value="">{{ $t('tenders.all_locations') }}</option>
          <option v-for="loc in tenderStore.locations" :key="loc.id" :value="loc.id">
            {{ loc.name }}
          </option>
        </select>

        <select v-model="filters.type" class="neu-input text-sm" @change="applyFilters">
          <option value="">{{ $t('tenders.all_types') }}</option>
          <option value="government">{{ $t('tenders.government') }}</option>
          <option value="private">{{ $t('tenders.private') }}</option>
        </select>

        <input
          v-model="filters.month"
          type="month"
          class="neu-input text-sm"
          @change="applyFilters"
        />
      </div>
    </div>

    <!-- Loading -->
    <div v-if="tenderStore.loading" class="text-center py-12 text-text-light">
      {{ $t('tenders.loading') }}
    </div>

    <!-- Tender grid -->
    <div v-else-if="tenderStore.tenders.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
      <div
        v-for="tender in tenderStore.tenders"
        :key="tender.id"
        class="neu-card hover:scale-[1.01] transition-transform duration-200 cursor-pointer relative"
        @click="$router.push(`/tenders/${tender.id}`)"
      >
        <!-- Favorite button -->
        <button
          class="absolute top-4 right-4 p-1.5 rounded-full hover:bg-danger/10 transition-colors z-10"
          @click.stop="toggleFav(tender.id)"
        >
          <svg
            class="w-5 h-5 transition-colors"
            :class="tenderStore.isFavorited(tender.id) ? 'text-danger fill-danger' : 'text-text-light'"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
          </svg>
        </button>

        <!-- Badges -->
        <div class="flex items-center gap-2 mb-3">
          <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
            {{ tender.category?.name || $t('tenders.general') }}
          </span>
          <span
            class="px-2 py-0.5 rounded-full text-xs font-medium"
            :class="tender.type === 'government' ? 'bg-info/10 text-info' : 'bg-accent/10 text-accent'"
          >
            {{ tender.type === 'government' ? $t('tenders.government') : $t('tenders.private') }}
          </span>
        </div>

        <!-- Content -->
        <h3 class="font-semibold text-text text-sm line-clamp-2 mb-1 pr-8">{{ tender.title }}</h3>
        <p class="text-xs text-text-light mb-4">{{ tender.organization }}</p>

        <!-- Footer -->
        <div class="flex items-center justify-between text-xs text-text-light pt-3 border-t border-surface-dark/30">
          <div class="flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            {{ tender.location?.name || $t('common.tanzania') }}
          </div>
          <span class="text-danger font-medium">
            Due: {{ formatDate(tender.deadline) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="neu-card text-center py-12">
      <svg class="w-12 h-12 mx-auto text-text-light mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="text-text-light">{{ $t('tenders.no_tenders') }}</p>
      <button class="neu-btn px-4 py-2 text-sm text-primary font-medium mt-4" @click="clearFilters">
        {{ $t('common.filter') }}
      </button>
    </div>

    <!-- Pagination -->
    <div
      v-if="tenderStore.pagination.last_page > 1"
      class="flex items-center justify-center gap-2"
    >
      <button
        class="neu-btn px-4 py-2 text-sm"
        :disabled="tenderStore.pagination.current_page <= 1"
        @click="goToPage(tenderStore.pagination.current_page - 1)"
      >
        Previous
      </button>
      <span class="text-sm text-text-light px-3">
        Page {{ tenderStore.pagination.current_page }} of {{ tenderStore.pagination.last_page }}
      </span>
      <button
        class="neu-btn px-4 py-2 text-sm"
        :disabled="tenderStore.pagination.current_page >= tenderStore.pagination.last_page"
        @click="goToPage(tenderStore.pagination.current_page + 1)"
      >
        Next
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTenderStore } from '../../stores/tenders'

const { t, locale } = useI18n()
const tenderStore = useTenderStore()

const filters = reactive({
  search: '',
  category_id: '',
  location_id: '',
  type: '',
  month: '',
  page: 1,
})

let searchTimeout = null

function formatDate(dateStr) {
  if (!dateStr) return t('common.na')
  const dateLocale = locale.value === 'sw' ? 'sw-TZ' : 'en-GB'
  return new Date(dateStr).toLocaleDateString(dateLocale, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    filters.page = 1
    applyFilters()
  }, 400)
}

function applyFilters() {
  const params = {}
  if (filters.search) params.search = filters.search
  if (filters.category_id) params.category_id = filters.category_id
  if (filters.location_id) params.location_id = filters.location_id
  if (filters.type) params.type = filters.type
  if (filters.month) params.month = filters.month
  params.page = filters.page
  tenderStore.fetchTenders(params)
}

function clearFilters() {
  filters.search = ''
  filters.category_id = ''
  filters.location_id = ''
  filters.type = ''
  filters.month = ''
  filters.page = 1
  applyFilters()
}

function goToPage(page) {
  filters.page = page
  applyFilters()
}

async function toggleFav(tenderId) {
  await tenderStore.toggleFavorite(tenderId)
}

onMounted(async () => {
  await Promise.all([
    tenderStore.fetchTenders(),
    tenderStore.fetchCategories(),
    tenderStore.fetchLocations(),
    tenderStore.fetchFavorites(),
  ])
})
</script>
