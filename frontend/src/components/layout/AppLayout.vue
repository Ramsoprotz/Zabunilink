<template>
  <div class="min-h-screen bg-bg flex">
    <!-- Mobile overlay -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 bg-black/30 z-40 lg:hidden"
      @click="sidebarOpen = false"
    />

    <!-- Sidebar -->
    <aside
      :class="[
        'fixed lg:static inset-y-0 left-0 z-50 w-64 bg-surface flex flex-col transition-transform duration-300',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
      ]"
      style="box-shadow: 4px 0 12px rgba(0,0,0,0.08)"
    >
      <!-- Logo -->
      <div class="px-6 py-5 flex items-center gap-3">
        <template v-if="logoUrl">
          <img :src="logoUrl" alt="Logo" class="h-9 max-w-[160px] object-contain" />
        </template>
        <template v-else>
          <div class="w-9 h-9 rounded-xl bg-primary flex items-center justify-center">
            <span class="text-white font-bold text-lg">Z</span>
          </div>
          <span class="text-xl font-bold text-primary tracking-tight">ZabuniLink</span>
        </template>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <router-link
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-3 px-4 py-3 rounded-xl text-text-light hover:text-text transition-all duration-200"
          active-class="neu-pressed !text-primary font-semibold"
          @click="sidebarOpen = false"
        >
          <span class="text-lg" v-html="item.icon" />
          <span class="text-sm">{{ item.label }}</span>
        </router-link>

        <!-- My Business section (Business plan only) -->
        <template v-if="authStore.isBusiness">
          <div class="pt-4 pb-1 px-4">
            <span class="text-[10px] font-bold tracking-widest text-text-light/60 uppercase">{{ $t('nav.my_business') }}</span>
          </div>
          <router-link
            v-for="item in businessNavItems"
            :key="item.to"
            :to="item.to"
            class="flex items-center gap-3 px-4 py-3 rounded-xl text-text-light hover:text-text transition-all duration-200"
            active-class="neu-pressed !text-primary font-semibold"
            @click="sidebarOpen = false"
          >
            <span class="text-lg" v-html="item.icon" />
            <span class="text-sm">{{ item.label }}</span>
          </router-link>
        </template>
      </nav>

      <!-- Logout -->
      <div class="px-3 pb-5">
        <button
          class="neu-btn w-full flex items-center gap-3 px-4 py-3 text-danger text-sm font-medium"
          @click="handleLogout"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          {{ $t('common.logout') }}
        </button>
      </div>
    </aside>

    <!-- Main content area -->
    <div class="flex-1 flex flex-col min-h-screen">
      <!-- Top header -->
      <header class="neu-flat sticky top-0 z-30 mx-4 mt-4 mb-2 px-6 py-3 flex items-center justify-between"
        style="border-radius: 14px">
        <!-- Hamburger -->
        <button class="lg:hidden neu-btn p-2" @click="sidebarOpen = !sidebarOpen">
          <svg class="w-6 h-6 text-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <div class="hidden lg:block" />

        <!-- Right section -->
        <div class="flex items-center gap-4">
          <!-- Subscription badge -->
          <span
            v-if="authStore.isBusiness"
            class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500 text-white"
          >
            BUSINESS
          </span>
          <span
            v-else-if="authStore.isPro"
            class="px-3 py-1 rounded-full text-xs font-bold bg-secondary text-white"
          >
            PRO
          </span>
          <span
            v-else
            class="px-3 py-1 rounded-full text-xs font-medium bg-surface-dark text-text-light"
          >
            Basic
          </span>

          <!-- Language toggle -->
          <button
            class="neu-btn px-3 py-1.5 text-xs font-bold tracking-wide text-text-light"
            @click="toggleLocale"
          >
            {{ locale === 'en' ? 'SW' : 'EN' }}
          </button>

          <!-- Notifications link -->
          <router-link to="/notifications" class="neu-btn p-2 relative">
            <svg class="w-5 h-5 text-text-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
          </router-link>

          <!-- User name -->
          <router-link to="/profile" class="flex items-center gap-2 text-sm">
            <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
              <span class="text-white font-semibold text-xs">
                {{ userInitials }}
              </span>
            </div>
            <span class="hidden sm:inline font-medium text-text">
              {{ authStore.user?.name }}
            </span>
          </router-link>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 p-4 lg:p-6 overflow-y-auto">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'

const router = useRouter()
const authStore = useAuthStore()
const { t, locale } = useI18n()
const sidebarOpen = ref(false)
const logoUrl = ref(null)

onMounted(async () => {
  try {
    const { data } = await api.get('/branding')
    logoUrl.value = data.data?.logo_url || null
  } catch {
    // Keep default branding
  }
})

const userInitials = computed(() => {
  const name = authStore.user?.name || ''
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
})

const navItems = computed(() => [
  { to: '/dashboard', label: t('nav.dashboard'), icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4"/></svg>' },
  { to: '/tenders', label: t('nav.tenders'), icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>' },
  { to: '/favorites', label: t('nav.favorites'), icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>' },
  { to: '/applications', label: t('nav.applications'), icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>' },
  { to: '/subscription', label: t('nav.subscription'), icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>' },
  { to: '/notifications', label: t('nav.notifications'), icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>' },
  { to: '/profile', label: t('nav.profile'), icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>' },
])

const businessNavItems = computed(() => [
  { to: '/business', label: t('nav.biz_dashboard'), icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>' },
  { to: '/business/post', label: t('nav.post_tender'), icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
  { to: '/business/tenders', label: t('nav.my_tenders'), icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>' },
])

function toggleLocale() {
  locale.value = locale.value === 'en' ? 'sw' : 'en'
  localStorage.setItem('locale', locale.value)
}

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>
