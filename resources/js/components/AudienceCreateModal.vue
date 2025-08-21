<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="$emit('close')">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 shadow-lg rounded-md bg-white" @click.stop>
      <div class="flex items-center justify-between pb-4 border-b">
        <h3 class="text-lg font-semibold text-gray-900">
          {{ audience ? 'Edit Custom Audience' : 'Create Custom Audience' }}
        </h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="mt-6">
        <!-- Basic Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Audience Name</label>
            <input
              id="name"
              v-model="formData.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="e.g., High-Value Prospects"
            >
          </div>
          <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <input
              id="description"
              v-model="formData.description"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Brief description of this audience"
            >
          </div>
        </div>

        <!-- Criteria Section -->
        <div class="mb-6">
          <h4 class="text-md font-medium text-gray-900 mb-4">Audience Criteria</h4>
          
          <!-- Lead Value Filter -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Lead Value</label>
              <input
                v-model="formData.criteria.minValue"
                type="number"
                min="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="0"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Maximum Lead Value</label>
              <input
                v-model="formData.criteria.maxValue"
                type="number"
                min="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="No limit"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Lead Status</label>
              <select
                v-model="formData.criteria.status"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">All Status</option>
                <option value="new">New</option>
                <option value="qualified">Qualified</option>
                <option value="contacted">Contacted</option>
                <option value="converted">Converted</option>
              </select>
            </div>
          </div>

          <!-- Date Range Filter -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
              <input
                v-model="formData.criteria.fromDate"
                type="date"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
              <input
                v-model="formData.criteria.toDate"
                type="date"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
            </div>
          </div>

          <!-- Source Filter -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Lead Sources</label>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
              <label v-for="source in availableSources" :key="source" class="flex items-center">
                <input
                  v-model="formData.criteria.sources"
                  :value="source"
                  type="checkbox"
                  class="mr-2 text-blue-600"
                >
                <span class="text-sm text-gray-700 capitalize">{{ source }}</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Preview Section -->
        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
          <h4 class="text-sm font-medium text-blue-900 mb-2">Audience Preview</h4>
          <div class="text-sm text-blue-700">
            <p>Estimated audience size: <span class="font-medium">{{ estimatedSize }} leads</span></p>
            <p class="mt-1">Based on current criteria and available data</p>
          </div>
        </div>

        <!-- Platform Sync Options -->
        <div class="mb-6">
          <h4 class="text-md font-medium text-gray-900 mb-4">Platform Sync</h4>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50">
              <input
                v-model="formData.syncPlatforms"
                value="facebook"
                type="checkbox"
                class="mr-3 text-blue-600"
              >
              <div class="flex items-center">
                <div class="w-6 h-6 bg-blue-600 rounded mr-2 flex items-center justify-center">
                  <span class="text-white text-xs font-bold">f</span>
                </div>
                <span class="text-sm font-medium">Facebook</span>
              </div>
            </label>
            <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50">
              <input
                v-model="formData.syncPlatforms"
                value="google"
                type="checkbox"
                class="mr-3 text-blue-600"
              >
              <div class="flex items-center">
                <div class="w-6 h-6 bg-red-600 rounded mr-2 flex items-center justify-center">
                  <span class="text-white text-xs font-bold">G</span>
                </div>
                <span class="text-sm font-medium">Google</span>
              </div>
            </label>
            <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50">
              <input
                v-model="formData.syncPlatforms"
                value="tiktok"
                type="checkbox"
                class="mr-3 text-blue-600"
              >
              <div class="flex items-center">
                <div class="w-6 h-6 bg-black rounded mr-2 flex items-center justify-center">
                  <span class="text-white text-xs font-bold">T</span>
                </div>
                <span class="text-sm font-medium">TikTok</span>
              </div>
            </label>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-3 pt-4 border-t">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            {{ audience ? 'Update Audience' : 'Create Audience' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{
  audience?: any
}>()

const emit = defineEmits<{
  close: []
  save: [data: any]
}>()

const formData = ref({
  name: '',
  description: '',
  criteria: {
    minValue: null,
    maxValue: null,
    status: '',
    fromDate: '',
    toDate: '',
    sources: [] as string[]
  },
  syncPlatforms: [] as string[]
})

const availableSources = ['facebook', 'google', 'website', 'upload']

const estimatedSize = computed(() => {
  // Mock calculation based on criteria
  let base = 1000
  if (formData.value.criteria.minValue) base *= 0.6
  if (formData.value.criteria.status) base *= 0.4
  if (formData.value.criteria.sources.length > 0) base *= (formData.value.criteria.sources.length / availableSources.length)
  return Math.floor(base)
})

const handleSubmit = () => {
  const data = {
    ...formData.value,
    size: estimatedSize.value
  }

  if (props.audience) {
    data.id = props.audience.id
  }

  emit('save', data)
}

onMounted(() => {
  if (props.audience) {
    formData.value = {
      name: props.audience.name || '',
      description: props.audience.description || '',
      criteria: props.audience.criteria || {
        minValue: null,
        maxValue: null,
        status: '',
        fromDate: '',
        toDate: '',
        sources: []
      },
      syncPlatforms: props.audience.syncPlatforms || []
    }
  }
})
</script>
