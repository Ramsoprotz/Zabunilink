<template>
  <div class="space-y-6">
    <!-- Back button -->
    <button class="neu-btn px-4 py-2 text-sm text-text-light font-medium inline-flex items-center gap-2" @click="$router.back()">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
      {{ $t('common.back') }}
    </button>

    <!-- Loading -->
    <div v-if="tenderStore.loading" class="text-center py-12 text-text-light">{{ $t('tenders.loading') }}</div>

    <!-- Error -->
    <div v-else-if="error" class="neu-card text-center py-12">
      <p class="text-danger mb-4">{{ error }}</p>
      <button class="neu-btn px-4 py-2 text-sm text-primary font-medium" @click="loadTender">{{ $t('common.retry') }}</button>
    </div>

    <!-- Tender detail -->
    <template v-else-if="tender">
      <div class="neu-card">
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 mb-6">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-3">
              <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
                {{ tender.category?.name || $t('tenders.general') }}
              </span>
              <span
                class="px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="tender.type === 'government' ? 'bg-info/10 text-info' : 'bg-accent/10 text-accent'"
              >
                {{ tender.type === 'government' ? $t('tenders.government') : $t('tenders.private') }}
              </span>
              <span
                class="px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="statusClass(tender.status)"
              >
                {{ tender.status || $t('tenders.open') }}
              </span>
            </div>
            <h1 class="text-xl font-bold text-text">{{ tender.title }}</h1>
            <p class="text-text-light text-sm mt-1">{{ tender.organization }}</p>
          </div>
          <button
            class="neu-btn p-3 flex-shrink-0"
            @click="toggleFav"
          >
            <svg
              class="w-6 h-6 transition-colors"
              :class="tenderStore.isFavorited(tender.id) ? 'text-danger fill-danger' : 'text-text-light'"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
          </button>
        </div>

        <!-- Info grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
          <div class="neu-card-sm">
            <p class="text-xs text-text-light uppercase tracking-wide mb-1">{{ $t('tenders.reference') }}</p>
            <p class="text-sm font-semibold text-text">{{ tender.reference_number || $t('common.na') }}</p>
          </div>
          <div class="neu-card-sm">
            <p class="text-xs text-text-light uppercase tracking-wide mb-1">{{ $t('tenders.deadline') }}</p>
            <p class="text-sm font-semibold text-danger">{{ formatDate(tender.deadline) }}</p>
          </div>
          <div class="neu-card-sm">
            <p class="text-xs text-text-light uppercase tracking-wide mb-1">{{ $t('tenders.location') }}</p>
            <p class="text-sm font-semibold text-text">{{ tender.location?.name || $t('common.tanzania') }}</p>
          </div>
          <div class="neu-card-sm">
            <p class="text-xs text-text-light uppercase tracking-wide mb-1">{{ $t('tenders.value') }}</p>
            <p class="text-sm font-semibold text-text">{{ formatCurrency(tender.value) }}</p>
          </div>
          <div class="neu-card-sm">
            <p class="text-xs text-text-light uppercase tracking-wide mb-1">{{ $t('tenders.category') }}</p>
            <p class="text-sm font-semibold text-text">{{ tender.category?.name || $t('tenders.general') }}</p>
          </div>
          <div class="neu-card-sm">
            <p class="text-xs text-text-light uppercase tracking-wide mb-1">{{ $t('tenders.type') }}</p>
            <p class="text-sm font-semibold text-text capitalize">{{ tender.type || $t('common.na') }}</p>
          </div>
        </div>

        <!-- Description -->
        <div class="mb-6">
          <h2 class="text-sm font-semibold text-text uppercase tracking-wide mb-2">{{ $t('tenders.description') }}</h2>
          <div class="neu-pressed p-4 text-sm text-text leading-relaxed whitespace-pre-line">
            {{ tender.description || 'No description provided.' }}
          </div>
        </div>

        <!-- Requirements -->
        <div v-if="tender.requirements" class="mb-6">
          <h2 class="text-sm font-semibold text-text uppercase tracking-wide mb-2">{{ $t('tenders.requirements') }}</h2>
          <div class="neu-pressed p-4 text-sm text-text leading-relaxed whitespace-pre-line">
            {{ tender.requirements }}
          </div>
        </div>

        <!-- Tender Documents -->
        <div v-if="tender.documents && tender.documents.length > 0" class="mb-6">
          <h2 class="text-sm font-semibold text-text uppercase tracking-wide mb-2">{{ $t('tenders.documents') }}</h2>
          <ul class="space-y-2">
            <li v-for="doc in tender.documents" :key="doc.path">
              <a
                :href="doc.url"
                target="_blank"
                rel="noopener noreferrer"
                class="neu-card-sm py-2.5 px-4 flex items-center gap-3 hover:ring-2 hover:ring-primary/20 transition-all"
              >
                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                  :class="doc.mime === 'application/pdf' ? 'bg-red-100' : 'bg-blue-100'">
                  <svg class="w-4 h-4" :class="doc.mime === 'application/pdf' ? 'text-red-600' : 'text-blue-600'"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-primary truncate">{{ doc.name }}</p>
                  <p class="text-xs text-text-light">{{ (doc.size / 1024).toFixed(0) }} KB</p>
                </div>
                <svg class="w-4 h-4 text-text-light shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
              </a>
            </li>
          </ul>
        </div>

        <!-- Contact info -->
        <div v-if="tender.contact_info" class="mb-6">
          <h2 class="text-sm font-semibold text-text uppercase tracking-wide mb-2">{{ $t('tenders.contact') }}</h2>
          <div class="neu-card-sm">
            <p class="text-sm text-text">{{ tender.contact_info }}</p>
          </div>
        </div>

        <!-- Actions -->
        <div class="pt-4 border-t border-surface-dark/30 space-y-4">
          <!-- Apply Yourself -->
          <!-- Business-posted: in-platform apply -->
          <div
            v-if="tender.source === 'business' && authStore.isAuthenticated"
            class="neu-card-sm flex items-center justify-between cursor-pointer hover:ring-2 hover:ring-primary/20 transition-all"
          >
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
              </div>
              <div>
                <h3 class="text-sm font-semibold text-text">{{ $t('tenders.apply_zabunilink') }}</h3>
                <p class="text-xs text-text-light mt-0.5">{{ $t('tenders.apply_zabunilink_desc') }}</p>
              </div>
            </div>
            <button
              class="neu-btn px-5 py-2.5 rounded-xl text-sm font-semibold text-primary inline-flex items-center gap-2 flex-shrink-0"
              @click="showApplyModal = true"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {{ $t('tenders.apply_now') }}
            </button>
          </div>

          <!-- External-posted: link to source website -->
          <a
            v-else-if="tender.source !== 'business' && tender.documents_url"
            :href="tender.documents_url"
            target="_blank"
            rel="noopener noreferrer"
            class="neu-card-sm flex items-center justify-between cursor-pointer hover:ring-2 hover:ring-primary/20 transition-all block"
          >
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
              </div>
              <div>
                <h3 class="text-sm font-semibold text-text">{{ $t('tenders.apply_yourself') }}</h3>
                <p class="text-xs text-text-light mt-0.5">{{ $t('tenders.apply_yourself_desc') }}</p>
              </div>
            </div>
            <span class="neu-btn px-5 py-2.5 rounded-xl text-sm font-semibold text-primary inline-flex items-center gap-2 flex-shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
              </svg>
              {{ $t('tenders.open_apply') }}
            </span>
          </a>

          <!-- Pro Application (let us do it for you) -->
          <div class="neu-card-sm flex items-center justify-between" :class="authStore.isPro ? 'ring-2 ring-primary/20' : ''">
            <div>
              <div class="flex items-center gap-2 mb-1">
                <h3 class="text-sm font-semibold text-text">{{ $t('tenders.let_us_apply') }}</h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-secondary/10 text-secondary uppercase">Pro</span>
              </div>
              <p class="text-xs text-text-light">{{ $t('tenders.let_us_apply_desc') }}</p>
            </div>
            <button
              v-if="authStore.isPro"
              class="neu-btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold inline-flex items-center gap-2 flex-shrink-0"
              :disabled="applying"
              @click="requestProApplication"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {{ applying ? $t('tenders.requesting') : $t('tenders.request_application') }}
            </button>
            <router-link
              v-else
              to="/subscription"
              class="neu-btn-secondary px-5 py-2.5 rounded-xl text-sm font-semibold inline-flex items-center gap-2 flex-shrink-0"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
              </svg>
              {{ $t('tenders.upgrade_pro') }}
            </router-link>
          </div>

          <!-- Success message -->
          <div v-if="applySuccess" class="neu-card-sm bg-success/5 flex items-center gap-3">
            <svg class="w-5 h-5 text-success flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
              <p class="text-sm font-medium text-success">{{ $t('tenders.application_submitted') }}</p>
              <p class="text-xs text-text-light">{{ $t('tenders.application_submitted_desc') }} <router-link to="/applications" class="text-primary font-medium hover:underline">{{ $t('tenders.my_applications_link') }}</router-link>.</p>
            </div>
          </div>

          <!-- Apply error -->
          <div v-if="applyError" class="neu-card-sm bg-danger/5 flex items-center gap-3">
            <svg class="w-5 h-5 text-danger flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-danger">{{ applyError }}</p>
          </div>
        </div>
      </div>
    </template>
  </div>

  <!-- Apply Modal -->
  <Teleport to="body">
    <div v-if="showApplyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="showApplyModal = false">
      <div class="neu-card w-full max-w-lg">
        <div class="flex items-center justify-between mb-5">
          <h3 class="text-lg font-bold text-text">{{ $t('tenders.submit_application') }}</h3>
          <button class="neu-btn p-1.5" @click="showApplyModal = false">
            <svg class="w-4 h-4 text-text-light" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <p class="text-sm text-text-light mb-4">{{ $t('tenders.applying_for') }} <span class="font-medium text-text">{{ tender?.title }}</span></p>

        <!-- Notes -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-text mb-1.5">{{ $t('tenders.cover_note') }}</label>
          <textarea
            v-model="applyNotes"
            class="neu-input w-full resize-none"
            rows="4"
            :placeholder="$t('tenders.cover_note_placeholder')"
          />
        </div>

        <!-- Document attachments -->
        <div class="mb-5">
          <label class="block text-sm font-medium text-text mb-1.5">{{ $t('tenders.attach_documents') }} <span class="text-text-light font-normal text-xs">{{ $t('tenders.attach_documents_hint') }}</span></label>
          <div
            class="neu-pressed rounded-xl p-4 border-2 border-dashed border-surface-dark/40 text-center cursor-pointer hover:border-primary/40 transition-colors"
            @click="applyDocInput.click()"
            @dragover.prevent
            @drop.prevent="e => Array.from(e.dataTransfer.files).forEach(f => applyDocs.push(f))"
          >
            <svg class="w-7 h-7 mx-auto text-text-light/50 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-sm text-text-light">{{ $t('tenders.attach_click') }}</p>
            <p class="text-xs text-text-light/60 mt-0.5">{{ $t('tenders.attach_hint') }}</p>
          </div>
          <input ref="applyDocInput" type="file" class="hidden" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" @change="e => { Array.from(e.target.files).forEach(f => applyDocs.push(f)); e.target.value='' }" />
          <ul v-if="applyDocs.length > 0" class="mt-2 space-y-1.5">
            <li v-for="(f, i) in applyDocs" :key="i" class="neu-card-sm py-1.5 px-3 flex items-center gap-2">
              <svg class="w-3.5 h-3.5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <span class="text-xs text-text flex-1 truncate">{{ f.name }}</span>
              <span class="text-xs text-text-light">{{ (f.size/1024).toFixed(0) }} KB</span>
              <button type="button" @click="applyDocs.splice(i,1)" class="text-danger">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </li>
          </ul>
        </div>

        <div v-if="applyError" class="mb-4 text-sm text-danger neu-card-sm py-2 px-3">{{ applyError }}</div>

        <div class="flex gap-3">
          <button
            class="neu-btn-primary flex-1 py-2.5 text-sm font-semibold flex items-center justify-center gap-2"
            :disabled="applying"
            @click="applyDirect"
          >
            <svg v-if="applying" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            {{ applying ? $t('tenders.submitting') : $t('tenders.submit_application') }}
          </button>
          <button class="neu-btn px-5 py-2.5 text-sm text-text-light font-medium" @click="showApplyModal = false">{{ $t('common.cancel') }}</button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useTenderStore } from '../../stores/tenders'
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const tenderStore = useTenderStore()
const authStore = useAuthStore()

