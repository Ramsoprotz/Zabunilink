<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-text">{{ $t('subscription.title') }}</h1>
      <p class="text-text-light text-sm mt-1">{{ $t('subscription.subtitle') }}</p>
    </div>

    <!-- Current subscription -->
    <div v-if="subStore.mySubscription" class="neu-card">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <p class="text-xs text-text-light uppercase tracking-wide">{{ $t('subscription.current_plan') }}</p>
          <div class="flex items-center gap-2 mt-1">
            <p class="text-xl font-bold text-primary">{{ subStore.mySubscription.plan?.name }}</p>
            <span v-if="subStore.mySubscription.is_trial"
              class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-info/10 text-info">{{ $t('subscription.free_trial_badge') }}</span>
            <span v-else-if="subStore.mySubscription.plan?.name === 'Business'"
              class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-secondary/10 text-secondary">BUSINESS</span>
            <span v-else-if="subStore.mySubscription.plan?.name === 'Pro'"
              class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary/10 text-primary">PRO</span>
          </div>
          <p class="text-sm text-text-light mt-1">
            <template v-if="subStore.mySubscription.is_trial">
              {{ $t('subscription.trial_period') }}
            </template>
            <template v-else>
              {{ $t('subscription.billing') }} <span class="capitalize">{{ subStore.mySubscription.billing_cycle?.replace('_', ' ') }}</span>
            </template>
          </p>
        </div>
        <div class="text-right">
          <span class="px-3 py-1 rounded-full text-xs font-bold"
            :class="subStore.mySubscription.is_trial ? 'bg-info/10 text-info' : 'bg-success/10 text-success'">
            {{ subStore.mySubscription.is_trial ? $t('subscription.trial_active') : $t('subscription.active') }}
          </span>
          <p class="text-xs text-text-light mt-2">
            {{ subStore.mySubscription.is_trial ? $t('subscription.trial_ends') : $t('subscription.expires') }} {{ formatDate(subStore.mySubscription.end_date) }}
          </p>
        </div>
      </div>

      <!-- Trial expiry hint -->
      <div v-if="subStore.mySubscription.is_trial" class="mt-4 p-3 rounded-xl bg-info/5 border border-info/20">
        <p class="text-sm text-text">{{ $t('subscription.trial_expiry_hint') }}</p>
      </div>

      <!-- Scheduled downgrade banner -->
      <div v-if="subStore.mySubscription.scheduled_plan_id" class="mt-4 p-3 rounded-xl bg-warning/10 border border-warning/20">
        <div class="flex items-center justify-between flex-wrap gap-3">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-warning flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-text">
              {{ $t('subscription.scheduled_change') }}
              <span class="font-semibold">{{ subStore.mySubscription.scheduled_plan?.name }}</span>
              {{ $t('subscription.on_date') }} {{ formatDate(subStore.mySubscription.end_date) }}
            </p>
          </div>
          <button
            class="neu-btn px-3 py-1.5 text-xs font-medium text-danger"
            :disabled="subStore.loading"
            @click="handleCancelScheduled"
          >
            {{ $t('subscription.cancel_change') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Free trial banner (no subscription + trial enabled) -->
    <div v-if="!subStore.mySubscription && subStore.trialConfig.enabled" class="neu-card border-2 border-info/30 bg-info/5">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-info/10 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
            </svg>
          </div>
          <div>
            <p class="font-semibold text-text">{{ $t('subscription.try_free', { days: subStore.trialConfig.days }) }}</p>
            <p class="text-xs text-text-light mt-0.5">{{ $t('subscription.try_free_desc') }}</p>
          </div>
        </div>
        <button
          class="neu-btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold"
          :disabled="subStore.loading"
          @click="handleStartTrial"
        >
          <span v-if="subStore.loading">{{ $t('common.processing') }}</span>
          <span v-else>{{ $t('subscription.start_trial') }}</span>
        </button>
      </div>
    </div>

    <!-- Billing cycle selector -->
    <div class="flex items-center justify-center gap-2 flex-wrap">
      <button
        v-for="cycle in billingCycles"
        :key="cycle.value"
        class="neu-btn px-4 py-2 text-sm font-medium"
        :class="selectedCycle === cycle.value ? 'neu-btn-active text-primary font-semibold' : 'text-text-light'"
        @click="selectedCycle = cycle.value"
      >
        {{ cycle.label }}
        <span v-if="cycle.savings" class="ml-1 text-[10px] text-success font-bold">{{ cycle.savings }}</span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12 text-text-light">{{ $t('subscription.loading') }}</div>

    <!-- Plan cards — 3 column grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div
        v-for="plan in subStore.plans"
        :key="plan.id"
        class="neu-card relative flex flex-col"
        :class="{
          'ring-2 ring-secondary': plan.name === 'Business',
          'ring-2 ring-primary/40': plan.name === 'Pro',
        }"
      >
        <!-- Badge -->
        <div
          v-if="plan.name === 'Business'"
          class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-secondary text-white text-xs font-bold whitespace-nowrap"
        >
          {{ $t('subscription.best_value') }}
        </div>
        <div
          v-else-if="plan.name === 'Pro'"
          class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-primary text-white text-xs font-bold whitespace-nowrap"
        >
          {{ $t('subscription.popular') }}
        </div>

        <!-- Plan icon -->
        <div class="flex justify-center mb-4 mt-2">
          <div class="w-12 h-12 rounded-2xl flex items-center justify-center"
            :class="{
              'bg-secondary/10': plan.name === 'Business',
              'bg-primary/10': plan.name === 'Pro',
              'bg-surface-dark': plan.name === 'Basic',
            }">
            <svg v-if="plan.name === 'Business'" class="w-6 h-6 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <svg v-else-if="plan.name === 'Pro'" class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
            </svg>
            <svg v-else class="w-6 h-6 text-text-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
        </div>

        <div class="text-center mb-5">
          <h3 class="text-lg font-bold text-text">{{ plan.name }}</h3>
          <p class="text-xs text-text-light mt-1 leading-relaxed px-2">{{ plan.description }}</p>
          <p class="text-3xl font-bold mt-4"
            :class="{
              'text-secondary': plan.name === 'Business',
              'text-primary': plan.name !== 'Basic',
              'text-text': plan.name === 'Basic',
            }">
            TZS {{ formatPrice(getPlanPrice(plan)) }}
          </p>
          <p class="text-xs text-text-light mt-1">{{ $t('subscription.per') }} {{ cycleLabel }}</p>
          <!-- Free trial hint under Basic price -->
          <p v-if="plan.name === 'Basic' && subStore.trialConfig.enabled && !subStore.mySubscription"
            class="text-xs text-info font-medium mt-2">
            {{ $t('subscription.or_try_free', { days: subStore.trialConfig.days }) }}
          </p>
        </div>

        <!-- Features -->
        <ul class="space-y-2.5 mb-6 flex-1">
          <li
            v-for="(feature, idx) in plan.features"
            :key="idx"
            class="flex items-start gap-2 text-sm"
          >
            <svg class="w-4 h-4 flex-shrink-0 mt-0.5"
              :class="plan.name === 'Business' ? 'text-secondary' : 'text-success'"
              fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="text-text leading-snug">{{ feature }}</span>
          </li>
        </ul>

        <!-- Action button -->
        <button
          class="w-full py-3 rounded-xl text-sm font-semibold transition-all duration-200"
          :class="getButtonClass(plan)"
          :disabled="subStore.loading || isCurrentPlan(plan)"
          @click="handlePlanAction(plan)"
        >
          <span v-if="isCurrentPlan(plan)">{{ $t('subscription.current') }}</span>
          <span v-else-if="subStore.loading">{{ $t('common.processing') }}</span>
          <span v-else>{{ getButtonLabel(plan) }}</span>
        </button>

        <!-- Start free trial button (Basic card, no subscription, trial enabled) -->
        <button
          v-if="plan.name === 'Basic' && !subStore.mySubscription && subStore.trialConfig.enabled"
          class="w-full mt-2 py-2.5 rounded-xl text-xs font-medium text-info border border-info/30 hover:bg-info/5 transition-all duration-200"
          :disabled="subStore.loading"
          @click="handleStartTrial"
        >
          {{ $t('subscription.start_trial_free', { days: subStore.trialConfig.days }) }}
        </button>
      </div>
    </div>

    <!-- Compare table hint -->
    <p class="text-center text-xs text-text-light">
      {{ $t('subscription.trial_note') }}
    </p>

    <!-- Error / Success -->
    <div v-if="error" class="neu-card-sm text-center text-danger text-sm">{{ error }}</div>
    <div v-if="success" class="neu-card-sm text-center text-success text-sm">{{ success }}</div>

    <!-- Payment Modal -->
    <div v-if="showPayment" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
      <div class="neu-card w-full max-w-md text-center">
        <!-- Pending -->
        <template v-if="paymentStatus === 'pending'">
          <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <h3 class="text-lg font-bold text-text mb-2">{{ $t('subscription.payment_ready') }}</h3>
          <p class="text-sm text-text-light mb-4">{{ $t('subscription.payment_ready_desc') }}</p>
          <p class="text-sm font-semibold text-text mb-1">TZS {{ formatPrice(paymentAmount) }}</p>
          <p class="text-xs text-text-light mb-6">{{ $t('subscription.payment_ref') }}: {{ paymentReference }}</p>

          <!-- Primary: Pay Now button (opens Selcom gateway) -->
          <a
            v-if="paymentGatewayUrl"
            :href="paymentGatewayUrl"
            target="_blank"
            class="neu-btn-primary px-6 py-3 rounded-xl text-sm font-semibold inline-flex items-center justify-center gap-2 w-full mb-3"
            @click="paymentLinkClicked = true"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
            {{ $t('subscription.payment_pay_now') }}
          </a>
          <p v-if="paymentGatewayUrl" class="text-xs text-text-light mb-4">{{ $t('subscription.payment_methods_hint') }}</p>

          <!-- Waiting indicator (shows after user clicks pay) -->
          <div v-if="paymentLinkClicked" class="flex items-center justify-center gap-2 mb-4 p-3 rounded-xl bg-primary/5">
            <div class="w-2 h-2 rounded-full bg-primary animate-bounce" style="animation-delay: 0s" />
            <div class="w-2 h-2 rounded-full bg-primary animate-bounce" style="animation-delay: 0.15s" />
            <div class="w-2 h-2 rounded-full bg-primary animate-bounce" style="animation-delay: 0.3s" />
            <span class="text-xs text-text-light ml-2">{{ $t('subscription.payment_waiting') }}</span>
          </div>

          <!-- No gateway URL fallback -->
          <p v-if="!paymentGatewayUrl" class="text-sm text-danger mb-4">{{ $t('subscription.payment_no_gateway') }}</p>

          <button class="neu-btn px-5 py-2.5 text-sm text-text-light w-full" @click="closePaymentModal">
            {{ $t('subscription.payment_close') }}
          </button>
        </template>

        <!-- Completed -->
        <template v-else-if="paymentStatus === 'completed'">
          <div class="w-16 h-16 rounded-full bg-success/10 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h3 class="text-lg font-bold text-text mb-2">{{ $t('subscription.payment_success') }}</h3>
          <p class="text-sm text-text-light mb-6">{{ $t('subscription.payment_activated') }}</p>
          <button class="neu-btn-primary px-6 py-2.5 rounded-xl text-sm font-semibold" @click="closePaymentModal">
            {{ $t('common.done') }}
          </button>
        </template>

        <!-- Failed -->
        <template v-else-if="paymentStatus === 'failed'">
          <div class="w-16 h-16 rounded-full bg-danger/10 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </div>
          <h3 class="text-lg font-bold text-text mb-2">{{ $t('subscription.payment_failed') }}</h3>
          <p class="text-sm text-text-light mb-6">{{ $t('subscription.payment_failed_desc') }}</p>
          <button class="neu-btn px-6 py-2.5 text-sm text-text-light" @click="closePaymentModal">
            {{ $t('common.close') }}
          </button>
        </template>
      </div>
    </div>

    <!-- Plan Change Preview Modal -->
    <div v-if="showPreview" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showPreview = false">
      <div class="neu-card w-full max-w-md">
        <h3 class="text-lg font-bold text-text mb-4">
          {{ preview?.action === 'upgrade' ? $t('subscription.confirm_upgrade') : $t('subscription.confirm_downgrade') }}
        </h3>

        <div class="space-y-3 mb-6">
          <!-- Current → New -->
          <div class="flex items-center justify-between text-sm">
            <span class="text-text-light">{{ $t('subscription.current_plan') }}</span>
            <span class="font-medium text-text">{{ subStore.mySubscription?.plan?.name }}</span>
          </div>
          <div class="flex items-center justify-center">
            <svg class="w-5 h-5 text-text-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
            </svg>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-text-light">{{ $t('subscription.new_plan') }}</span>
            <span class="font-semibold" :class="preview?.action === 'upgrade' ? 'text-primary' : 'text-text'">{{ previewPlan?.name }}</span>
          </div>

          <hr class="border-surface-dark">

          <!-- Upgrade: proration breakdown -->
          <template v-if="preview?.action === 'upgrade'">
            <div class="flex items-center justify-between text-sm">
              <span class="text-text-light">{{ $t('subscription.new_plan_price') }}</span>
              <span class="text-text">TZS {{ formatPrice(preview.new_amount) }}</span>
            </div>
            <div v-if="preview.credit > 0" class="flex items-center justify-between text-sm">
              <span class="text-text-light">{{ $t('subscription.proration_credit') }}</span>
              <span class="text-success">- TZS {{ formatPrice(preview.credit) }}</span>
            </div>
            <hr class="border-surface-dark">
            <div class="flex items-center justify-between text-base font-bold">
              <span class="text-text">{{ $t('subscription.amount_due') }}</span>
              <span class="text-primary">TZS {{ formatPrice(preview.amount_due) }}</span>
            </div>
            <p class="text-xs text-text-light">{{ $t('subscription.upgrade_immediate') }}</p>
          </template>

          <!-- Downgrade info -->
          <template v-else>
            <div class="flex items-center justify-between text-sm">
              <span class="text-text-light">{{ $t('subscription.new_plan_price') }}</span>
              <span class="text-text">TZS {{ formatPrice(preview.new_amount) }}</span>
            </div>
            <p class="text-xs text-text-light mt-2">{{ $t('subscription.downgrade_scheduled_info') }} {{ formatDate(preview.effective_date) }}.</p>
          </template>
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
          <button class="neu-btn flex-1 py-2.5 text-sm font-medium text-text-light" @click="showPreview = false">
            {{ $t('common.cancel') }}
          </button>
          <button
            class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white"
            :class="preview?.action === 'upgrade' ? 'bg-primary hover:bg-primary/90' : 'bg-warning hover:bg-warning/90'"
            :disabled="subStore.loading"
            @click="confirmPlanChange"
          >
            <span v-if="subStore.loading">{{ $t('common.processing') }}</span>
            <span v-else>{{ $t('common.confirm') }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useSubscriptionStore } from '../../stores/subscription'

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const subStore = useSubscriptionStore()

