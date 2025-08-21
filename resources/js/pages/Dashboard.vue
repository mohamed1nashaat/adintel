<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          {{ $t('dashboard.title') }}
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          {{ $t(`objectives.${dashboardStore.objective}`) }} • 
          {{ formatDateRange(dashboardStore.dateRange) }}
        </p>
      </div>
      <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
        <DateRangePicker />
        <FilterBar />
        <button
          @click="refreshData"
          :disabled="dashboardStore.loading"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          <ArrowPathIcon 
            :class="['h-4 w-4 mr-2', dashboardStore.loading ? 'animate-spin' : '']" 
            aria-hidden="true" 
          />
          {{ $t('dashboard.refresh') }}
        </button>
        <ExportButton />
      </div>
    </div>

    <!-- Phase 2 Features Quick Access -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <router-link to="/content" class="bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <DocumentTextIcon class="h-8 w-8 text-blue-600" />
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-900">Content Manager</p>
            <p class="text-sm text-gray-500">{{ contentStats.total }} posts</p>
          </div>
        </div>
      </router-link>

      <router-link to="/leads" class="bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <UserGroupIcon class="h-8 w-8 text-green-600" />
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-900">Lead Management</p>
            <p class="text-sm text-gray-500">{{ leadStats.total }} leads</p>
          </div>
        </div>
      </router-link>

      <router-link to="/communications" class="bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ChatBubbleLeftRightIcon class="h-8 w-8 text-purple-600" />
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-900">Communications</p>
            <p class="text-sm text-gray-500">{{ messageStats.unread }} unread</p>
          </div>
        </div>
      </router-link>

      <router-link to="/benchmarks" class="bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ChartBarIcon class="h-8 w-8 text-orange-600" />
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-900">Benchmarks</p>
            <p class="text-sm text-gray-500">GCC Market Data</p>
          </div>
        </div>
      </router-link>
    </div>

    <!-- Social Platform Overview -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Social Platform Performance</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
        <div v-for="platform in socialPlatforms" :key="platform.name" class="text-center">
          <div class="flex justify-center mb-2">
            <component :is="platform.icon" class="h-8 w-8" :class="platform.color" />
          </div>
          <p class="text-sm font-medium text-gray-900">{{ platform.name }}</p>
          <p class="text-xs text-gray-500">{{ platform.status }}</p>
        </div>
      </div>
    </div>

    <!-- KPI Grid -->
    <KPIGrid />

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Primary Chart -->
      <ChartCard
        :title="getPrimaryChartTitle()"
        :chart-data="timeseriesData"
        :loading="dashboardStore.loading"
        :metric="getPrimaryMetric()"
        chart-type="line"
      />

      <!-- Secondary Chart -->
      <ChartCard
        :title="getSecondaryChartTitle()"
        :chart-data="campaignData"
        :loading="dashboardStore.loading"
        :metric="getPrimaryMetric()"
        chart-type="bar"
      />
    </div>

    <!-- Phase 2 Features Dashboard -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Recent Content Posts -->
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">Recent Posts</h3>
          <router-link to="/content" class="text-sm text-blue-600 hover:text-blue-800">View all</router-link>
        </div>
        <div class="space-y-3">
          <div v-for="post in recentPosts" :key="post.id" class="flex items-center space-x-3">
            <div class="flex-shrink-0">
              <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                <component :is="getPlatformIcon(post.platform)" class="h-4 w-4" />
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">{{ post.title }}</p>
              <p class="text-xs text-gray-500">{{ post.status }} • {{ formatDate(post.created_at) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Leads -->
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">Recent Leads</h3>
          <router-link to="/leads" class="text-sm text-blue-600 hover:text-blue-800">View all</router-link>
        </div>
        <div class="space-y-3">
          <div v-for="lead in recentLeads" :key="lead.id" class="flex items-center space-x-3">
            <div class="flex-shrink-0">
              <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                <UserIcon class="h-4 w-4 text-green-600" />
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900">{{ lead.name || lead.email }}</p>
              <p class="text-xs text-gray-500">{{ lead.source }} • {{ formatDate(lead.created_at) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Communication Summary -->
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">Messages</h3>
          <router-link to="/communications" class="text-sm text-blue-600 hover:text-blue-800">View all</router-link>
        </div>
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">WhatsApp</span>
            <span class="text-sm font-medium text-gray-900">{{ messageStats.whatsapp }} unread</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Facebook</span>
            <span class="text-sm font-medium text-gray-900">{{ messageStats.facebook }} unread</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Instagram</span>
            <span class="text-sm font-medium text-gray-900">{{ messageStats.instagram }} unread</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Twitter</span>
            <span class="text-sm font-medium text-gray-900">{{ messageStats.twitter }} unread</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
          Campaign Performance
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
          Detailed breakdown by campaign
        </p>
      </div>
      <DataTable :data="tableData" :loading="dashboardStore.loading" />
    </div>
  </div>
</template>

<script setup lang="ts">
import ChartCard from '@/components/ChartCard.vue'
import DataTable from '@/components/DataTable.vue'
import DateRangePicker from '@/components/DateRangePicker.vue'
import ExportButton from '@/components/ExportButton.vue'
import FilterBar from '@/components/FilterBar.vue'
import KPIGrid from '@/components/KPIGrid.vue'
import { useDashboardStore } from '@/stores/dashboard'
import { ArrowPathIcon } from '@heroicons/vue/24/outline'
import { onMounted, ref, watch } from 'vue'

const dashboardStore = useDashboardStore()

const timeseriesData = ref([])
const campaignData = ref([])
const tableData = ref([])

const formatDateRange = (dateRange: any) => {
  const from = new Date(dateRange.from).toLocaleDateString()
  const to = new Date(dateRange.to).toLocaleDateString()
  return `${from} - ${to}`
}

const getPrimaryChartTitle = () => {
  switch (dashboardStore.objective) {
    case 'awareness':
      return 'Impressions Over Time'
    case 'leads':
      return 'Leads Over Time'
    case 'sales':
      return 'Revenue Over Time'
    case 'calls':
      return 'Calls Over Time'
    default:
      return 'Performance Over Time'
  }
}

const getSecondaryChartTitle = () => {
  switch (dashboardStore.objective) {
    case 'awareness':
      return 'CPM by Campaign'
    case 'leads':
      return 'CPL by Campaign'
    case 'sales':
      return 'ROAS by Campaign'
    case 'calls':
      return 'Cost per Call by Campaign'
    default:
      return 'Performance by Campaign'
  }
}

const getPrimaryMetric = () => {
  return dashboardStore.primaryKpis[0] || 'cpm'
}

const refreshData = async () => {
  await Promise.all([
    dashboardStore.fetchSummary(),
    fetchTimeseriesData(),
    fetchCampaignData(),
    fetchTableData()
  ])
}

const fetchTimeseriesData = async () => {
  try {
    const metric = getPrimaryMetric()
    const data = await dashboardStore.fetchTimeseries(metric, 'date')
    timeseriesData.value = data
  } catch (error) {
    console.error('Error fetching timeseries data:', error)
  }
}

const fetchCampaignData = async () => {
  try {
    const metric = getPrimaryMetric()
    const data = await dashboardStore.fetchTimeseries(metric, 'campaign')
    campaignData.value = data
  } catch (error) {
    console.error('Error fetching campaign data:', error)
  }
}

const fetchTableData = async () => {
  try {
    // This would fetch detailed campaign data for the table
    // For now, we'll use mock data
    tableData.value = []
  } catch (error) {
    console.error('Error fetching table data:', error)
  }
}

// Watch for objective changes and refresh data
watch(() => dashboardStore.objective, () => {
  refreshData()
})

// Watch for date range changes and refresh data
watch(() => dashboardStore.dateRange, () => {
  refreshData()
}, { deep: true })

// Watch for filter changes and refresh data
watch(() => dashboardStore.filters, () => {
  refreshData()
}, { deep: true })

onMounted(() => {
  refreshData()
})
</script>
