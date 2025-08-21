<template>
  <div class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm hover:border-gray-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
    <div class="flex items-center space-x-3">
      <div class="flex-shrink-0">
        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
          <svg class="h-6 w-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
          </svg>
        </div>
      </div>
      <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-900">Facebook Ads</p>
            <p class="text-sm text-gray-500 truncate">Connect Facebook and Instagram advertising</p>
          </div>
          <div class="flex items-center space-x-2">
            <!-- Connection Status -->
            <span
              v-if="integration && integration.status === 'active'"
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
            >
              <div class="w-1.5 h-1.5 bg-green-400 rounded-full mr-1.5"></div>
              Connected
            </span>
            <span
              v-else-if="integration && integration.status === 'error'"
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
            >
              <div class="w-1.5 h-1.5 bg-red-400 rounded-full mr-1.5"></div>
              Error
            </span>

            <!-- Action Buttons -->
            <div class="flex space-x-2">
              <!-- One-Click Connect Button -->
              <button
                v-if="!integration || integration.status !== 'active'"
                @click="handleOneClickConnect"
                :disabled="connecting"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="connecting" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
                <svg v-else class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
                {{ connecting ? 'Connecting...' : 'Connect with Facebook' }}
              </button>

              <!-- Connected Actions -->
              <div v-else class="flex space-x-2">
                <button
                  @click="testConnection"
                  :disabled="testing"
                  class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                >
                  <span v-if="testing" class="animate-spin rounded-full h-4 w-4 border-b-2 border-indigo-600 mr-2"></span>
                  <svg v-else class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Test
                </button>

                <button
                  @click="refreshToken"
                  :disabled="refreshing"
                  class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                >
                  <span v-if="refreshing" class="animate-spin rounded-full h-4 w-4 border-b-2 border-indigo-600 mr-2"></span>
                  <svg v-else class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                  Refresh
                </button>

                <Menu as="div" class="relative inline-block text-left">
                  <MenuButton class="inline-flex items-center p-2 border border-transparent rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <EllipsisVerticalIcon class="h-5 w-5" />
                  </MenuButton>
                  <transition
                    enter-active-class="transition duration-100 ease-out"
                    enter-from-class="transform scale-95 opacity-0"
                    enter-to-class="transform scale-100 opacity-100"
                    leave-active-class="transition duration-75 ease-in"
                    leave-from-class="transform scale-100 opacity-100"
                    leave-to-class="transform scale-95 opacity-0"
                  >
                    <MenuItems class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                      <div class="py-1">
                        <MenuItem v-slot="{ active }">
                          <button
                            @click="viewInsights"
                            :class="[
                              active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                              'group flex w-full items-center px-4 py-2 text-sm'
                            ]"
                          >
                            <ChartBarIcon class="mr-3 h-4 w-4 text-gray-400 group-hover:text-gray-500" />
                            View Insights
                          </button>
                        </MenuItem>
                        <MenuItem v-slot="{ active }">
                          <button
                            @click="reconnect"
                            :class="[
                              active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                              'group flex w-full items-center px-4 py-2 text-sm'
                            ]"
                          >
                            <ArrowPathIcon class="mr-3 h-4 w-4 text-gray-400 group-hover:text-gray-500" />
                            Reconnect
                          </button>
                        </MenuItem>
                        <MenuItem v-slot="{ active }">
                          <button
                            @click="disconnect"
                            :class="[
                              active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                              'group flex w-full items-center px-4 py-2 text-sm'
                            ]"
                          >
                            <XMarkIcon class="mr-3 h-4 w-4 text-gray-400 group-hover:text-red-500" />
                            Disconnect
                          </button>
                        </MenuItem>
                      </div>
                    </MenuItems>
                  </transition>
                </Menu>
              </div>
            </div>
          </div>
        </div>

        <!-- Connection Details -->
        <div v-if="integration && integration.status === 'active'" class="mt-3 text-xs text-gray-500">
          <div class="flex items-center space-x-4">
            <span v-if="integration.app_config?.user_name">
              👤 {{ integration.app_config.user_name }}
            </span>
            <span v-if="integration.accounts_count">
              🏢 {{ integration.accounts_count }} accounts
            </span>
            <span v-if="integration.app_config?.connected_at">
              📅 Connected {{ formatDate(integration.app_config.connected_at) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Success/Error Messages -->
    <div v-if="message" class="mt-4">
      <div
        :class="[
          'rounded-md p-4',
          messageType === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'
        ]"
      >
        <div class="flex">
          <div class="flex-shrink-0">
            <CheckCircleIcon v-if="messageType === 'success'" class="h-5 w-5 text-green-400" />
            <ExclamationTriangleIcon v-else class="h-5 w-5 text-red-400" />
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium">{{ message }}</p>
          </div>
          <div class="ml-auto pl-3">
            <button @click="clearMessage" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import {
    ArrowPathIcon,
    ChartBarIcon,
    CheckCircleIcon,
    EllipsisVerticalIcon,
    ExclamationTriangleIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'
import axios from 'axios'
import { ref } from 'vue'

interface Integration {
  id: string
  platform: string
  status: 'active' | 'inactive' | 'error'
  created_at: string
  accounts_count?: number
  app_config?: {
    user_name?: string
    user_email?: string
    connected_at?: string
    permissions?: string[]
  }
}

interface Props {
  integration?: Integration | null
}

const props = defineProps<Props>()
const emit = defineEmits(['updated', 'connected', 'disconnected'])

const connecting = ref(false)
const testing = ref(false)
const refreshing = ref(false)
const message = ref('')
const messageType = ref<'success' | 'error'>('success')

const handleOneClickConnect = async () => {
  connecting.value = true
  clearMessage()

  try {
    // Get Facebook OAuth URL
    const response = await axios.get('/api/integrations/facebook/auth-url', {
      params: {
        redirect_uri: `${window.location.origin}/integrations/facebook/callback`
      }
    })

    // Open Facebook OAuth in popup
    const popup = window.open(
      response.data.auth_url,
      'facebook-oauth',
      'width=600,height=700,scrollbars=yes,resizable=yes'
    )

    // Listen for popup messages
    const messageListener = (event: MessageEvent) => {
      if (event.origin !== window.location.origin) return

      if (event.data.type === 'FACEBOOK_OAUTH_SUCCESS') {
        popup?.close()
        window.removeEventListener('message', messageListener)
        
        showMessage('Facebook integration connected successfully!', 'success')
        emit('connected', event.data.integration)
        connecting.value = false
      } else if (event.data.type === 'FACEBOOK_OAUTH_ERROR') {
        popup?.close()
        window.removeEventListener('message', messageListener)
        
        showMessage(event.data.error || 'Failed to connect Facebook integration', 'error')
        connecting.value = false
      }
    }

    window.addEventListener('message', messageListener)

    // Handle popup closed manually
    const checkClosed = setInterval(() => {
      if (popup?.closed) {
        clearInterval(checkClosed)
        window.removeEventListener('message', messageListener)
        if (connecting.value) {
          connecting.value = false
          showMessage('Facebook connection was cancelled', 'error')
        }
      }
    }, 1000)

  } catch (error: any) {
    connecting.value = false
    showMessage(error.response?.data?.message || 'Failed to initiate Facebook connection', 'error')
  }
}

const testConnection = async () => {
  if (!props.integration) return

  testing.value = true
  clearMessage()

  try {
    const response = await axios.post(`/api/integrations/facebook/${props.integration.id}/test`)
    showMessage(response.data.message, 'success')
    emit('updated')
  } catch (error: any) {
    showMessage(error.response?.data?.message || 'Connection test failed', 'error')
  } finally {
    testing.value = false
  }
}

const refreshToken = async () => {
  if (!props.integration) return

  refreshing.value = true
  clearMessage()

  try {
    const response = await axios.post(`/api/integrations/facebook/${props.integration.id}/refresh-token`)
    showMessage(response.data.message, 'success')
    emit('updated')
  } catch (error: any) {
    showMessage(error.response?.data?.message || 'Token refresh failed', 'error')
  } finally {
    refreshing.value = false
  }
}

const viewInsights = async () => {
  if (!props.integration) return

  try {
    const response = await axios.get(`/api/integrations/facebook/${props.integration.id}/insights`)
    // Handle insights display - could open a modal or navigate to insights page
    console.log('Facebook Insights:', response.data.insights)
    showMessage('Insights loaded successfully', 'success')
  } catch (error: any) {
    showMessage(error.response?.data?.message || 'Failed to load insights', 'error')
  }
}

const reconnect = () => {
  handleOneClickConnect()
}

const disconnect = async () => {
  if (!props.integration) return

  if (confirm('Are you sure you want to disconnect Facebook? This will remove all associated data.')) {
    try {
      await axios.delete(`/api/integrations/${props.integration.id}`)
      showMessage('Facebook integration disconnected successfully', 'success')
      emit('disconnected')
    } catch (error: any) {
      showMessage(error.response?.data?.message || 'Failed to disconnect integration', 'error')
    }
  }
}

const showMessage = (text: string, type: 'success' | 'error') => {
  message.value = text
  messageType.value = type
  
  // Auto-clear success messages after 5 seconds
  if (type === 'success') {
    setTimeout(() => {
      clearMessage()
    }, 5000)
  }
}

const clearMessage = () => {
  message.value = ''
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}
</script>
