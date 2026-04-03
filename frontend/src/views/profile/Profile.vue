<template>
  <div class="space-y-6 max-w-2xl">
    <div>
      <h1 class="text-2xl font-bold text-text">{{ $t('profile.title') }}</h1>
      <p class="text-text-light text-sm mt-1">{{ $t('profile.subtitle') }}</p>
    </div>

    <!-- Profile form -->
    <div class="neu-card">
      <h2 class="text-sm font-semibold text-text uppercase tracking-wide mb-4">Personal Information</h2>

      <div v-if="profileError" class="mb-4 p-3 rounded-xl bg-danger/10 text-danger text-sm">
        {{ profileError }}
      </div>
      <div v-if="profileSuccess" class="mb-4 p-3 rounded-xl bg-success/10 text-success text-sm">
        {{ profileSuccess }}
      </div>

      <form @submit.prevent="handleUpdateProfile" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-text-light mb-2">{{ $t('profile.full_name') }}</label>
          <input v-model="profileForm.name" type="text" class="neu-input" required />
        </div>

        <div>
          <label class="block text-sm font-medium text-text-light mb-2">{{ $t('profile.email') }}</label>
          <input v-model="profileForm.email" type="email" class="neu-input" required />
        </div>

        <div>
          <label class="block text-sm font-medium text-text-light mb-2">{{ $t('profile.phone') }}</label>
          <input v-model="profileForm.phone" type="tel" class="neu-input" placeholder="+255 7XX XXX XXX" />
        </div>

        <div>
          <label class="block text-sm font-medium text-text-light mb-2">{{ $t('profile.business_name') }}</label>
          <input v-model="profileForm.business_name" type="text" class="neu-input" />
        </div>

        <button
          type="submit"
          class="neu-btn-primary px-6 py-3 rounded-xl text-sm font-semibold"
          :disabled="savingProfile"
        >
          {{ savingProfile ? $t('profile.saving') : $t('profile.save_changes') }}
        </button>
      </form>
    </div>

    <!-- Password change -->
    <div class="neu-card">
      <h2 class="text-sm font-semibold text-text uppercase tracking-wide mb-4">Change Password</h2>

      <div v-if="passwordError" class="mb-4 p-3 rounded-xl bg-danger/10 text-danger text-sm">
        {{ passwordError }}
      </div>
      <div v-if="passwordSuccess" class="mb-4 p-3 rounded-xl bg-success/10 text-success text-sm">
        {{ passwordSuccess }}
      </div>

      <form @submit.prevent="handleChangePassword" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-text-light mb-2">Current Password</label>
          <input v-model="passwordForm.current_password" type="password" class="neu-input" required />
        </div>

        <div>
          <label class="block text-sm font-medium text-text-light mb-2">New Password</label>
          <input v-model="passwordForm.password" type="password" class="neu-input" required />
        </div>

        <div>
          <label class="block text-sm font-medium text-text-light mb-2">Confirm New Password</label>
          <input v-model="passwordForm.password_confirmation" type="password" class="neu-input" required />
        </div>

        <button
          type="submit"
          class="neu-btn-primary px-6 py-3 rounded-xl text-sm font-semibold"
          :disabled="savingPassword"
        >
          {{ savingPassword ? $t('profile.saving') : 'Update Password' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'

const { t } = useI18n()
const authStore = useAuthStore()

const profileForm = reactive({
  name: '',
  email: '',
  phone: '',
  business_name: '',
})

const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const savingProfile = ref(false)
const profileError = ref('')
const profileSuccess = ref('')

const savingPassword = ref(false)
const passwordError = ref('')
const passwordSuccess = ref('')

function loadProfile() {
  const user = authStore.user
  if (user) {
    profileForm.name = user.name || ''
    profileForm.email = user.email || ''
    profileForm.phone = user.phone || ''
    profileForm.business_name = user.business_name || ''
  }
}

async function handleUpdateProfile() {
  profileError.value = ''
  profileSuccess.value = ''
  savingProfile.value = true
  try {
    await authStore.updateProfile({ ...profileForm })
    profileSuccess.value = t('profile.saved')
    setTimeout(() => { profileSuccess.value = '' }, 3000)
  } catch (err) {
    const data = err.response?.data
    if (data?.errors) {
      profileError.value = Object.values(data.errors).flat().join(' ')
    } else {
      profileError.value = data?.message || 'Failed to update profile.'
    }
  } finally {
    savingProfile.value = false
  }
}

async function handleChangePassword() {
  passwordError.value = ''
  passwordSuccess.value = ''

  if (passwordForm.password !== passwordForm.password_confirmation) {
    passwordError.value = 'Passwords do not match.'
    return
  }

  savingPassword.value = true
  try {
    await api.put('/password', { ...passwordForm })
    passwordSuccess.value = 'Password updated successfully.'
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
    setTimeout(() => { passwordSuccess.value = '' }, 3000)
  } catch (err) {
    const data = err.response?.data
    if (data?.errors) {
      passwordError.value = Object.values(data.errors).flat().join(' ')
    } else {
      passwordError.value = data?.message || 'Failed to update password.'
    }
  } finally {
    savingPassword.value = false
  }
}

onMounted(() => {
  loadProfile()
})
</script>
