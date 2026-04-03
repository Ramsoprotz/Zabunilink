<template>
  <div class="space-y-6">
    <!-- Back button -->
    <button class="neu-btn px-4 py-2 text-sm text-text-light font-medium inline-flex items-center gap-2" @click="$router.back()">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
      Back to Applications
    </button>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12 text-text-light">Loading application...</div>

    <!-- Error -->
    <div v-else-if="error" class="neu-card text-center py-12">
      <p class="text-danger mb-4">{{ error }}</p>
      <button class="neu-btn px-4 py-2 text-sm text-primary font-medium" @click="fetchApplication">Retry</button>
    </div>

    <template v-else-if="application">
      <!-- Status timeline -->
      <div class="neu-card">
        <h2 class="text-sm font-semibold text-text uppercase tracking-wide mb-4">Application Progress</h2>
        <div class="flex items-center justify-between overflow-x-auto pb-2">
          <div
            v-for="(step, idx) in progressSteps"
            :key="step.key"
            class="flex items-center"
          >
            <div class="flex flex-col items-center min-w-[80px]">
              <div
                class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
                :class="isStepCompleted(step.key) ? 'bg-primary text-white' : isStepCurrent(step.key) ? 'bg-secondary text-white' : 'bg-surface-dark text-text-light'"
              >
                {{ idx + 1 }}
              </div>
              <span class="text-xs text-text-light mt-1 text-center">{{ step.label }}</span>
            </div>
            <div
              v-if="idx < progressSteps.length - 1"
              class="w-12 h-0.5 mx-1"
              :class="isStepCompleted(step.key) ? 'bg-primary' : 'bg-surface-dark'"
            />
          </div>
        </div>
      </div>

      <!-- Tender info -->
      <div class="neu-card">
        <h2 class="text-sm font-semibold text-text uppercase tracking-wide mb-3">Tender Information</h2>
        <div class="space-y-2">
          <p class="text-sm"><span class="text-text-light">Title:</span> <span class="font-medium text-text">{{ application.tender?.title }}</span></p>
          <p class="text-sm"><span class="text-text-light">Organization:</span> <span class="text-text">{{ application.tender?.organization }}</span></p>
          <p class="text-sm"><span class="text-text-light">Reference:</span> <span class="text-text">{{ application.tender?.reference_number || 'N/A' }}</span></p>
          <p class="text-sm"><span class="text-text-light">Deadline:</span> <span class="text-danger font-medium">{{ formatDate(application.tender?.deadline) }}</span></p>
          <p class="text-sm"><span class="text-text-light">Value:</span> <span class="text-text">{{ formatCurrency(application.tender?.value) }}</span></p>
        </div>
      </div>

      <!-- Application details -->
      <div class="neu-card">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-sm font-semibold text-text uppercase tracking-wide">Application Details</h2>
          <span
            class="px-3 py-1 rounded-full text-xs font-semibold"
            :class="statusBadgeClass(application.status)"
          >
            {{ formatStatus(application.status) }}
          </span>
        </div>
        <div class="space-y-2">
          <p class="text-sm"><span class="text-text-light">Applied on:</span> <span class="text-text">{{ formatDate(application.created_at) }}</span></p>
          <p class="text-sm"><span class="text-text-light">Last updated:</span> <span class="text-text">{{ formatDate(application.updated_at) }}</span></p>
        </div>
      </div>

      <!-- Notes -->
      <div class="neu-card">
        <h2 class="text-sm font-semibold text-text uppercase tracking-wide mb-3">Notes</h2>
        <div v-if="application.notes" class="neu-pressed p-4 text-sm text-text leading-relaxed whitespace-pre-line mb-4">
          {{ application.notes }}
        </div>
        <div class="space-y-3">
          <textarea
            v-model="newNote"
            class="neu-input min-h-[80px]"
            placeholder="Add a note about this application..."
          />
          <button
            class="neu-btn-primary px-4 py-2 rounded-xl text-sm font-semibold"
            :disabled="!newNote.trim() || savingNote"
            @click="saveNote"
          >
            {{ savingNote ? 'Saving...' : 'Save Note' }}
          </button>
        </div>
      </div>

      <!-- Documents -->
      <div class="neu-card">
        <h2 class="text-sm font-semibold text-text uppercase tracking-wide mb-3">Documents</h2>

        <!-- Existing documents -->
        <div v-if="application.documents?.length" class="space-y-2 mb-4">
          <div
            v-for="doc in application.documents"
            :key="doc.id"
            class="neu-card-sm flex items-center justify-between"
          >
            <div class="flex items-center gap-3">
              <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
              <div>
                <p class="text-sm font-medium text-text">{{ doc.name || doc.original_name }}</p>
                <p class="text-xs text-text-light">{{ formatDate(doc.created_at) }}</p>
              </div>
            </div>
            <a
              :href="doc.url"
              target="_blank"
              class="neu-btn px-3 py-1.5 text-xs text-primary font-medium"
              @click.stop
            >
              Download
            </a>
          </div>
        </div>

        <!-- Upload area -->
        <div
          class="neu-pressed p-6 text-center cursor-pointer"
          @click="$refs.fileInput.click()"
          @dragover.prevent
          @drop.prevent="handleDrop"
        >
          <svg class="w-8 h-8 mx-auto text-text-light mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
          </svg>
          <p class="text-sm text-text-light">Click or drag files to upload</p>
          <p class="text-xs text-text-light mt-1">PDF, DOC, DOCX up to 10MB</p>
          <input
            ref="fileInput"
            type="file"
            class="hidden"
            accept=".pdf,.doc,.docx"
            multiple
            @change="handleFileSelect"
          />
        </div>

        <!-- Upload progress -->
        <div v-if="uploading" class="mt-3 text-center text-sm text-text-light">Uploading...</div>
        <div v-if="uploadError" class="mt-3 text-center text-sm text-danger">{{ uploadError }}</div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../../services/api'

