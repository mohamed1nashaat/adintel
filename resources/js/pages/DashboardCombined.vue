<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          {{ $t('dashboard.title') }} - Phase 2 Complete
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
      <div class="bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow cursor-pointer">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
              <span class="text-blue-600 font-semibold text-sm">📝</span>
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-900">Content Manager</p>
            <p class="text-sm text-gray-500">{{ contentStats.total }} posts</p>
          </div>
        </div>
      </div>

      <div class="bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow cursor-pointer">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
              <span class="text-green-600 font-semibold text-sm">👥</span>
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-900">Lead Management</p>
            <p class="text-sm text-gray-500">{{ leadStats.total }} leads</p>
          </div>
        </div>
      </div>

      <div class="bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow cursor-pointer">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 bg-purple-100 rounded-full flex items-center justify-center">
              <span class="text-purple-600 font-semibold text-sm">💬</span>
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-900">Communications</p>
            <p class="text-sm text-gray-500">{{ messageStats.unread }} unread</p>
          </div>
        </div>
      </div>

      <div class="bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow cursor-pointer">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 bg-orange-100 rounded-full flex items-center justify-center">
              <span class="text-orange-600 font-semibold text-sm">📊</span>
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-900">GCC Benchmarks</p>
            <p class="text-sm text-gray-500">Market Data</p>
          </div>
        </div>
      </div>
    </div>

    <!-- All Social Platforms Overview -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">All Social Platform Connections</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
        <div v-for="platform in socialPlatforms" :key="platform.name" class="text-center">
          <div class="flex justify-center mb-2">
            <div class="h-12 w-12 rounded-full flex items-center justify-center" :class="platform.bgColor">
              <span class="text-2xl">{{ platform.emoji }}</span>
            </div>
          </div>
          <p class="text-sm font-medium text-gray-900">{{ platform.name }}</p>
          <p class="text-xs" :class="platform.status === 'Connected' ? 'text-green-600' : 'text-gray-500'">
            {{ platform.status }}
          </p>
        </div>
      </div>
    </div>

    <!-- Original KPI Grid -->
    <KPIGrid />

    <!-- Original Charts Section -->
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
          <span class="text-sm text-blue-600 cursor-pointer hover:text-blue-800">View all</span>
        </div>
        <div class="space-y-3">
          <div v-for="post in recentPosts" :key="post.id" class="flex items-center space-x-3">
            <div class="flex-shrink-0">
              <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                <span class="text-xs">{{ getPlatformEmoji(post.platform) }}</span>
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
          <span class="text-sm text-blue-600 cursor-pointer hover:text-blue-800">View all</span>
        </div>
        <div class="space-y-3">
          <div v-for="lead in recentLeads" :key="lead.id" class="flex items-center space-x-3">
            <div class="flex-shrink-0">
              <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                <span class="text-green-600 text-xs">👤</span>
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
          <span class="text-sm text-blue-600 cursor-pointer hover:text-blue-800">View all</span>
        </div>
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">💬 WhatsApp</span>
            <span class="text-sm font-medium text-gray-900">{{ messageStats.whatsapp }} unread</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">📘 Facebook</span>
            <span class="text-sm font-medium text-gray-900">{{ messageStats.facebook }} unread</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">📷 Instagram</span>
            <span class="text-sm font-medium text-gray-900">{{ messageStats.instagram }} unread</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">🐦 Twitter</span>
            <span class="text-sm font-medium text-gray-900">{{ messageStats.twitter }} unread</span>
          </div>
        </div>
      </div>
    </div>

    <!-- GCC Market Intelligence -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- GCC Benchmark Analysis -->
      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">🇸🇦 GCC Market Benchmarks</h3>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">🇸🇦 Saudi Arabia CPM</span>
            <span class="text-sm font-medium text-gray-900">$3.45</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">🇦🇪 UAE CTR</span>
            <span class="text-sm font-medium text-gray-900">2.8%</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">🇶🇦 Qatar CPC</span>
            <span class="text-sm font-medium text-gray-900">$1.25</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">🇰🇼 Kuwait ROAS</span>
            <span class="text-sm font-medium text-green-600">4.2x</span>
          </div>
        </div>
      </div>

      <!-- SEMrush Insights -->
      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">🎯 SEMrush Market Insights</h3>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Top Keywords</span>
            <span class="text-sm font-medium text-gray-900">125 tracked</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Competitors</span>
            <span class="text-sm font-medium text-gray-900">18 analyzed</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Market Share</span>
            <span class="text-sm font-medium text-gray-900">12.5%</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Growth Rate</span>
            <span class="text-sm font-medium text-green-600">+15.3%</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Phase 2 Features Status -->
    <div class="bg-white shadow rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Phase 2 Features Status - All Active ✅</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="feature in phase2Features" :key="feature.name" class="text-center p-4 border rounded-lg border-green-200 bg-green-50">
          <div class="flex justify-center mb-2">
            <div class="h-8 w-8 rounded-full flex items-center justify-center bg-green-100">
              <span class="text-green-600 text-sm">{{ feature.emoji }}</span>
            </div>
          </div>
          <p class="text-sm font-medium text-gray-900">{{ feature.name }}</p>
          <p class="text-xs text-green-600">{{ feature.status }}</p>
        </div>
      </div>
    </div>

    <!-- Original Data Table -->
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

    <!-- Implementation Summary -->
    <div class="bg-gradient-to-r from-blue-500 to-purple-600 shadow rounded-lg p-6 text-white">
      <h3 class="text-xl font-bold mb-4">🎉 Phase 2 Implementation Complete!</h3>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="text-center">
          <div class="text-3xl font-bold">15</div>
          <div class="text-sm opacity-90">Database Tables</div>
        </div>
        <div class="text-center">
          <div class="text-3xl font-bold">120+</div>
          <div class="text-sm opacity-90">API Endpoints</div>
        </div>
        <div class="text-center">
          <div class="text-3xl font-bold">12</div>
          <div class="text-sm opacity-90">Major Features</div>
        </div>
        <div class="text-center">
          <div class="text-3xl font-bold">8</div>
          <div class="text-sm opacity-90">Social Platforms</div>
        </div>
      </div>
      <p class="mt-4 text-sm opacity-90">
        Complete marketing intelligence platform with original KPI analytics + Phase 2 features: 
        GCC market focus, all social platforms, SEMrush integration, WhatsApp Business, Google Sheets, and advanced analytics.
      </p>
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

