import axios from 'axios'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

export type Objective = 'awareness' | 'leads' | 'sales' | 'calls'

export interface DateRange {
  from: string
  to: string
}

export interface Filters {
  account_id?: number
  campaign_id?: number
  platform?: 'facebook' | 'google' | 'tiktok'
}

export interface KPIData {
  [key: string]: number | null
}

export interface TimeseriesData {
  period: string
  value: number
  raw_metrics: {
    spend: number
    impressions: number
    clicks: number
    revenue: number
    leads: number
    calls: number
  }
}

export const useDashboardStore = defineStore('dashboard', () => {
  const objective = ref<Objective>('awareness')
  const dateRange = ref<DateRange>({
    from: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    to: new Date().toISOString().split('T')[0]
  })
  const filters = ref<Filters>({})
  const kpis = ref<KPIData>({})
  const timeseriesData = ref<TimeseriesData[]>([])
  const loading = ref(false)

  const primaryKpis = computed(() => {
    switch (objective.value) {
      case 'awareness':
        return ['cpm']
      case 'leads':
        return ['cpl', 'cvr']
      case 'sales':
        return ['roas', 'cpa']
      case 'calls':
        return ['cost_per_call']
      default:
        return []
    }
  })

  const secondaryKpis = computed(() => {
    switch (objective.value) {
      case 'awareness':
        return ['reach', 'frequency', 'vtr', 'ctr']
      case 'leads':
        return ['ctr', 'cpc']
      case 'sales':
        return ['aov', 'cvr', 'cpc']
      case 'calls':
        return ['ctr', 'call_conversion_rate']
      default:
        return []
    }
  })

  async function fetchSummary() {
    loading.value = true
    try {
      const params = {
        from: dateRange.value.from,
        to: dateRange.value.to,
        objective: objective.value,
        ...filters.value
      }

      const response = await axios.get('/api/metrics/summary', { params })
      kpis.value = response.data.kpis
    } finally {
      loading.value = false
    }
  }

  async function fetchTimeseries(metric: string, groupBy: string = 'date') {
    loading.value = true
    try {
      const params = {
        metric,
        from: dateRange.value.from,
        to: dateRange.value.to,
        objective: objective.value,
        group_by: groupBy,
        ...filters.value
      }

      const response = await axios.get('/api/metrics/timeseries', { params })
      timeseriesData.value = response.data.data
      return response.data.data
    } finally {
      loading.value = false
    }
  }

  function setObjective(newObjective: Objective) {
    objective.value = newObjective
    // Save to localStorage for persistence
    localStorage.setItem('dashboard_objective', newObjective)
  }

  function setDateRange(newDateRange: DateRange) {
    dateRange.value = newDateRange
  }

  function setFilters(newFilters: Filters) {
    filters.value = { ...filters.value, ...newFilters }
  }

  function clearFilters() {
    filters.value = {}
  }

  // Initialize objective from localStorage
  const savedObjective = localStorage.getItem('dashboard_objective') as Objective
  if (savedObjective && ['awareness', 'leads', 'sales', 'calls'].includes(savedObjective)) {
    objective.value = savedObjective
  }

  return {
    objective,
    dateRange,
    filters,
    kpis,
    timeseriesData,
    loading,
    primaryKpis,
    secondaryKpis,
    fetchSummary,
    fetchTimeseries,
    setObjective,
    setDateRange,
    setFilters,
    clearFilters
  }
})