const route = useRoute()

const application = ref(null)
const loading = ref(true)
const error = ref('')
const newNote = ref('')
const savingNote = ref(false)
const uploading = ref(false)
const uploadError = ref('')
const fileInput = ref(null)

const progressSteps = [
  { key: 'pending', label: 'Pending' },
  { key: 'in_progress', label: 'In Progress' },
  { key: 'submitted', label: 'Submitted' },
  { key: 'won', label: 'Awarded' },
]

const statusOrder = ['pending', 'in_progress', 'submitted', 'won', 'lost']

function isStepCompleted(stepKey) {
  const currentIdx = statusOrder.indexOf(application.value?.status)
  const stepIdx = statusOrder.indexOf(stepKey)
  return stepIdx < currentIdx
}

function isStepCurrent(stepKey) {
  return application.value?.status === stepKey
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A'
  return new Date(dateStr).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

function formatCurrency(value) {
  if (!value) return 'N/A'
  return `TZS ${Number(value).toLocaleString('en-US')}`
}

function formatStatus(status) {
  if (!status) return 'Pending'
  return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

function statusBadgeClass(status) {
  const map = {
    pending: 'bg-warning/10 text-warning',
    in_progress: 'bg-info/10 text-info',
    submitted: 'bg-primary/10 text-primary',
    won: 'bg-success/10 text-success',
    lost: 'bg-danger/10 text-danger',
  }
  return map[status] || 'bg-warning/10 text-warning'
}

async function fetchApplication() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get(`/applications/${route.params.id}`)
    application.value = data.data
  } catch {
    error.value = 'Failed to load application details.'
  } finally {
    loading.value = false
  }
}

async function saveNote() {
  savingNote.value = true
  try {
    await api.put(`/applications/${route.params.id}`, {
      notes: newNote.value,
    })
    application.value.notes = newNote.value
    newNote.value = ''
  } catch {
    // Silently handle
  } finally {
    savingNote.value = false
  }
}

async function uploadFiles(files) {
  if (!files.length) return
  uploading.value = true
  uploadError.value = ''
  try {
    const formData = new FormData()
    for (const file of files) {
      formData.append('documents[]', file)
    }
    const { data } = await api.post(`/applications/${route.params.id}/documents`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    // Refresh application data
    await fetchApplication()
  } catch {
    uploadError.value = 'Failed to upload documents. Please try again.'
  } finally {
    uploading.value = false
  }
}

function handleFileSelect(event) {
  uploadFiles(Array.from(event.target.files))
  event.target.value = ''
}

function handleDrop(event) {
  uploadFiles(Array.from(event.dataTransfer.files))
}

onMounted(fetchApplication)
</script>
