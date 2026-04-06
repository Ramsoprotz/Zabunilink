<template>
  <!-- Offline banner -->
  <transition name="slide-down">
    <div
      v-if="!isOnline"
      class="fixed top-0 inset-x-0 z-[100] bg-red-600 text-white text-center py-2 px-4 text-sm font-medium shadow-lg flex items-center justify-center gap-2"
    >
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M18.364 5.636a9 9 0 010 12.728M5.636 18.364a9 9 0 010-12.728M12 9v4m0 4h.01" />
      </svg>
      {{ $t('common.offline') }}
    </div>
  </transition>

  <!-- Back-online toast -->
  <transition name="slide-down">
    <div
      v-if="showReconnected"
      class="fixed top-0 inset-x-0 z-[100] bg-green-600 text-white text-center py-2 px-4 text-sm font-medium shadow-lg flex items-center justify-center gap-2"
    >
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
      </svg>
      {{ $t('common.back_online') }}
    </div>
  </transition>

  <router-view />
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const isOnline = ref(navigator.onLine)
const showReconnected = ref(false)
let reconnectTimer = null

function handleOnline() {
  isOnline.value = true
  showReconnected.value = true
  clearTimeout(reconnectTimer)
  reconnectTimer = setTimeout(() => {
    showReconnected.value = false
  }, 3000)
}

function handleOffline() {
  isOnline.value = false
  showReconnected.value = false
}

onMounted(() => {
  window.addEventListener('online', handleOnline)
  window.addEventListener('offline', handleOffline)
})

onUnmounted(() => {
  window.removeEventListener('online', handleOnline)
  window.removeEventListener('offline', handleOffline)
  clearTimeout(reconnectTimer)
})
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
  transform: translateY(-100%);
  opacity: 0;
}
</style>
