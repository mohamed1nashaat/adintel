<template>
  <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Primary KPIs -->
    <div
      v-for="kpi in primaryKpis"
      :key="kpi"
      class="relative bg-white pt-5 px-4 pb-12 sm:pt-6 sm:px-6 shadow rounded-lg overflow-hidden"
    >
      <dt>
        <div class="absolute bg-indigo-500 rounded-md p-3">
          <component :is="getKpiIcon(kpi)" class="h-6 w-6 text-white" aria-hidden="true" />
        </div>
        <p class="ml-16 text-sm font-medium text-gray-500 truncate">
          {{ $t(`kpis.${kpi}`) }}
        </p>
      </dt>
      <dd class="ml-16 pb-6 flex items-baseline sm:pb-7">
        <p class="text-2xl font-semibold text-gray-900">
          {{ formatKpiValue(kpi, dashboardStore.kpis[kpi]) }}
        </p>
        <div class="absolute bottom-0 inset-x-0 bg-gray-50 px-4 py-4 sm:px-6">
          <div class="text-sm">
            <span class="text-gray-600">{{ getKpiDescription(kpi) }}</span>
          </div>
        </div>
      </dd>
    </div>

    <!-- Secondary KPIs -->
    <div
      v-for="kpi in secondaryKpis"
      :key="kpi"
      class="relative bg-white pt-5 px-4 pb-12 sm:pt-6 sm:px-6 shadow rounded-lg overflow-hidden"
    >
      <dt>
        <div class="absolute bg-gray-500 rounded-md p-3">
          <component :is="getKpiIcon(kpi)" class="h-6 w-6 text-white" aria-hidden="true" />
        </div>
        <p class="ml-16 text-sm font-medium text-gray-500 truncate">
          {{ $t(`kpis.${kpi}`) }}
        </p>
      </dt>
      <dd class="ml-16 pb-6 flex items-baseline sm:pb-7">
        <p class="text-2xl font-semibold text-gray-900">
          {{ formatKpiValue(kpi, dashboardStore.kpis[kpi]) }}
        </p>
        <div class="absolute bottom-0 inset-x-0 bg-gray-50 px-4 py-4 sm:px-6">
          <div class="text-sm">
            <span class="text-gray-600">{{ getKpiDescription(kpi) }}</span>
          </div>
        </div>
      </dd>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  EyeIcon,
  CurrencyDollarIcon,
  PhoneIcon,
  UserGroupIcon,
  ChartBarIcon,
  ClockIcon,
  PlayIcon,
  CursorArrowRaysIcon
} from '@heroicons/vue/24/outline'
import { useDashboardStore } from '@/stores/dashboard'

const dashboardStore = useDashboardStore()

const primaryKpis = computed(() => dashboardStore.primaryKpis)
const secondaryKpis = computed(() => dashboardStore.secondaryKpis)

const getKpiIcon = (kpi: string) => {
  const iconMap: Record<string, any> = {
    cpm: EyeIcon,
    cpl: UserGroupIcon,
    cpc: CursorArrowRaysIcon,
    ctr: ChartBarIcon,
    cvr: ChartBarIcon,
    roas: CurrencyDollarIcon,
    cpa: CurrencyDollarIcon,
    aov: CurrencyDollarIcon,
    cost_per_call: PhoneIcon,
    call_conversion_rate: PhoneIcon,
    reach: UserGroupIcon,
    frequency: ClockIcon,
    vtr: PlayIcon
  }
  return iconMap[kpi] || ChartBarIcon
}

const formatKpiValue = (kpi: string, value: number | null): string => {
  if (value === null || value === undefined) {
    return 'N/A'
  }

  // Currency KPIs
  if (['cpm', 'cpl', 'cpc', 'cpa', 'aov', 'cost_per_call'].includes(kpi)) {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value)
  }

  // Percentage KPIs
  if (['ctr', 'cvr', 'vtr', 'call_conversion_rate'].includes(kpi)) {
    return `${(value * 100).toFixed(2)}%`
  }

  // Ratio KPIs
  if (kpi === 'roas') {
    return `${value.toFixed(2)}x`
  }

  // Frequency
  if (kpi === 'frequency') {
    return value.toFixed(2)
  }

  // Large numbers (reach, impressions)
  if (['reach'].includes(kpi)) {
    if (value >= 1000000) {
      return `${(value / 1000000).toFixed(1)}M`
    } else if (value >= 1000) {
      return `${(value / 1000).toFixed(1)}K`
    }
  }

  return value.toLocaleString()
}

const getKpiDescription = (kpi: string): string => {
  const descriptions: Record<string, string> = {
    cpm: 'Cost per 1,000 impressions',
    cpl: 'Cost per lead generated',
    cpc: 'Cost per click',
    ctr: 'Click-through rate',
    cvr: 'Conversion rate',
    roas: 'Return on ad spend',
    cpa: 'Cost per acquisition',
    aov: 'Average order value',
    cost_per_call: 'Cost per phone call',
    call_conversion_rate: 'Calls per click',
    reach: 'Unique users reached',
    frequency: 'Average impressions per user',
    vtr: 'Video view rate'
  }
  return descriptions[kpi] || ''
}
</script>
