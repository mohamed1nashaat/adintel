<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          Dashboard
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Performance Overview • {{ formatDateRange() }}
        </p>
      </div>
      <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
        <button
          @click="refreshData"
          :disabled="loading"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          <ArrowPathIcon 
            :class="['h-4 w-4 mr-2', loading ? 'animate-spin' : '']" 
            aria-hidden="true" 
          />
          Refresh
        </button>
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
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <CurrencyDollarIcon class="h-6 w-6 text-gray-400" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Spend</dt>
                <dd class="text-lg font-medium text-gray-900">${{ kpiData.totalSpend.toLocaleString() }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <EyeIcon class="h-6 w-6 text-gray-400" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Impressions</dt>
                <dd class="text-lg font-medium text-gray-900">{{ kpiData.impressions.toLocaleString() }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <CursorArrowRaysIcon class="h-6 w-6 text-gray-400" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Clicks</dt>
                <dd class="text-lg font-medium text-gray-900">{{ kpiData.clicks.toLocaleString() }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ChartBarIcon class="h-6 w-6 text-gray-400" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">CTR</dt>
                <dd class="text-lg font-medium text-gray-900">{{ kpiData.ctr }}%</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Primary Chart -->
      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Performance Over Time</h3>
        <div class="h-64 flex items-center justify-center bg-gray-50 rounded">
          <div v-if="loading" class="text-gray-500">Loading chart data...</div>
          <div v-else class="text-gray-500">Chart will be rendered here</div>
        </div>
      </div>

      <!-- Secondary Chart -->
      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Campaign Performance</h3>
        <div class="h-64 flex items-center justify-center bg-gray-50 rounded">
          <div v-if="loading" class="text-gray-500">Loading chart data...</div>
          <div v-else class="text-gray-500">Chart will be rendered here</div>
        </div>
      </div>
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
                <DocumentTextIcon class="h-4 w-4" />
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
      <div class="border-t border-gray-200">
        <div v-if="loading" class="p-4 text-center text-gray-500">Loading campaign data...</div>
        <div v-else-if="tableData.length === 0" class="p-4 text-center text-gray-500">No campaign data available</div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaign</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Platform</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Spend</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Impressions</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clicks</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CTR</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="campaign in tableData" :key="campaign.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ campaign.name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ campaign.platform }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${{ campaign.spend }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ campaign.impressions }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ campaign.clicks }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ campaign.ctr }}%</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  ArrowPathIcon,
  ChartBarIcon,
  ChatBubbleLeftRightIcon,
  CurrencyDollarIcon,
  CursorArrowRaysIcon,
  DocumentTextIcon,
  EyeIcon,
  UserGroupIcon,
  UserIcon
} from '@heroicons/vue/24/outline'
import { onMounted, ref } from 'vue'

const loading = ref(false)

// Mock data
const contentStats = ref({ total: 24 })
const leadStats = ref({ total: 156 })
const messageStats = ref({ 
  unread: 7,
  whatsapp: 3,
  facebook: 2,
  instagram: 1,
  twitter: 1
})

const kpiData = ref({
  totalSpend: 45230,
  impressions: 2847392,
  clicks: 89456,
  ctr: 3.14
})

const socialPlatforms = ref([
  { name: 'Facebook', status: 'Connected', icon: 'div', color: 'text-blue-600' },
  { name: 'Instagram', status: 'Connected', icon: 'div', color: 'text-pink-600' },
  { name: 'Twitter', status: 'Connected', icon: 'div', color: 'text-blue-400' },
  { name: 'LinkedIn', status: 'Connected', icon: 'div', color: 'text-blue-700' },
  { name: 'TikTok', status: 'Connected', icon: 'div', color: 'text-black' },
  { name: 'Snapchat', status: 'Connected', icon: 'div', color: 'text-yellow-400' },
  { name: 'YouTube', status: 'Connected', icon: 'div', color: 'text-red-600' },
  { name: 'WhatsApp', status: 'Connected', icon: 'div', color: 'text-green-600' }
])

const recentPosts = ref([
  { id: 1, title: 'Summer Sale Campaign', status: 'Published', platform: 'facebook', created_at: '2025-01-15T10:30:00Z' },
  { id: 2, title: 'Product Launch Teaser', status: 'Scheduled', platform: 'instagram', created_at: '2025-01-15T09:15:00Z' },
  { id: 3, title: 'Customer Testimonial', status: 'Draft', platform: 'twitter', created_at: '2025-01-15T08:45:00Z' }
])

const recentLeads = ref([
  { id: 1, name: 'Ahmed Al-Rashid', email: 'ahmed@example.com', source: 'Facebook Ads', created_at: '2025-01-15T11:20:00Z' },
  { id: 2, name: 'Fatima Al-Zahra', email: 'fatima@example.com', source: 'Google Ads', created_at: '2025-01-15T10:45:00Z' },
  { id: 3, email: 'mohammed@example.com', source: 'Instagram', created_at: '2025-01-15T09:30:00Z' }
])

const tableData = ref([
  { id: 1, name: 'Summer Sale', platform: 'Facebook', spend: '1,250', impressions: '45,230', clicks: '1,420', ctr: '3.14' },
  { id: 2, name: 'Product Launch', platform: 'Instagram', spend: '890', impressions: '32,150', clicks: '980', ctr: '3.05' },
  { id: 3, name: 'Brand Awareness', platform: 'Twitter', spend: '650', impressions: '28,900', clicks: '750', ctr: '2.60' }
])

const formatDateRange = () => {
  const today = new Date()
  const lastWeek = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000)
  return `${lastWeek.toLocaleDateString()} - ${today.toLocaleDateString()}`
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString()
}

const refreshData = async () => {
  loading.value = true
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    console.log('Dashboard data refreshed')
  } catch (error) {
    console.error('Error refreshing dashboard data:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  refreshData()
})
</script>