const loading = ref(true)
const error = ref('')
const success = ref('')
const selectedCycle = ref('monthly')

// Preview modal state
const showPreview = ref(false)
const preview = ref(null)
const previewPlan = ref(null)

// Payment modal state
const showPayment = ref(false)
const paymentStatus = ref('pending')
const paymentReference = ref('')
const paymentAmount = ref(0)
const paymentGatewayUrl = ref(null)
const paymentLinkClicked = ref(false)
let paymentPollInterval = null

const billingCycles = [
  { value: 'monthly', label: t('subscription.monthly'), savings: null },
  { value: 'quarterly', label: t('subscription.quarterly'), savings: t('subscription.save_10') },
  { value: 'semi_annual', label: t('subscription.semi_annual'), savings: t('subscription.save_17') },
  { value: 'annual', label: t('subscription.annual'), savings: t('subscription.save_22') },
]

const cycleLabel = computed(() => {
  const map = {
    monthly: t('subscription.month'),
    quarterly: t('subscription.quarter'),
    semi_annual: t('subscription.six_months'),
    annual: t('subscription.year'),
  }
  return map[selectedCycle.value] || t('subscription.month')
})

function formatDate(dateStr) {
  if (!dateStr) return t('common.na')
  const dateLocale = locale.value === 'sw' ? 'sw-TZ' : 'en-GB'
  return new Date(dateStr).toLocaleDateString(dateLocale, { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatPrice(amount) {
  if (!amount) return '0'
  return Number(amount).toLocaleString('en-US')
}

function getPlanPrice(plan) {
  const map = {
    monthly: plan.monthly_price,
    quarterly: plan.quarterly_price,
    semi_annual: plan.semi_annual_price,
    annual: plan.annual_price,
  }
  return map[selectedCycle.value] || plan.monthly_price || 0
}

function isCurrentPlan(plan) {
  if (!subStore.mySubscription) return false
  // For trial subscriptions, match by plan id only (billing_cycle is irrelevant)
  if (subStore.mySubscription.is_trial) return subStore.mySubscription.plan?.id === plan.id
  return subStore.mySubscription.plan?.id === plan.id && subStore.mySubscription.billing_cycle === selectedCycle.value
}

function isUpgrade(plan) {
  if (!subStore.mySubscription) return true
  const currentTier = subStore.mySubscription.plan?.tier || 0
  return plan.tier > currentTier
    || (plan.tier === currentTier && getPlanPrice(plan) > Number(subStore.mySubscription.amount))
}

function isDowngrade(plan) {
  if (!subStore.mySubscription) return false
  const currentTier = subStore.mySubscription.plan?.tier || 0
  return plan.tier < currentTier
    || (plan.tier === currentTier && getPlanPrice(plan) < Number(subStore.mySubscription.amount))
}

function getButtonLabel(plan) {
  if (!subStore.mySubscription) return t('subscription.get') + ' ' + plan.name
  if (isUpgrade(plan)) return t('subscription.upgrade_to') + ' ' + plan.name
  if (isDowngrade(plan)) return t('subscription.downgrade_to') + ' ' + plan.name
  return t('subscription.switch_cycle')
}

function getButtonClass(plan) {
  if (isCurrentPlan(plan)) return 'neu-pressed opacity-60 cursor-not-allowed text-primary'
  if (plan.name === 'Business' && !isCurrentPlan(plan)) return 'neu-btn-secondary'
  if (plan.name === 'Pro' && !isCurrentPlan(plan)) return 'neu-btn-primary'
  return 'neu-btn text-text-light'
}

async function handleStartTrial() {
  error.value = ''
  success.value = ''
  try {
    const result = await subStore.startTrial()
    success.value = result.message || t('subscription.trial_started')
    await subStore.fetchMySubscription()
  } catch (err) {
    error.value = err.response?.data?.message || t('subscription.trial_error')
  }
}

function openPaymentModal(paymentData) {
  const payment = paymentData.payment
  if (!payment) return

  paymentReference.value = payment.reference
  paymentAmount.value = payment.amount
  paymentGatewayUrl.value = payment.metadata?.gateway_url || null
  paymentStatus.value = 'pending'
  showPayment.value = true

  // Start polling for payment status
  startPaymentPolling(payment.reference)
}

function startPaymentPolling(reference) {
  stopPaymentPolling()
  let attempts = 0
  const maxAttempts = 60 // 5 minutes at 5s intervals

  paymentPollInterval = setInterval(async () => {
    attempts++
    if (attempts > maxAttempts) {
      stopPaymentPolling()
      return
    }

    try {
      const status = await subStore.checkPaymentStatus(reference)
      if (status.status === 'completed') {
        paymentStatus.value = 'completed'
        stopPaymentPolling()
        await subStore.fetchMySubscription()
      } else if (status.status === 'failed') {
        paymentStatus.value = 'failed'
        stopPaymentPolling()
      }
    } catch {
      // Keep polling
    }
  }, 5000)
}

function stopPaymentPolling() {
  if (paymentPollInterval) {
    clearInterval(paymentPollInterval)
    paymentPollInterval = null
  }
}

function closePaymentModal() {
  stopPaymentPolling()
  showPayment.value = false
  paymentStatus.value = 'pending'
  paymentLinkClicked.value = false
}

async function handlePlanAction(plan) {
  error.value = ''
  success.value = ''

  // No active subscription — direct subscribe
  if (!subStore.mySubscription) {
    try {
      const result = await subStore.subscribe(plan.id, selectedCycle.value)
      if (result.payment) {
        openPaymentModal(result)
      } else {
        success.value = result.message || t('subscription.subscribe_success') + ' ' + plan.name
        await subStore.fetchMySubscription()
      }
    } catch (err) {
      error.value = err.response?.data?.message || t('subscription.subscribe_error')
    }
    return
  }

  // On a trial — upgrading from trial is like a new subscription (no proration on free trial)
  if (subStore.mySubscription.is_trial) {
    try {
      const result = await subStore.subscribe(plan.id, selectedCycle.value)
      if (result.payment) {
        openPaymentModal(result)
      } else {
        success.value = result.message || t('subscription.upgrade_success')
        await subStore.fetchMySubscription()
      }
    } catch (err) {
      error.value = err.response?.data?.message || t('subscription.change_error')
    }
    return
  }

  // Has paid subscription — preview the change first
  try {
    preview.value = await subStore.previewChange(plan.id, selectedCycle.value)
    previewPlan.value = plan
    showPreview.value = true
  } catch (err) {
    error.value = err.response?.data?.message || t('subscription.preview_error')
  }
}

async function confirmPlanChange() {
  error.value = ''
  success.value = ''

  try {
    const result = await subStore.changePlan(previewPlan.value.id, selectedCycle.value)
    showPreview.value = false

    if (result.action === 'downgrade_scheduled') {
      success.value = result.message
      await subStore.fetchMySubscription()
    } else if (result.payment) {
      openPaymentModal(result)
    } else {
      success.value = result.message || t('subscription.upgrade_success')
      await subStore.fetchMySubscription()
    }
  } catch (err) {
    error.value = err.response?.data?.message || t('subscription.change_error')
    showPreview.value = false
  }
}

async function handleCancelScheduled() {
  error.value = ''
  success.value = ''
  try {
    const result = await subStore.cancelScheduledChange()
    success.value = result.message
    await subStore.fetchMySubscription()
  } catch (err) {
    error.value = err.response?.data?.message || t('subscription.cancel_error')
  }
}

onMounted(async () => {
  try {
    await Promise.all([subStore.fetchPlans(), subStore.fetchMySubscription()])
  } finally {
    loading.value = false
  }

  // Handle return from Selcom payment page
  const paymentRef = route.query.payment
  const paymentQueryStatus = route.query.status

  if (paymentRef) {
    // Clean the query params from URL
    router.replace({ path: route.path, query: {} })

    paymentReference.value = paymentRef

    if (paymentQueryStatus === 'cancelled') {
      paymentStatus.value = 'failed'
      showPayment.value = true
    } else {
      // status=success or any other — check actual payment status from backend
      paymentStatus.value = 'pending'
      paymentLinkClicked.value = true
      showPayment.value = true

      try {
        const status = await subStore.checkPaymentStatus(paymentRef)
        paymentAmount.value = status.amount || 0

        if (status.status === 'completed') {
          paymentStatus.value = 'completed'
          await subStore.fetchMySubscription()
        } else if (status.status === 'failed') {
          paymentStatus.value = 'failed'
        } else {
          // Still pending — start polling
          startPaymentPolling(paymentRef)
        }
      } catch {
        // Could not check — start polling anyway
        startPaymentPolling(paymentRef)
      }
    }
  }
})
</script>
