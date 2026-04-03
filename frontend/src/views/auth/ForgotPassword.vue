<template>
  <div class="min-h-screen bg-bg flex items-center justify-center px-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <template v-if="logoUrl">
          <img :src="logoUrl" alt="ZabuniLink" class="h-16 max-w-[200px] object-contain mx-auto mb-4" />
        </template>
        <template v-else>
          <div class="w-16 h-16 rounded-2xl bg-primary flex items-center justify-center mx-auto mb-4">
            <span class="text-white font-bold text-2xl">Z</span>
          </div>
          <h1 class="text-2xl font-bold text-primary">ZabuniLink</h1>
        </template>
        <p class="text-text-light text-sm mt-1">{{ $t('auth.forgot_title') }}</p>
      </div>

      <!-- Card -->
      <div class="neu-card">
        <h2 class="text-lg font-semibold text-text mb-2">{{ $t('auth.forgot_title') }}</h2>
        <p class="text-sm text-text-light mb-6">{{ $t('auth.forgot_desc') }}</p>

        <!-- Success -->
        <div v-if="success" class="mb-4 p-3 rounded-xl bg-success/10 text-success text-sm">
          {{ $t('auth.code_sent') }}
        </div>

        <!-- Error -->
        <div v-if="error" class="mb-4 p-3 rounded-xl bg-danger/10 text-danger text-sm">
          {{ error }}
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-5">
          <div>
            <label class="block text-sm font-medium text-text-light mb-2">{{ $t('auth.email') }}</label>
            <input
              v-model="email"
              type="email"
              class="neu-input"
              placeholder="you@example.com"
              required
            />
          </div>

          <button
            type="submit"
            class="neu-btn-primary w-full py-3 rounded-xl font-semibold text-sm"
            :disabled="loading"
          >
            {{ loading ? $t('auth.sending_code') : $t('auth.send_code') }}
          </button>
        </form>

        <div class="flex items-center justify-between mt-6 text-sm">
          <router-link to="/login" class="text-primary font-semibold hover:underline">
            {{ $t('auth.back_to_login') }}
          </router-link>
          <router-link to="/reset-password" class="text-text-light hover:text-text">
            {{ $t('auth.have_code') }}
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../../services/api'

const { t } = useI18n()

const email = ref('')
const loading = ref(false)
const error = ref('')
const success = ref(false)
const logoUrl = ref(null)

onMounted(async () => {
  try {
    const { data } = await api.get('/branding')
    logoUrl.value = data.data?.logo_url || null
  } catch {
    // Keep default
  }
})

async function handleSubmit() {
  error.value = ''
  success.value = false
  loading.value = true
  try {
    await api.post('/forgot-password', { email: email.value })
    success.value = true
  } catch (err) {
    error.value = err.response?.data?.message || 'Something went wrong. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>
