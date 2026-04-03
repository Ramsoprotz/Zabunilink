<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-text">{{ $t('notifications.title') }}</h1>
      <p class="text-text-light text-sm mt-1">{{ $t('notifications.subtitle') }}</p>
    </div>

    <!-- Preferences section -->
    <div class="neu-card">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-semibold text-text uppercase tracking-wide">{{ $t('notifications.preferences') }}</h2>
        <button
          class="neu-btn px-4 py-2 text-xs text-primary font-medium"
          :class="showPreferences ? 'neu-btn-active' : ''"
          @click="showPreferences = !showPreferences"
        >
          {{ showPreferences ? 'Hide' : 'Settings' }}
        </button>
      </div>

      <div v-if="showPreferences" class="space-y-4">
        <!-- Notification Language -->
        <div>
          <label class="block text-xs font-medium text-text-light mb-2">{{ $t('notifications.language') }}</label>
          <div class="flex gap-2">
            <button
              class="neu-btn px-4 py-2 text-sm font-medium"
              :class="preferences.locale === 'en' ? 'neu-btn-primary text-white' : ''"
              @click="preferences.locale = 'en'"
            >
              English
            </button>
            <button
              class="neu-btn px-4 py-2 text-sm font-medium"
              :class="preferences.locale === 'sw' ? 'neu-btn-primary text-white' : ''"
              @click="preferences.locale = 'sw'"
            >
              Kiswahili
            </button>
          </div>
          <p class="text-xs text-text-light mt-1">{{ $t('notifications.language_hint') }}</p>
        </div>

        <!-- Channel toggles -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div
            v-for="channel in channels"
            :key="channel.key"
            class="neu-card-sm flex items-center justify-between cursor-pointer"
            @click="togglePreference(channel.key)"
          >
            <div class="flex items-center gap-2">
              <span v-html="channel.icon" />
              <span class="text-sm font-medium text-text">{{ channel.label }}</span>
            </div>
            <div
              class="w-10 h-6 rounded-full transition-colors duration-200 relative"
              :class="preferences[channel.key] ? 'bg-primary' : 'bg-surface-dark'"
            >
              <div
                class="w-4 h-4 rounded-full bg-white absolute top-1 transition-all duration-200"
                :class="preferences[channel.key] ? 'left-5' : 'left-1'"
              />
            </div>
          </div>
        </div>

        <!-- Category and location selectors -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs text-text-light mb-2">Preferred Categories</label>
            <select v-model="preferences.categories" class="neu-input text-sm" multiple>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-xs text-text-light mb-2">Preferred Locations</label>
            <select v-model="preferences.locations" class="neu-input text-sm" multiple>
              <option v-for="loc in locations" :key="loc.id" :value="loc.id">
                {{ loc.name }}
              </option>
            </select>
          </div>
        </div>

        <button
          class="neu-btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold"
          :disabled="savingPrefs"
          @click="savePreferences"
        >
          {{ savingPrefs ? $t('common.processing') : $t('notifications.save_preferences') }}
        </button>

        <p v-if="prefSuccess" class="text-sm text-success">Preferences saved successfully.</p>
        <p v-if="prefError" class="text-sm text-danger">{{ prefError }}</p>
      </div>
    </div>

    <!-- Mark all as read -->
    <div class="flex items-center justify-between">
      <h2 class="text-sm font-semibold text-text uppercase tracking-wide">Recent Notifications</h2>
      <button
        v-if="notifications.length"
        class="neu-btn px-3 py-1.5 text-xs text-primary font-medium"
        @click="markAllRead"
      >
        {{ $t('notifications.mark_read') }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12 text-text-light">{{ $t('notifications.loading') }}</div>

    <!-- Notification list -->
    <div v-else-if="notifications.length" class="space-y-3">
      <div
        v-for="notif in notifications"
        :key="notif.id"
        class="neu-card-sm flex items-start gap-3 cursor-pointer transition-all duration-200"
        :class="!notif.read_at ? 'ring-1 ring-primary/20' : 'opacity-75'"
        @click="markAsRead(notif)"
      >
        <!-- Icon -->
        <div
          class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
          :class="notifIconClass(notif.type)"
        >
          <svg v-if="notif.type === 'tender'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <svg v-else-if="notif.type === 'application'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          <svg v-else-if="notif.type === 'subscription'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
          <p class="text-sm text-text" :class="!notif.read_at ? 'font-semibold' : ''">
            {{ notif.message || notif.data?.message || 'Notification' }}
          </p>
          <p class="text-xs text-text-light mt-1">{{ formatTimeAgo(notif.created_at) }}</p>
        </div>

        <!-- Unread dot -->
        <div v-if="!notif.read_at" class="w-2 h-2 rounded-full bg-primary flex-shrink-0 mt-2" />
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="neu-card text-center py-16">
      <svg class="w-16 h-16 mx-auto text-text-light mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
      </svg>
      <h3 class="text-lg font-semibold text-text mb-1">{{ $t('notifications.no_notifications') }}</h3>
      <p class="text-text-light text-sm">{{ $t('notifications.subtitle') }}</p>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="flex items-center justify-center gap-2">
      <button class="neu-btn px-4 py-2 text-sm" :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">
        Previous
      </button>
      <span class="text-sm text-text-light px-3">Page {{ currentPage }} of {{ pagination.last_page }}</span>
      <button class="neu-btn px-4 py-2 text-sm" :disabled="currentPage >= pagination.last_page" @click="goToPage(currentPage + 1)">
        Next
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../../services/api'

const { t } = useI18n()

const notifications = ref([])
const pagination = ref({})
const currentPage = ref(1)
const categories = ref([])
const locations = ref([])
const loading = ref(true)
const showPreferences = ref(false)
const savingPrefs = ref(false)
const prefSuccess = ref(false)
const prefError = ref('')

const preferences = reactive({
  email: true,
  sms: false,
  push: true,
  locale: 'en',
  categories: [],
  locations: [],
})

const channels = [
  { key: 'email', label: t('notifications.email'), icon: '<svg class="w-4 h-4 text-text-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>' },
  { key: 'sms', label: t('notifications.sms'), icon: '<svg class="w-4 h-4 text-text-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>' },
  { key: 'push', label: t('notifications.push'), icon: '<svg class="w-4 h-4 text-text-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>' },
]

function togglePreference(key) {
  preferences[key] = !preferences[key]
}

function notifIconClass(type) {
  const map = {
    tender: 'bg-primary/10 text-primary',
    application: 'bg-info/10 text-info',
    subscription: 'bg-secondary/10 text-secondary',
  }
  return map[type] || 'bg-text-light/10 text-text-light'
}

function formatTimeAgo(dateStr) {
  if (!dateStr) return ''
  const diff = Date.now() - new Date(dateStr).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'Just now'
  if (mins < 60) return `${mins}m ago`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours}h ago`
  const days = Math.floor(hours / 24)
  if (days < 7) return `${days}d ago`
  return new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })
}

async function savePreferences() {
  savingPrefs.value = true
  prefError.value = ''
  prefSuccess.value = false
  try {
    await api.put('/notification-preferences', {
      email_enabled: preferences.email,
      sms_enabled: preferences.sms,
      push_enabled: preferences.push,
      locale: preferences.locale,
      category_ids: preferences.categories,
      location_ids: preferences.locations,
    })
    prefSuccess.value = true
    setTimeout(() => { prefSuccess.value = false }, 3000)
  } catch {
    prefError.value = 'Failed to save preferences.'
  } finally {
    savingPrefs.value = false
  }
}

async function markAsRead(notif) {
  if (notif.read_at) return
  try {
    await api.put(`/notifications/${notif.id}/read`)
    notif.read_at = new Date().toISOString()
  } catch {
    // Silently handle
  }
}

async function markAllRead() {
  try {
    await api.put('/notifications/read-all')
    notifications.value.forEach(n => {
      n.read_at = n.read_at || new Date().toISOString()
    })
  } catch {
    // Silently handle
  }
}

async function fetchNotifications(page = 1) {
  const { data } = await api.get('/notifications', { params: { page, per_page: 15 } })
  notifications.value = data.data || []
  pagination.value = data.meta || {}
  currentPage.value = page
}

async function goToPage(page) {
  loading.value = true
  try {
    await fetchNotifications(page)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  try {
    const [, catsRes, locsRes] = await Promise.all([
      fetchNotifications(),
      api.get('/categories').catch(() => ({ data: { data: [] } })),
      api.get('/locations').catch(() => ({ data: { data: [] } })),
    ])
    categories.value = catsRes.data.data || []
    locations.value = locsRes.data.data || []

    // Load existing preferences
    try {
      const { data } = await api.get('/notification-preferences')
      if (data.data) {
        preferences.email = data.data.email_enabled ?? true
        preferences.sms = data.data.sms_enabled ?? false
        preferences.push = data.data.push_enabled ?? true
        preferences.locale = data.data.locale ?? 'en'
        preferences.categories = data.data.category_ids || []
        preferences.locations = data.data.location_ids || []
      }
    } catch {
      // Use defaults
    }
  } finally {
    loading.value = false
  }
})
</script>
