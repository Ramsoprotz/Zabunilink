<template>
  <div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-text">{{ $t('business.post_title') }}</h1>
      <p class="text-sm text-text-light mt-1">{{ $t('business.post_subtitle') }}</p>
    </div>

    <!-- Success message -->
    <div v-if="successMsg" class="neu-card-sm p-4 border-l-4 border-primary bg-primary/5 flex items-center gap-3">
      <svg class="w-5 h-5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
      </svg>
      <span class="text-sm text-primary font-medium">{{ successMsg }}</span>
    </div>

    <!-- Error message -->
    <div v-if="errorMsg" class="neu-card-sm p-4 border-l-4 border-red-500 bg-red-50 flex items-center gap-3">
      <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <span class="text-sm text-red-600">{{ errorMsg }}</span>
    </div>

    <form class="neu-card p-6 space-y-5" @submit.prevent="submitForm">
      <!-- Title -->
      <div>
        <label class="block text-sm font-medium text-text mb-1.5">{{ $t('business.tender_title') }} <span class="text-red-500">*</span></label>
        <input
          v-model="form.title"
          type="text"
          class="neu-input w-full"
          :placeholder="$t('business.tender_title_ph')"
          required
        />
        <p v-if="errors.title" class="text-xs text-red-500 mt-1">{{ errors.title }}</p>
      </div>

      <!-- Organization -->
      <div>
        <label class="block text-sm font-medium text-text mb-1.5">{{ $t('business.organization') }} <span class="text-red-500">*</span></label>
        <input
          v-model="form.organization"
          type="text"
          class="neu-input w-full"
          :placeholder="$t('business.organization_ph')"
          required
        />
        <p v-if="errors.organization" class="text-xs text-red-500 mt-1">{{ errors.organization }}</p>
      </div>

      <!-- Description -->
      <div>
        <label class="block text-sm font-medium text-text mb-1.5">{{ $t('business.description') }} <span class="text-red-500">*</span></label>
        <textarea
          v-model="form.description"
          class="neu-input w-full resize-none"
          rows="6"
          :placeholder="$t('business.description_ph')"
          required
        />
        <p v-if="errors.description" class="text-xs text-red-500 mt-1">{{ errors.description }}</p>
      </div>

      <!-- Category & Location row -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-text mb-1.5">{{ $t('business.category') }} <span class="text-red-500">*</span></label>
          <select v-model="form.category_id" class="neu-select w-full" required>
            <option value="">{{ $t('business.select_category') }}</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
          <p v-if="errors.category_id" class="text-xs text-red-500 mt-1">{{ errors.category_id }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-text mb-1.5">{{ $t('business.location') }} <span class="text-red-500">*</span></label>
          <select v-model="form.location_id" class="neu-select w-full" required>
            <option value="">{{ $t('business.select_location') }}</option>
            <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
          </select>
          <p v-if="errors.location_id" class="text-xs text-red-500 mt-1">{{ errors.location_id }}</p>
        </div>
      </div>

      <!-- Type & Value row -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-text mb-1.5">{{ $t('business.tender_type') }} <span class="text-red-500">*</span></label>
          <select v-model="form.type" class="neu-select w-full" required>
            <option value="business">{{ $t('tenders.business') }}</option>
            <option value="government">{{ $t('tenders.government') }}</option>
            <option value="private">{{ $t('tenders.private') }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-text mb-1.5">{{ $t('business.value') }} <span class="text-text-light font-normal text-xs">(optional)</span></label>
          <input
            v-model="form.value"
            type="number"
            class="neu-input w-full"
            placeholder="e.g. 5000000"
            min="0"
          />
        </div>
      </div>

      <!-- Deadline -->
      <div>
        <label class="block text-sm font-medium text-text mb-1.5">{{ $t('business.deadline') }} <span class="text-red-500">*</span></label>
        <input
          v-model="form.deadline"
          type="date"
          class="neu-input w-full"
          :min="minDate"
          required
        />
        <p v-if="errors.deadline" class="text-xs text-red-500 mt-1">{{ errors.deadline }}</p>
      </div>

      <!-- Requirements -->
      <div>
        <label class="block text-sm font-medium text-text mb-1.5">{{ $t('business.requirements') }}</label>
        <textarea
          v-model="form.requirements"
          class="neu-input w-full resize-none"
          rows="4"
          :placeholder="$t('business.requirements_ph')"
        />
      </div>

      <!-- Contact Info -->
      <div>
        <label class="block text-sm font-medium text-text mb-1.5">{{ $t('business.contact_info') }} <span class="text-red-500">*</span></label>
        <input
          v-model="form.contact_info"
          type="text"
          class="neu-input w-full"
          :placeholder="$t('business.contact_ph')"
          required
        />
      </div>

      <!-- Tender Documents -->
      <div>
        <label class="block text-sm font-medium text-text mb-1.5">{{ $t('business.documents_label') }} <span class="text-text-light font-normal text-xs">{{ $t('business.documents_hint') }}</span></label>
        <div
          class="neu-pressed rounded-xl p-4 border-2 border-dashed border-surface-dark/40 text-center cursor-pointer hover:border-primary/40 transition-colors"
          @click="docInput.click()"
          @dragover.prevent
          @drop.prevent="onDrop"
        >
          <svg class="w-8 h-8 mx-auto text-text-light/50 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <p class="text-sm text-text-light">{{ $t('business.drop_click') }}</p>
          <p class="text-xs text-text-light/60 mt-0.5">{{ $t('business.drop_hint') }}</p>
        </div>
        <input ref="docInput" type="file" class="hidden" multiple accept=".pdf,.doc,.docx" @change="onFileSelect" />
        <ul v-if="pendingDocs.length > 0" class="mt-3 space-y-2">
          <li v-for="(f, i) in pendingDocs" :key="i" class="neu-card-sm py-2 px-3 flex items-center gap-3">
            <svg class="w-4 h-4 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span class="text-sm text-text flex-1 truncate">{{ f.name }}</span>
            <span class="text-xs text-text-light">{{ (f.size / 1024).toFixed(0) }} KB</span>
            <button type="button" class="text-danger hover:text-danger/70" @click="pendingDocs.splice(i,1)">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </li>
        </ul>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-3 pt-2">
        <button
          type="submit"
          class="neu-btn-primary px-8 py-2.5 font-semibold text-sm flex items-center gap-2"
          :disabled="businessStore.loading"
        >
          <svg v-if="businessStore.loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
          </svg>
          {{ businessStore.loading ? $t('business.posting') : $t('business.post_btn') }}
        </button>
        <button type="button" class="neu-btn-secondary px-6 py-2.5 font-semibold text-sm" @click="router.back()">
          {{ $t('common.cancel') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useBusinessStore } from '../../stores/business'
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'

const { t } = useI18n()
const router = useRouter()
const businessStore = useBusinessStore()
const authStore = useAuthStore()

const categories = ref([])
const locations = ref([])
const errors = ref({})
const successMsg = ref('')
const errorMsg = ref('')
const pendingDocs = ref([])
const docInput = ref(null)

const minDate = new Date().toISOString().split('T')[0]

function onFileSelect(e) {
  Array.from(e.target.files).forEach(f => pendingDocs.value.push(f))
  e.target.value = ''
}
function onDrop(e) {
  Array.from(e.dataTransfer.files).forEach(f => pendingDocs.value.push(f))
}

const form = reactive({
  title: '',
  organization: authStore.user?.business_name ?? authStore.user?.name ?? '',
  description: '',
  category_id: '',
  location_id: '',
  type: 'business',
  value: '',
  deadline: '',
  requirements: '',
  documents_url: '',
  contact_info: '',
})

async function submitForm() {
  errors.value = {}
  errorMsg.value = ''
  successMsg.value = ''

  // Basic client-side validation
  if (!form.title.trim()) { errors.value.title = 'Title is required'; return }
  if (!form.organization.trim()) { errors.value.organization = 'Organization is required'; return }
  if (!form.description.trim()) { errors.value.description = 'Description is required'; return }
  if (!form.category_id) { errors.value.category_id = 'Category is required'; return }
  if (!form.location_id) { errors.value.location_id = 'Location is required'; return }
  if (!form.deadline) { errors.value.deadline = 'Deadline is required'; return }

  try {
    const payload = { ...form }
    if (!payload.value) delete payload.value
    if (!payload.documents_url) delete payload.documents_url
    if (!payload.requirements) delete payload.requirements

    const newTender = await businessStore.createTender(payload)
    if (pendingDocs.value.length > 0) {
      await businessStore.uploadTenderDocuments(newTender.id, pendingDocs.value)
    }
    successMsg.value = t('business.posted_success')
    setTimeout(() => router.push(`/business/tenders/${newTender.id}/applications`), 1200)
  } catch (err) {
    const serverErrors = err.response?.data?.errors
    if (serverErrors) {
      errors.value = Object.fromEntries(
        Object.entries(serverErrors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
      )
    } else {
      errorMsg.value = err.response?.data?.message ?? 'Failed to post tender. Please try again.'
    }
  }
}

onMounted(async () => {
  const [catRes, locRes] = await Promise.all([
    api.get('/categories').catch(() => ({ data: { data: [] } })),
    api.get('/locations').catch(() => ({ data: { data: [] } })),
  ])
  categories.value = catRes.data.data ?? catRes.data
  locations.value = locRes.data.data ?? locRes.data
})
</script>
