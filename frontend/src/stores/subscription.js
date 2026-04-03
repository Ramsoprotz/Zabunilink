import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api'

export const useSubscriptionStore = defineStore('subscription', () => {
  const plans = ref([])
  const mySubscription = ref(null)
  const trialConfig = ref({ enabled: false, days: null })
  const loading = ref(false)

  async function fetchPlans() {
    const { data } = await api.get('/plans')
    plans.value = data.data
    if (data.trial) {
      trialConfig.value = data.trial
    }
  }

  async function fetchMySubscription() {
    try {
      const { data } = await api.get('/my-subscription')
      mySubscription.value = data.data
    } catch {
      mySubscription.value = null
    }
  }

  async function subscribe(planId, billingCycle) {
    loading.value = true
    try {
      const { data } = await api.post('/subscribe', {
        plan_id: planId,
        billing_cycle: billingCycle,
      })
      return data
    } finally {
      loading.value = false
    }
  }

  async function changePlan(planId, billingCycle) {
    loading.value = true
    try {
      const { data } = await api.post('/subscription/change-plan', {
        plan_id: planId,
        billing_cycle: billingCycle,
      })
      return data
    } finally {
      loading.value = false
    }
  }

  async function previewChange(planId, billingCycle) {
    const { data } = await api.post('/subscription/preview-change', {
      plan_id: planId,
      billing_cycle: billingCycle,
    })
    return data.data
  }

  async function cancelScheduledChange() {
    loading.value = true
    try {
      const { data } = await api.post('/subscription/cancel-scheduled')
      return data
    } finally {
      loading.value = false
    }
  }

  async function startTrial() {
    loading.value = true
    try {
      const { data } = await api.post('/subscription/start-trial')
      return data
    } finally {
      loading.value = false
    }
  }

  async function checkPaymentStatus(reference) {
    const { data } = await api.get(`/payments/${reference}/status`)
    return data.data
  }

  return {
    plans,
    mySubscription,
    trialConfig,
    loading,
    fetchPlans,
    fetchMySubscription,
    subscribe,
    changePlan,
    previewChange,
    cancelScheduledChange,
    startTrial,
    checkPaymentStatus,
  }
})
