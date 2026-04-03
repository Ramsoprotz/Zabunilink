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
        <p class="text-text-light text-sm mt-1">{{ $t('auth.tagline') }}</p>
      </div>

      <!-- Login card -->
      <div class="neu-card">
        <h2 class="text-lg font-semibold text-text mb-6">{{ $t('auth.sign_in_account') }}</h2>

        <!-- Error message -->
        <div
          v-if="error"
          class="mb-4 p-3 rounded-xl bg-danger/10 text-danger text-sm"
        >
          {{ error }}
        </div>

        <form @submit.prevent="handleLogin" class="space-y-5">
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
            <label class="block text-sm font-medium text-text-light mb-2">{{ $t('auth.password') }}</label>
            <input
              v-model="form.password"
              type="password"
              class="neu-input"
              :placeholder="$t('auth.enter_password')"
              required
            />
          </div>

          <div class="text-right">
            <router-link to="/forgot-password" class="text-sm text-primary hover:underline">
              {{ $t('auth.forgot_password') }}
            </router-link>
          </div>

          <button
            type="submit"
            class="neu-btn-primary w-full py-3 rounded-xl font-semibold text-sm transition-all duration-200"
            :disabled="loading"
          >
            <span v-if="loading">{{ $t('auth.signing_in') }}</span>
            <span v-else>{{ $t('auth.sign_in') }}</span>
          </button>
        </form>

        <p class="text-center text-sm text-text-light mt-6">
          {{ $t('auth.no_account') }}
          <router-link to="/register" class="text-primary font-semibold hover:underline">
            {{ $t('auth.register') }}
          </router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useI18n } from 'vue-i18n'
import api from '../../services/api'

const { t } = useI18n()
const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  email: '',
  password: '',
})
const loading = ref(false)
const error = ref('')
const logoUrl = ref(null)

onMounted(async () => {
  try {
    const { data } = await api.get('/branding')
    logoUrl.value = data.data?.logo_url || null
  } catch {
    // Keep default branding
  }
})

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await authStore.login(form)
    router.push('/dashboard')
  } catch (err) {
    error.value = err.response?.data?.message || t('auth.invalid_credentials')
  } finally {
    loading.value = false
  }
}
</script>