const error = ref('')
const applying = ref(false)
const applySuccess = ref(false)
const applyError = ref('')
const showApplyModal = ref(false)
const applyNotes = ref('')
const applyDocs = ref([])
const applyDocInput = ref(null)
const tender = computed(() => tenderStore.tender)

function formatDate(dateStr) {
  if (!dateStr) return t('common.na')
  const dateLocale = locale.value === 'sw' ? 'sw-TZ' : 'en-GB'
  return new Date(dateStr).toLocaleDateString(dateLocale, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

function formatCurrency(value) {
  if (!value) return t('common.na')
  return `TZS ${Number(value).toLocaleString('en-US')}`
}

function statusClass(status) {
  const map = {
    open: 'bg-success/10 text-success',
    closed: 'bg-danger/10 text-danger',
    awarded: 'bg-info/10 text-info',
  }
  return map[status] || 'bg-success/10 text-success'
}

async function toggleFav() {
  await tenderStore.toggleFavorite(tender.value.id)
}

async function applyDirect() {
  applying.value = true
  applyError.value = ''
  applySuccess.value = false
  try {
    const { data } = await api.post('/applications', {
      tender_id: tender.value.id,
      notes: applyNotes.value || null,
    })
    // Upload documents if any were attached
    if (applyDocs.value.length > 0) {
      const form = new FormData()
      applyDocs.value.forEach(f => form.append('documents[]', f))
      await api.post(`/applications/${data.data.id}/documents`, form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    }
    applySuccess.value = true
    showApplyModal.value = false
    applyNotes.value = ''
    applyDocs.value = []
  } catch (err) {
    if (err.response?.status === 409) {
      applyError.value = t('tenders.already_applied')
    } else {
      applyError.value = err.response?.data?.message || 'Failed to submit. Please try again.'
    }
  } finally {
    applying.value = false
  }
}

async function requestProApplication() {
  applying.value = true
  applyError.value = ''
  applySuccess.value = false
  try {
    await api.post('/applications', {
      tender_id: tender.value.id,
      notes: `Pro application request for: ${tender.value.title}`,
    })
    applySuccess.value = true
  } catch (err) {
    if (err.response?.status === 409) {
      applyError.value = t('tenders.already_applied')
    } else if (err.response?.status === 403) {
      applyError.value = 'Pro subscription required. Please upgrade your plan.'
    } else {
      applyError.value = err.response?.data?.message || 'Failed to submit application. Please try again.'
    }
  } finally {
    applying.value = false
  }
}

async function loadTender() {
  error.value = ''
  try {
    await tenderStore.fetchTender(route.params.id)
    if (authStore.isAuthenticated) {
      try { await tenderStore.fetchFavorites() } catch {}
    }
  } catch {
    error.value = 'Failed to load tender details.'
  }
}

onMounted(loadTender)
</script>
