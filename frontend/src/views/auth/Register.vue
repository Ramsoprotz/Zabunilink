<template>
  <div class="min-h-screen bg-bg flex items-center justify-center px-4 py-8">
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
        <p class="text-text-light text-sm mt-1">{{ $t('auth.create_account') }}</p>
      </div>

      <!-- Register card -->
      <div class="neu-card">
        <h2 class="text-lg font-semibold text-text mb-6">{{ $t('auth.register') }}</h2>

        <!-- Error message -->
        <div
          v-if="error"
          class="mb-4 p-3 rounded-xl bg-danger/10 text-danger text-sm"
        >
          {{ error }}
        </div>

        <form @submit.prevent="handleRegister" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-text-light mb-2">{{ $t('auth.full_name') }}</label>
            <input
              v-model="form.name"
              type="text"
              class="neu-input"
              placeholder="John Doe"
              required
            />
          </div>

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
            <label class="block text-sm font-medium text-text-light mb-2">{{ $t('auth.phone') }}</label>
            <input
              v-model="form.phone"
              type="tel"
              class="neu-input"
              placeholder="+255 7XX XXX XXX"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-text-light mb-2">{{ $t('auth.business_name') }}</label>
            <input
              v-model="form.business_name"
              type="text"
              class="neu-input"
              placeholder="Your company name"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-text-light mb-2">{{ $t('auth.password') }}</label>
            <input
              v-model="form.password"
              type="password"
              class="neu-input"
              placeholder="At least 8 characters"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-text-light mb-2">{{ $t('auth.confirm_password') }}</label>
            <input
              v-model="form.password_confirmation"
              type="password"
              class="neu-input"
              placeholder="Repeat your password"
              required
            />
          </div>

          <button
            type="submit"
            class="neu-btn-primary w-full py-3 rounded-xl font-semibold text-sm transition-all duration-200"
            :disabled="loading"
          >
            <span v-if="loading">{{ $t('auth.creating_account') }}</span>
            <span v-else>{{ $t('auth.create_account') }}</span>
          </button>
        </form>

        <p class="text-center text-sm text-text-light mt-6">
          {{ $t('auth.already_account') }}
          <router-link to="/login" class="text-primary font-semibold hover:underline">
            {{ $t('auth.sign_in_link') }}
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
  name: '',
  email: '',
  phone: '',
  business_name: '',
  password: '',
  password_confirmation: '',
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

async function handleRegister() {
  error.value = ''

  if (form.password !== form.password_confirmation) {
    error.value = 'Passwords do not match.'
    return
  }

  loading.value = true
  try {
    await authStore.register(form)
    router.push('/dashboard')
  } catch (err) {
    const data = err.response?.data
    if (data?.errors) {
      error.value = Object.values(data.errors).flat().join(' ')
    } else {
      error.value = data?.message || 'Registration failed. Please try again.'
    }
  } finally {
    loading.value = false
  }
}
</script>
