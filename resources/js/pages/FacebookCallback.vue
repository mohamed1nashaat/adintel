<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100">
          <svg v-if="!error" class="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg v-else class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
        </div>
        
        <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
          {{ error ? 'Connection Failed' : 'Connecting Facebook...' }}
        </h2>
        
        <p class="mt-2 text-sm text-gray-600">
          {{ error ? errorMessage : 'Please wait while we complete your Facebook integration.' }}
        </p>

        <div v-if="success" class="mt-4 p-4 bg-green-50 rounded-md">
          <div class="flex">
            <div class="flex-shrink-0">
              <CheckCircleIcon class="h-5 w-5 text-green-400" />
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-green-800">
                Facebook Connected Successfully!
              </h3>
              <div class="mt-2 text-sm text-green-700">
                <p>Found {{ accountsFound }} ad accounts. You can now close this window.</p>
              </div>
            </div>
          </div>
        </div>

        <div v-if="error" class="mt-4 p-4 bg-red-50 rounded-md">
          <div class="flex">
            <div class="flex-shrink-0">
              <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">
                Connection Error
              </h3>
              <div class="mt-2 text-sm text-red-700">
                <p>{{ errorMessage }}</p>
              </div>
              <div class="mt-4">
                <button
                  @click="retry"
                  class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                >
                  Try Again
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="!error && !success" class="mt-6">
          <div class="flex items-center justify-center space-x-2 text-sm text-gray-500">
            <div class="animate-pulse flex space-x-1">
              <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
              <div class="w-2 h-2 bg-blue-600 rounded-full animation-delay-200"></div>
              <div class="w-2 h-2 bg-blue-600 rounded-full animation-delay-400"></div>
            </div>
            <span>Processing your Facebook connection...</span>
          </div>
        </div>

        <div class="mt-8">
          <button
            @click="closeWindow"
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Close Window
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { CheckCircleIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const success = ref(false)
const error = ref(false)
const errorMessage = ref('')
const accountsFound = ref(0)

const handleCallback = async () => {
  const code = route.query.code as string
  const state = route.query.state as string
  const errorParam = route.query.error as string

  // Handle OAuth errors
  if (errorParam) {
    error.value = true
    errorMessage.value = getErrorMessage(errorParam, route.query.error_description as string)
    notifyParent('FACEBOOK_OAUTH_ERROR', { error: errorMessage.value })
    return
  }

  // Handle missing parameters
  if (!code || !state) {
    error.value = true
    errorMessage.value = 'Missing required parameters from Facebook'
    notifyParent('FACEBOOK_OAUTH_ERROR', { error: errorMessage.value })
    return
  }

  try {
    // Send callback data to backend
    const response = await axios.post('/api/integrations/facebook/callback', {
      code,
      state
    })

    if (response.data.success) {
      success.value = true
      accountsFound.value = response.data.accounts_found || 0
      
      // Notify parent window of success
      notifyParent('FACEBOOK_OAUTH_SUCCESS', {
        integration: response.data.integration,
        userInfo: response.data.user_info,
        accountsFound: response.data.accounts_found
      })

      // Auto-close after 3 seconds
      setTimeout(() => {
        closeWindow()
      }, 3000)
    } else {
      throw new Error(response.data.message || 'Unknown error occurred')
    }
  } catch (err: any) {
    error.value = true
    errorMessage.value = err.response?.data?.message || err.message || 'Failed to complete Facebook integration'
    
    notifyParent('FACEBOOK_OAUTH_ERROR', { 
      error: errorMessage.value,
      details: err.response?.data 
    })
  }
}

const getErrorMessage = (error: string, description?: string): string => {
  const errorMessages: Record<string, string> = {
    'access_denied': 'You denied access to Facebook. Please try again and grant the required permissions.',
    'server_error': 'Facebook server error occurred. Please try again later.',
    'temporarily_unavailable': 'Facebook service is temporarily unavailable. Please try again later.',
    'invalid_request': 'Invalid request to Facebook. Please contact support if this persists.',
    'unauthorized_client': 'Application is not authorized. Please contact support.',
    'unsupported_response_type': 'Unsupported response type. Please contact support.',
    'invalid_scope': 'Invalid permissions requested. Please contact support.',
    'invalid_client': 'Invalid Facebook application configuration. Please contact support.'
  }

  return errorMessages[error] || description || `Facebook OAuth error: ${error}`
}

const notifyParent = (type: string, data: any) => {
  if (window.opener) {
    window.opener.postMessage({ type, ...data }, window.location.origin)
  }
}

const retry = () => {
  // Reset state and try again
  success.value = false
  error.value = false
  errorMessage.value = ''
  
  // Redirect back to integrations page
  window.location.href = '/integrations'
}

const closeWindow = () => {
  if (window.opener) {
    window.close()
  } else {
    // If not opened as popup, redirect to integrations
    window.location.href = '/integrations'
  }
}

onMounted(() => {
  handleCallback()
})
</script>

<style scoped>
.animation-delay-200 {
  animation-delay: 0.2s;
}

.animation-delay-400 {
  animation-delay: 0.4s;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse > div {
  animation: pulse 1.5s ease-in-out infinite;
}
</style>