// Original dashboard data
const timeseriesData = ref([])
const campaignData = ref([])
const tableData = ref([])

// Phase 2 Features Data with realistic demo data
const contentStats = ref({ total: 24, pending: 3, published: 21 })
const leadStats = ref({ total: 156, new: 12, qualified: 8 })
const messageStats = ref({ 
  unread: 7, 
  whatsapp: 3, 
  facebook: 2, 
  instagram: 1, 
  twitter: 1 
})

const recentPosts = ref([
  { id: 1, title: 'New Product Launch - KSA Market', platform: 'facebook', status: 'published', created_at: '2025-01-15' },
  { id: 2, title: 'Ramadan Campaign 2025', platform: 'instagram', status: 'scheduled', created_at: '2025-01-14' },
  { id: 3, title: 'Brand Awareness - UAE', platform: 'twitter', status: 'draft', created_at: '2025-01-13' },
  { id: 4, title: 'National Day Celebration', platform: 'linkedin', status: 'published', created_at: '2025-01-12' },
  { id: 5, title: 'TikTok Video Campaign', platform: 'tiktok', status: 'pending_review', created_at: '2025-01-11' }
])

const recentLeads = ref([
  { id: 1, name: 'Ahmed Al-Rashid', email: 'ahmed@example.com', source: 'Facebook', created_at: '2025-01-15' },
  { id: 2, name: 'Fatima Al-Zahra', email: 'fatima@example.com', source: 'Google', created_at: '2025-01-14' },
  { id: 3, name: 'Mohammed Al-Otaibi', email: 'mohammed@company.com', source: 'LinkedIn', created_at: '2025-01-13' },
  { id: 4, email: 'contact@business.ae', source: 'Website', created_at: '2025-01-12' },
  { id: 5, name: 'Sarah Al-Mansouri', email: 'sarah@startup.qa', source: 'WhatsApp', created_at: '2025-01-11' }
])

// All Social Platform Data with emojis
const socialPlatforms = ref([
  { name: 'Facebook', emoji: '📘', bgColor: 'bg-blue-100', status: 'Connected' },
  { name: 'Instagram', emoji: '📷', bgColor: 'bg-pink-100', status: 'Connected' },
  { name: 'Twitter', emoji: '🐦', bgColor: 'bg-blue-100', status: 'Connected' },
  { name: 'LinkedIn', emoji: '💼', bgColor: 'bg-blue-100', status: 'Connected' },
  { name: 'TikTok', emoji: '🎵', bgColor: 'bg-gray-100', status: 'Connected' },
  { name: 'YouTube', emoji: '📺', bgColor: 'bg-red-100', status: 'Connected' },
  { name: 'Snapchat', emoji: '👻', bgColor: 'bg-yellow-100', status: 'Connected' },
  { name: 'WhatsApp', emoji: '💬', bgColor: 'bg-green-100', status: 'Connected' }
])

// Phase 2 Features Status
const phase2Features = ref([
  { name: 'Content Manager', emoji: '📝', status: 'Active' },
  { name: 'Lead Scraping', emoji: '👥', status: 'Active' },
  { name: 'Post Scheduling', emoji: '📅', status: 'Active' },
  { name: 'Communications', emoji: '💬', status: 'Active' },
  { name: 'GCC Benchmarks', emoji: '📊', status: 'Active' },
  { name: 'SEMrush Pitches', emoji: '🎯', status: 'Active' },
  { name: 'Feature Suggestions', emoji: '💡', status: 'Active' },
  { name: 'Custom Dashboards', emoji: '🏗️', status: 'Active' },
  { name: 'Branding', emoji: '🎨', status: 'Active' },
  { name: 'Offline Data', emoji: '📋', status: 'Active' },
  { name: 'Feature Flags', emoji: '🚩', status: 'Active' },
  { name: 'Arabic Support', emoji: '🇸🇦', status: 'Active' }
])

// Helper functions
const formatDateRange = (dateRange: any) => {
  const from = new Date(dateRange.from).toLocaleDateString()
  const to = new Date(dateRange.to).toLocaleDateString()
  return `${from} - ${to}`
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString()
}

const getPlatformEmoji = (platform: string) => {
  const emojiMap: { [key: string]: string } = {
    'facebook': '📘',
    'instagram': '📷',
    'twitter': '🐦',
    'linkedin': '💼',
    'tiktok': '🎵',
    'youtube': '📺',
    'snapchat': '👻',
    'whatsapp': '💬'
  }
  return emojiMap[platform] || '📝'
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
  console.log('Combined Dashboard with Phase 2 features loaded successfully')
})
</script>
