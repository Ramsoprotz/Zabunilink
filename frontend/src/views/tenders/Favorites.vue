<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-text">{{ $t('favorites.title') }}</h1>
      <p class="text-text-light text-sm mt-1">{{ $t('favorites.subtitle') }}</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12 text-text-light">{{ $t('common.loading') }}</div>

    <!-- Favorites list -->
    <div v-else-if="tenderStore.favorites.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
      <div
        v-for="fav in tenderStore.favorites"
        :key="fav.id"
        class="neu-card hover:scale-[1.01] transition-transform duration-200 cursor-pointer relative"
        @click="$router.push(`/tenders/${fav.tender?.id || fav.tender_id}`)"
      >
        <!-- Remove favorite -->
        <button
          class="absolute top-4 right-4 p-1.5 rounded-full hover:bg-danger/10 transition-colors z-10"
          :title="$t('favorites.remove')"
          @click.stop="removeFavorite(fav.tender?.id || fav.tender_id)"
        >
          <svg class="w-5 h-5 text-danger fill-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
          </svg>
        </button>

        <div class="flex items-center gap-2 mb-3">
          <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
            {{ fav.tender?.category?.name || $t('tenders.general') }}
          </span>
          <span
            class="px-2 py-0.5 rounded-full text-xs font-medium"
            :class="fav.tender?.type === 'government' ? 'bg-info/10 text-info' : 'bg-accent/10 text-accent'"
          >
            {{ fav.tender?.type === 'government' ? $t('tenders.government') : $t('tenders.private') }}
          </span>
        </div>

        <h3 class="font-semibold text-text text-sm line-clamp-2 mb-1 pr-8">{{ fav.tender?.title || 'Untitled Tender' }}</h3>
        <p class="text-xs text-text-light mb-4">{{ fav.tender?.organization }}</p>

        <div class="flex items-center justify-between text-xs text-text-light pt-3 border-t border-surface-dark/30">
          <span>{{ fav.tender?.location?.name || $t('common.tanzania') }}</span>
          <span class="text-danger font-medium">
            {{ $t('favorites.deadline') }} {{ formatDate(fav.tender?.deadline) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="neu-card text-center py-16">
      <svg class="w-16 h-16 mx-auto text-text-light mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
      </svg>
      <h3 class="text-lg font-semibold text-text mb-1">{{ $t('favorites.no_favorites') }}</h3>
      <p class="text-text-light text-sm mb-4">{{ $t('favorites.no_favorites_desc') }}</p>
      <router-link to="/tenders" class="neu-btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold inline-block">
        {{ $t('favorites.browse') }}
      </router-link>
    </div>

    <!-- Pagination -->
    <div v-if="tenderStore.favoritesPagination.last_page > 1" class="flex items-center justify-center gap-2">
      <button class="neu-btn px-4 py-2 text-sm" :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">
        Previous
      </button>
      <span class="text-sm text-text-light px-3">Page {{ currentPage }} of {{ tenderStore.favoritesPagination.last_page }}</span>
      <button class="neu-btn px-4 py-2 text-sm" :disabled="currentPage >= tenderStore.favoritesPagination.last_page" @click="goToPage(currentPage + 1)">
        Next
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTenderStore } from '../../stores/tenders'

const { t, locale } = useI18n()
const tenderStore = useTenderStore()
const loading = ref(true)
const currentPage = ref(1)

function formatDate(dateStr) {
  if (!dateStr) return t('common.na')
  const dateLocale = locale.value === 'sw' ? 'sw-TZ' : 'en-GB'
  return new Date(dateStr).toLocaleDateString(dateLocale, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

async function removeFavorite(tenderId) {
  await tenderStore.toggleFavorite(tenderId)
}

async function goToPage(page) {
  currentPage.value = page
  loading.value = true
  try {
    await tenderStore.fetchFavorites(page)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  try {
    await tenderStore.fetchFavorites()
  } finally {
    loading.value = false
  }
})
</script>
