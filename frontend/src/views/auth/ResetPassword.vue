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
        <p class="text-text-light text-sm mt-1">{{ $t('auth.reset_title') }}</p>
      </div>

      <!-- Card -->
      <div class="neu-card">
        <h2 class="text-lg font-semibold text-text mb-2">{{ $t('auth.reset_title') }}</h2>
        <p class="text-sm text-text-light mb-6">{{ $t('auth.reset_desc') }}</p>

        <!-- Success -->
        <div v-if="success" class="mb-4 p-3 rounded-xl bg-success/10 text-success text-sm">
          {{ $t('auth.reset_success') }}
          <router-link to="/login" class="block mt-2 text-primary font-semibold hover:underline">
            {{ $t('auth.back_to_login') }}
          </router-link>
        </div>

        <!-- Error -->
        <div v-if="error" class="mb-4 p-3 rounded-xl bg-danger/10 text-danger text-sm">
          {{ error }}
        </div>

        <form v-if="!success" @submit.prevent="handleReset" class="space-y-5">
          <div>
            <label class="block text-sm font-medium text-text-light mb-2">{{ $t('auth.email') }}</label>
            <input
              v-model="form.email"
              type="email"
              class="neu-input"
              placeholder="you@example.com"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-text-light mb-2">{{ $t('auth.reset_code') }}</label>
            <input
              v-model="form.otp"
              type="text"
              inputmode="numeric"
              maxlength="6"
              class="neu-input text-center text-2xl tracking-[0.5em] font-bold"
              placeholder="000000"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-text-light mb-2">{{ $t('auth.new_password') }}</label>
            <input
              v-model="form.password"
              type="password"
              class="neu-input"
              placeholder="At least 8 characters"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-text-light mb-2">{{ $t('auth.confirm_new_password') }}</label>
            <input
              v-model="form.password_confirmation"
              type="password"
              class="neu-input"
              placeholder="Repeat your new password"
              required
            />
          </div>

          <button
            type="submit"
            class="neu-btn-primary w-full py-3 rounded-xl font-semibold text-sm"
            :disabled="loading"
          >
            {{ loading ? $t('auth.resetting') : $t('auth.reset_password') }}
          </button>
        </form>

        <div class="flex items-center justify-between mt-6 text-sm">
          <router-link to="/login" class="text-primary font-semibold hover:underline">
            {{ $t('auth.back_to_login') }}
          </router-link>
          <router-link to="/forgot-password" class="text-text-light hover:text-text">
            {{ $t('auth.send_code') }}
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../../services/api'

const { t } = useI18n()

const form = reactive({
  email: '',
  otp: '',
  password: '',
  password_confirmation: '',
})
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

async function handleReset() {
  error.value = ''

  if (form.password !== form.password_confirmation) {
    error.value = 'Passwords do not match.'
    return
  }

  loading.value = true
  try {
    await api.post('/reset-password', {
      email: form.email,
      otp: form.otp,
      password: form.password,
      password_confirmation: form.password_confirmation,
    })
    success.value = true
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to reset password. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>
