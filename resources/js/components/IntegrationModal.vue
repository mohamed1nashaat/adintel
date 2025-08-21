<template>
  <TransitionRoot as="template" :show="show">
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
              <div>
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100">
                  <component v-if="platform" :is="platform.icon" :class="['h-6 w-6', platform.color]" />
                </div>
                <div class="mt-3 text-center sm:mt-5">
                  <DialogTitle as="h3" class="text-base font-semibold leading-6 text-gray-900">
                    Connect {{ platform?.name }}
                  </DialogTitle>
                  <div class="mt-2">
                    <p class="text-sm text-gray-500">
                      Enter your {{ platform?.name }} credentials to connect your advertising account.
                    </p>
                  </div>
                </div>
              </div>

              <form @submit.prevent="handleSubmit" class="mt-6 space-y-4">
                <!-- Facebook Ads Fields -->
                <div v-if="platform?.id === 'facebook'" class="space-y-4">
                  <div>
                    <label for="app_id" class="block text-sm font-medium text-gray-700">App ID</label>
                    <input
                      id="app_id"
                      v-model="form.app_id"
                      type="text"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                      placeholder="Your Facebook App ID"
                    />
                  </div>
                  <div>
                    <label for="app_secret" class="block text-sm font-medium text-gray-700">App Secret</label>
                    <input
                      id="app_secret"
                      v-model="form.app_secret"
                      type="password"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                      placeholder="Your Facebook App Secret"
                    />
                  </div>
                  <div>
                    <label for="access_token" class="block text-sm font-medium text-gray-700">Access Token</label>
                    <input
                      id="access_token"
                      v-model="form.access_token"
                      type="password"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                      placeholder="Your Facebook Access Token"
                    />
                  </div>
                </div>

                <!-- Google Ads Fields -->
                <div v-else-if="platform?.id === 'google'" class="space-y-4">
                  <div>
                    <label for="client_id" class="block text-sm font-medium text-gray-700">Client ID</label>
                    <input
                      id="client_id"
                      v-model="form.client_id"
                      type="text"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                      placeholder="Your Google Ads Client ID"
                    />
                  </div>
                  <div>
                    <label for="client_secret" class="block text-sm font-medium text-gray-700">Client Secret</label>
                    <input
                      id="client_secret"
                      v-model="form.client_secret"
                      type="password"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                      placeholder="Your Google Ads Client Secret"
                    />
                  </div>
                  <div>
                    <label for="developer_token" class="block text-sm font-medium text-gray-700">Developer Token</label>
                    <input
                      id="developer_token"
                      v-model="form.developer_token"
                      type="password"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                      placeholder="Your Google Ads Developer Token"
                    />
                  </div>
                  <div>
                    <label for="refresh_token" class="block text-sm font-medium text-gray-700">Refresh Token</label>
                    <input
                      id="refresh_token"
                      v-model="form.refresh_token"
                      type="password"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                      placeholder="Your Google Ads Refresh Token"
                    />
                  </div>
                </div>

                <!-- TikTok Ads Fields -->
                <div v-else-if="platform?.id === 'tiktok'" class="space-y-4">
                  <div>
                    <label for="app_id" class="block text-sm font-medium text-gray-700">App ID</label>
                    <input
                      id="app_id"
                      v-model="form.app_id"
                      type="text"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                      placeholder="Your TikTok App ID"
                    />
                  </div>
                  <div>
                    <label for="secret" class="block text-sm font-medium text-gray-700">Secret</label>
                    <input
                      id="secret"
                      v-model="form.secret"
                      type="password"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                      placeholder="Your TikTok Secret"
                    />
                  </div>
                  <div>
                    <label for="access_token" class="block text-sm font-medium text-gray-700">Access Token</label>
                    <input
                      id="access_token"
                      v-model="form.access_token"
                      type="password"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                      placeholder="Your TikTok Access Token"
                    />
                  </div>
                </div>

                <!-- Error Message -->
                <div v-if="error" class="rounded-md bg-red-50 p-4">
                  <div class="flex">
                    <div class="flex-shrink-0">
                      <ExclamationTriangleIcon class="h-5 w-5 text-red-400" aria-hidden="true" />
                    </div>
                    <div class="ml-3">
                      <h3 class="text-sm font-medium text-red-800">
                        {{ error }}
                      </h3>
                    </div>
                  </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                  <button
                    type="submit"
                    :disabled="loading"
                    class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:col-start-2 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <span v-if="loading" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
                    {{ loading ? 'Connecting...' : 'Connect' }}
                  </button>
                  <button
                    type="button"
                    @click="$emit('close')"
                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0"
                  >
                    Cancel
                  </button>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'

interface Platform {
  id: string
  name: string
  description: string
  icon: any
  color: string
}

interface Props {
  show: boolean
  platform: Platform | null
}

const props = defineProps<Props>()
const emit = defineEmits(['close', 'success'])

const loading = ref(false)
const error = ref('')

const form = reactive({
  // Facebook fields
  app_id: '',
  app_secret: '',
  access_token: '',
  
  // Google fields
  client_id: '',
  client_secret: '',
  developer_token: '',
  refresh_token: '',
  
  // TikTok fields
  secret: ''
})

const resetForm = () => {
  Object.keys(form).forEach(key => {
    form[key as keyof typeof form] = ''
  })
  error.value = ''
}

const handleSubmit = async () => {
  if (!props.platform) return

  loading.value = true
  error.value = ''

  try {
    const config: any = {}

    // Build config based on platform
    if (props.platform.id === 'facebook') {
      config.app_id = form.app_id
      config.app_secret = form.app_secret
      config.access_token = form.access_token
    } else if (props.platform.id === 'google') {
      config.client_id = form.client_id
      config.client_secret = form.client_secret
      config.developer_token = form.developer_token
      config.refresh_token = form.refresh_token
    } else if (props.platform.id === 'tiktok') {
      config.app_id = form.app_id
      config.secret = form.secret
      config.access_token = form.access_token
    }

    const response = await axios.post('/api/integrations', {
      platform: props.platform.id,
      app_config: config
    })

    emit('success', response.data)
    resetForm()
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to connect integration. Please check your credentials.'
  } finally {
    loading.value = false
  }
}

// Reset form when modal opens/closes or platform changes
watch([() => props.show, () => props.platform], () => {
  if (props.show) {
    resetForm()
  }
})
</script>
