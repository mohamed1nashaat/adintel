<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Integrations</h1>
        <p class="text-gray-600">Connect your ad platforms to start importing data</p>
      </div>
      <button
        @click="showAddModal = true"
        class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 flex items-center gap-2"
      >
        <PlusIcon class="w-5 h-5" />
        Add Integration
      </button>
    </div>

    <!-- Available Platforms -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">Available Platforms</h2>
        <p class="text-sm text-gray-600">Connect to these advertising platforms to import your campaign data</p>
      </div>
      
      <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Facebook Ads -->
        <FacebookIntegrationCard />
        
        <!-- Google Ads -->
        <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                  <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                  <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                  <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">Google Ads</h3>
                <p class="text-sm text-gray-600">Connect Google Ads campaigns</p>
              </div>
            </div>
          </div>
          
          <button
            @click="connectPlatform('google')"
            :disabled="connecting === 'google'"
            class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <svg v-if="connecting === 'google'" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <LinkIcon class="w-4 h-4" />
            {{ connecting === 'google' ? 'Connecting...' : 'Connect with Google' }}
          </button>
        </div>

        <!-- Snapchat Ads -->
        <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.097.118.112.221.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.748-1.378 0 0-.599 2.282-.744 2.840-.282 1.084-1.064 2.456-1.549 3.235C9.584 23.815 10.77 24.001 12.017 24.001c6.624 0 11.99-5.367 11.99-12.014C24.007 5.367 18.641.001 12.017.001z"/>
                </svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">Snapchat Ads</h3>
                <p class="text-sm text-gray-600">Connect Snapchat for Business campaigns</p>
              </div>
            </div>
          </div>
          
          <button
            @click="connectPlatform('snapchat')"
            :disabled="connecting === 'snapchat'"
            class="w-full bg-yellow-500 text-white py-2 px-4 rounded-lg hover:bg-yellow-600 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <svg v-if="connecting === 'snapchat'" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <LinkIcon class="w-4 h-4" />
            {{ connecting === 'snapchat' ? 'Connecting...' : 'Connect with Snapchat' }}
          </button>
        </div>

        <!-- TikTok Ads -->
        <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 bg-black rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                </svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">TikTok Ads</h3>
                <p class="text-sm text-gray-600">Connect TikTok for Business campaigns</p>
              </div>
            </div>
          </div>
          
          <button
            @click="connectPlatform('tiktok')"
            :disabled="connecting === 'tiktok'"
            class="w-full bg-black text-white py-2 px-4 rounded-lg hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <svg v-if="connecting === 'tiktok'" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <LinkIcon class="w-4 h-4" />
            {{ connecting === 'tiktok' ? 'Connecting...' : 'Connect with TikTok' }}
          </button>
        </div>

        <!-- Twitter Ads -->
        <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow opacity-60">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                </svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">Twitter Ads</h3>
                <p class="text-sm text-gray-600">Connect Twitter advertising campaigns</p>
              </div>
            </div>
          </div>
          
          <button
            disabled
            class="w-full bg-gray-300 text-gray-500 py-2 px-4 rounded-lg cursor-not-allowed flex items-center justify-center gap-2"
          >
            <ClockIcon class="w-4 h-4" />
            Coming Soon
          </button>
        </div>

        <!-- LinkedIn Ads -->
        <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow opacity-60">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-700" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                </svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">LinkedIn Ads</h3>
                <p class="text-sm text-gray-600">Connect LinkedIn advertising campaigns</p>
              </div>
            </div>
          </div>
          
          <button
            disabled
            class="w-full bg-gray-300 text-gray-500 py-2 px-4 rounded-lg cursor-not-allowed flex items-center justify-center gap-2"
          >
            <ClockIcon class="w-4 h-4" />
            Coming Soon
          </button>
        </div>

        <!-- Pinterest Ads -->
        <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow opacity-60">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.097.118.112.221.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.748-1.378 0 0-.599 2.282-.744 2.840-.282 1.084-1.064 2.456-1.549 3.235C9.584 23.815 10.77 24.001 12.017 24.001c6.624 0 11.99-5.367 11.99-12.014C24.007 5.367 18.641.001 12.017.001z"/>
                </svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">Pinterest Ads</h3>
                <p class="text-sm text-gray-600">Connect Pinterest advertising campaigns</p>
              </div>
            </div>
          </div>
          
          <button
            disabled
            class="w-full bg-gray-300 text-gray-500 py-2 px-4 rounded-lg cursor-not-allowed flex items-center justify-center gap-2"
          >
            <ClockIcon class="w-4 h-4" />
            Coming Soon
          </button>
        </div>
      </div>
    </div>

    <!-- Connected Integrations -->
    <div v-if="integrations.length > 0" class="bg-white rounded-lg shadow">
      <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">Connected Integrations</h2>
        <p class="text-sm text-gray-600">Manage your connected advertising platforms</p>
      </div>
      
      <div class="divide-y divide-gray-200">
        <div
          v-for="integration in integrations"
          :key="integration.id"
          class="p-6 flex items-center justify-between"
        >
          <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
              <component :is="getPlatformIcon(integration.platform)" class="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <h3 class="font-medium text-gray-900">{{ integration.account_name }}</h3>
              <p class="text-sm text-gray-600">{{ integration.platform }} • Connected {{ formatDate(integration.created_at) }}</p>
            </div>
          </div>
          
          <div class="flex items-center gap-2">
            <span
              :class="[
                'px-2 py-1 text-xs font-medium rounded-full',
                integration.status === 'active' 
                  ? 'bg-green-100 text-green-800' 
                  : 'bg-red-100 text-red-800'
              ]"
            >
              {{ integration.status }}
            </span>
            
            <button
              @click="testConnection(integration)"
              class="text-gray-400 hover:text-gray-600"
            >
              <ArrowPathIcon class="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ArrowPathIcon, ClockIcon, LinkIcon, PlusIcon } from '@heroicons/vue/24/outline'
