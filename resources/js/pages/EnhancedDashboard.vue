<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          AdIntel Dashboard - Phase 2
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Complete Marketing Intelligence Platform • GCC Market Focus
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
      <div class="bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow cursor-pointer">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <DocumentTextIcon class="h-8 w-8 text-blue-600" />
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
            <UserGroupIcon class="h-8 w-8 text-green-600" />
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
            <ChatBubbleLeftRightIcon class="h-8 w-8 text-purple-600" />
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
            <ChartBarIcon class="h-8 w-8 text-orange-600" />
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-900">Benchmarks</p>
            <p class="text-sm text-gray-500">GCC Market Data</p>
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
              <component :is="platform.icon" class="h-6 w-6" :class="platform.color" />
            </div>
          </div>
          <p class="text-sm font-medium text-gray-900">{{ platform.name }}</p>
          <p class="text-xs" :class="platform.status === 'Connected' ? 'text-green-600' : 'text-gray-500'">
            {{ platform.status }}
          </p>
        </div>
      </div>
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
          <span class="text-sm text-blue-600 cursor-pointer hover:text-blue-800">View all</span>
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
          <span class="text-sm text-blue-600 cursor-pointer hover:text-blue-800">View all</span>
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

    <!-- Additional Phase 2 Features -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Benchmark Analysis -->
      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">GCC Market Benchmarks</h3>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Saudi Arabia CPM</span>
            <span class="text-sm font-medium text-gray-900">$3.45</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">UAE CTR</span>
            <span class="text-sm font-medium text-gray-900">2.8%</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Qatar CPC</span>
            <span class="text-sm font-medium text-gray-900">$1.25</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Kuwait ROAS</span>
            <span class="text-sm font-medium text-gray-900">4.2x</span>
          </div>
        </div>
      </div>

      <!-- SEMrush Insights -->
      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">SEMrush Market Insights</h3>
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

    <!-- Feature Status Grid -->
    <div class="bg-white shadow rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Phase 2 Features Status</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="feature in phase2Features" :key="feature.name" class="text-center p-4 border rounded-lg">
          <div class="flex justify-center mb-2">
            <div class="h-8 w-8 rounded-full flex items-center justify-center" :class="feature.status === 'Active' ? 'bg-green-100' : 'bg-gray-100'">
              <component :is="feature.icon" class="h-4 w-4" :class="feature.status === 'Active' ? 'text-green-600' : 'text-gray-400'" />
            </div>
          </div>
          <p class="text-sm font-medium text-gray-900">{{ feature.name }}</p>
          <p class="text-xs" :class="feature.status === 'Active' ? 'text-green-600' : 'text-gray-500'">
            {{ feature.status }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
    ArrowPathIcon,
    BellIcon,
    BuildingOfficeIcon,
    ChartBarIcon,
    ChatBubbleLeftRightIcon,
    CogIcon,
    DocumentTextIcon,
    PresentationChartBarIcon,
    UserGroupIcon,
    UserIcon
} from '@heroicons/vue/24/outline'
import { onMounted, ref } from 'vue'

const loading = ref(false)

// Phase 2 Features Data
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

// All Social Platform Data
const socialPlatforms = ref([
  { name: 'Facebook', icon: DocumentTextIcon, color: 'text-blue-600', bgColor: 'bg-blue-100', status: 'Connected' },
  { name: 'Instagram', icon: DocumentTextIcon, color: 'text-pink-600', bgColor: 'bg-pink-100', status: 'Connected' },
  { name: 'Twitter', icon: DocumentTextIcon, color: 'text-blue-400', bgColor: 'bg-blue-100', status: 'Connected' },
  { name: 'LinkedIn', icon: DocumentTextIcon, color: 'text-blue-700', bgColor: 'bg-blue-100', status: 'Connected' },
  { name: 'TikTok', icon: DocumentTextIcon, color: 'text-black', bgColor: 'bg-gray-100', status: 'Connected' },
  { name: 'YouTube', icon: DocumentTextIcon, color: 'text-red-600', bgColor: 'bg-red-100', status: 'Connected' },
  { name: 'Snapchat', icon: DocumentTextIcon, color: 'text-yellow-500', bgColor: 'bg-yellow-100', status: 'Connected' },
  { name: 'WhatsApp', icon: ChatBubbleLeftRightIcon, color: 'text-green-600', bgColor: 'bg-green-100', status: 'Connected' }
])

// Phase 2 Features Status
const phase2Features = ref([
  { name: 'Content Manager', icon: DocumentTextIcon, status: 'Active' },
  { name: 'Lead Scraping', icon: UserGroupIcon, status: 'Active' },
  { name: 'Post Scheduling', icon: CogIcon, status: 'Active' },
  { name: 'Communications', icon: ChatBubbleLeftRightIcon, status: 'Active' },
  { name: 'Benchmarks', icon: ChartBarIcon, status: 'Active' },
  { name: 'Pitch Generator', icon: PresentationChartBarIcon, status: 'Active' },
  { name: 'Feature Suggestions', icon: BellIcon, status: 'Active' },
  { name: 'Custom Dashboards', icon: BuildingOfficeIcon, status: 'Active' },
  { name: 'Branding', icon: BuildingOfficeIcon, status: 'Active' },
  { name: 'Offline Data', icon: DocumentTextIcon, status: 'Active' },
  { name: 'Feature Flags', icon: CogIcon, status: 'Active' },
  { name: 'GCC Analytics', icon: ChartBarIcon, status: 'Active' }
])

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString()
}

const refreshData = async () => {
  loading.value = true
  try {
    // Simulate API calls
    await new Promise(resolve => setTimeout(resolve, 1000))
    console.log('Phase 2 data refreshed')
  } catch (error) {
    console.error('Error refreshing data:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  console.log('Enhanced Dashboard with Phase 2 features loaded')
})
</script>
