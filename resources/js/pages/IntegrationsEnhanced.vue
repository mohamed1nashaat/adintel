<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          Platform Integrations
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Connect and manage all your social media and advertising platforms
        </p>
      </div>
      <div class="mt-4 flex md:mt-0 md:ml-4">
        <button
          @click="refreshIntegrations"
          :disabled="loading"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          <ArrowPathIcon 
            :class="['h-4 w-4 mr-2', loading ? 'animate-spin' : '']" 
            aria-hidden="true" 
          />
          Refresh
        </button>
        <button
          @click="showAddIntegration = true"
          class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <PlusIcon class="h-4 w-4 mr-2" aria-hidden="true" />
          Add Integration
        </button>
      </div>
    </div>

    <!-- Integration Stats -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <CheckCircleIcon class="h-6 w-6 text-green-400" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Connected Platforms</dt>
                <dd class="text-lg font-medium text-gray-900">{{ connectedCount }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ClockIcon class="h-6 w-6 text-yellow-400" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Last Sync</dt>
                <dd class="text-lg font-medium text-gray-900">{{ lastSyncTime }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ChartBarIcon class="h-6 w-6 text-blue-400" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Active Campaigns</dt>
                <dd class="text-lg font-medium text-gray-900">{{ activeCampaigns }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <CurrencyDollarIcon class="h-6 w-6 text-green-400" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Spend (30d)</dt>
                <dd class="text-lg font-medium text-gray-900">${{ totalSpend.toLocaleString() }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Platform Integrations Grid -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
          Available Platforms
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
          Connect your advertising and social media accounts
        </p>
      </div>
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 p-6">
        <!-- Facebook -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
              <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-gray-900">Facebook Ads</h3>
                <p class="text-xs text-gray-500">Meta Business Platform</p>
              </div>
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
              Connected
            </span>
          </div>
          <div class="space-y-2 text-sm text-gray-600">
            <div class="flex justify-between">
              <span>Ad Accounts:</span>
              <span class="font-medium">3</span>
            </div>
            <div class="flex justify-between">
              <span>Active Campaigns:</span>
              <span class="font-medium">12</span>
            </div>
            <div class="flex justify-between">
              <span>Last Sync:</span>
              <span class="font-medium">2 min ago</span>
            </div>
          </div>
          <div class="mt-4 flex space-x-2">
            <button class="flex-1 bg-blue-600 text-white text-xs py-2 px-3 rounded-md hover:bg-blue-700">
              Manage
            </button>
            <button class="flex-1 bg-gray-100 text-gray-700 text-xs py-2 px-3 rounded-md hover:bg-gray-200">
              Sync Now
            </button>
          </div>
        </div>

        <!-- Google Ads -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
              <div class="w-10 h-10 bg-red-600 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                  <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                  <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                  <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-gray-900">Google Ads</h3>
                <p class="text-xs text-gray-500">Google Advertising</p>
              </div>
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
              Connected
            </span>
          </div>
          <div class="space-y-2 text-sm text-gray-600">
            <div class="flex justify-between">
              <span>Ad Accounts:</span>
              <span class="font-medium">2</span>
            </div>
            <div class="flex justify-between">
              <span>Active Campaigns:</span>
              <span class="font-medium">8</span>
            </div>
            <div class="flex justify-between">
              <span>Last Sync:</span>
              <span class="font-medium">5 min ago</span>
            </div>
          </div>
          <div class="mt-4 flex space-x-2">
            <button class="flex-1 bg-red-600 text-white text-xs py-2 px-3 rounded-md hover:bg-red-700">
              Manage
            </button>
            <button class="flex-1 bg-gray-100 text-gray-700 text-xs py-2 px-3 rounded-md hover:bg-gray-200">
              Sync Now
            </button>
          </div>
        </div>

        <!-- TikTok Ads -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
              <div class="w-10 h-10 bg-black rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-gray-900">TikTok Ads</h3>
                <p class="text-xs text-gray-500">TikTok for Business</p>
              </div>
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
              Connected
            </span>
          </div>
          <div class="space-y-2 text-sm text-gray-600">
            <div class="flex justify-between">
              <span>Ad Accounts:</span>
              <span class="font-medium">1</span>
            </div>
            <div class="flex justify-between">
              <span>Active Campaigns:</span>
              <span class="font-medium">5</span>
            </div>
            <div class="flex justify-between">
              <span>Last Sync:</span>
              <span class="font-medium">1 min ago</span>
            </div>
          </div>
          <div class="mt-4 flex space-x-2">
            <button class="flex-1 bg-black text-white text-xs py-2 px-3 rounded-md hover:bg-gray-800">
              Manage
            </button>
            <button class="flex-1 bg-gray-100 text-gray-700 text-xs py-2 px-3 rounded-md hover:bg-gray-200">
              Sync Now
            </button>
          </div>
        </div>

        <!-- WhatsApp Business -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
              <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.465 3.488"/>
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-gray-900">WhatsApp Business</h3>
                <p class="text-xs text-gray-500">WhatsApp Business API</p>
              </div>
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
              Connected
            </span>
          </div>
          <div class="space-y-2 text-sm text-gray-600">
            <div class="flex justify-between">
              <span>Phone Numbers:</span>
              <span class="font-medium">2</span>
            </div>
            <div class="flex justify-between">
              <span>Messages Today:</span>
              <span class="font-medium">24</span>
            </div>
            <div class="flex justify-between">
              <span>Response Time:</span>
              <span class="font-medium">2.5m</span>
            </div>
          </div>
          <div class="mt-4 flex space-x-2">
            <button class="flex-1 bg-green-500 text-white text-xs py-2 px-3 rounded-md hover:bg-green-600">
              Manage
            </button>
            <button class="flex-1 bg-gray-100 text-gray-700 text-xs py-2 px-3 rounded-md hover:bg-gray-200">
              Settings
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  ArrowPathIcon,
  ChartBarIcon,
  CheckCircleIcon,
  ClockIcon,
  CurrencyDollarIcon,
  PlusIcon
} from '@heroicons/vue/24/outline'
import { computed, onMounted, ref } from 'vue'

// Reactive data
const loading = ref(false)
const showAddIntegration = ref(false)
const integrations = ref([
  { platform: 'facebook', connected: true, accounts: 3, campaigns: 12, lastSync: '2 min ago' },
  { platform: 'google', connected: true, accounts: 2, campaigns: 8, lastSync: '5 min ago' },
  { platform: 'tiktok', connected: true, accounts: 1, campaigns: 5, lastSync: '1 min ago' },
  { platform: 'whatsapp', connected: true, accounts: 2, campaigns: 0, lastSync: 'Real-time' },
  { platform: 'snapchat', connected: false, accounts: 0, campaigns: 0, lastSync: 'Never' },
  { platform: 'linkedin', connected: true, accounts: 1, campaigns: 3, lastSync: '10 min ago' },
  { platform: 'youtube', connected: true, accounts: 1, campaigns: 4, lastSync: '3 min ago' },
  { platform: 'instagram', connected: true, accounts: 2, campaigns: 6, lastSync: '2 min ago' }
])

// Computed properties
const connectedCount = computed(() => {
  return integrations.value.filter(i => i.connected).length
})

const lastSyncTime = computed(() => {
  const connected = integrations.value.filter(i => i.connected)
  if (connected.length === 0) return 'Never'
  return '2 min ago' // Most recent sync
})

const activeCampaigns = computed(() => {
  return integrations.value.reduce((total, integration) => {
    return total + (integration.connected ? integration.campaigns : 0)
  }, 0)
})

const totalSpend = computed(() => {
  // Mock calculation based on connected platforms
  return connectedCount.value * 15000 + Math.floor(Math.random() * 10000)
})

// Methods
const refreshIntegrations = async () => {
  loading.value = true
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Update last sync times
    integrations.value.forEach(integration => {
      if (integration.connected) {
        integration.lastSync = 'Just now'
      }
    })
  } catch (error) {
    console.error('Error refreshing integrations:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  // Load integrations data on component mount
  console.log('Integrations page loaded with enhanced features')
})
</script>