import { onMounted, ref } from 'vue'
import FacebookIntegrationCard from '../components/FacebookIntegrationCard.vue'

const integrations = ref([])
const showAddModal = ref(false)
const connecting = ref<string | null>(null)

const connectPlatform = async (platform: string) => {
  connecting.value = platform
  
  try {
    const response = await fetch(`/api/integrations/${platform}/auth-url`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Accept': 'application/json',
      },
    })
    
    const data = await response.json()
    
    if (data.success && data.auth_url) {
      // Open OAuth popup
      const popup = window.open(
        data.auth_url,
        `${platform}_oauth`,
        'width=600,height=700,scrollbars=yes,resizable=yes'
      )
      
      // Listen for popup completion
      const checkClosed = setInterval(() => {
        if (popup?.closed) {
          clearInterval(checkClosed)
          connecting.value = null
          // Refresh integrations list
          loadIntegrations()
        }
      }, 1000)
    } else {
      throw new Error(data.message || `Failed to get ${platform} authorization URL`)
    }
  } catch (error) {
    console.error(`${platform} connection error:`, error)
    alert(`Failed to connect to ${platform}: ${error.message}`)
  } finally {
    connecting.value = null
  }
}

const loadIntegrations = async () => {
  try {
    const response = await fetch('/api/integrations', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Accept': 'application/json',
      },
    })
    
    const data = await response.json()
    integrations.value = data.data || []
  } catch (error) {
    console.error('Failed to load integrations:', error)
  }
}

const testConnection = async (integration: any) => {
  try {
    const response = await fetch(`/api/integrations/${integration.platform}/${integration.id}/test`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Accept': 'application/json',
      },
    })
    
    const data = await response.json()
    
    if (data.success) {
      alert('Connection test successful!')
    } else {
      alert('Connection test failed: ' + data.message)
    }
  } catch (error) {
    console.error('Connection test error:', error)
    alert('Connection test failed')
  }
}

const getPlatformIcon = (platform: string) => {
  // Return appropriate icon component based on platform
  return 'div' // Placeholder
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString()
}

onMounted(() => {
  loadIntegrations()
})
</script>
