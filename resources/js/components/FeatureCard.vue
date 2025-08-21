<template>
  <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 cursor-pointer" @click="handleClick">
    <div class="p-6">
      <!-- Header -->
      <div class="flex items-start justify-between mb-4">
        <div class="flex items-center">
          <div class="text-2xl mr-3">{{ feature.icon }}</div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ feature.title }}</h3>
            <span :class="categoryClass" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
              {{ categoryLabel }}
            </span>
          </div>
        </div>
        <div class="flex items-center">
          <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
          </svg>
        </div>
      </div>

      <!-- Description -->
      <p class="text-gray-600 text-sm mb-4 leading-relaxed">{{ feature.description }}</p>

      <!-- Stats -->
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <div class="flex items-center text-sm text-gray-500">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            {{ feature.endpoints }} APIs
          </div>
          <div class="flex items-center text-sm text-gray-500">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Complete
          </div>
        </div>
        
        <!-- Action Button -->
        <button class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white transition-colors duration-200" :class="feature.color + ' hover:opacity-90'">
          View Feature
          <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </button>
      </div>

      <!-- Progress Bar -->
      <div class="mt-4">
        <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
          <span>Implementation Progress</span>
          <span>100%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div class="h-2 rounded-full transition-all duration-1000" :class="feature.color" style="width: 100%"></div>
        </div>
      </div>
    </div>

    <!-- Feature Details (Expandable) -->
    <div v-if="showDetails" class="border-t border-gray-100 px-6 py-4 bg-gray-50">
      <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
          <span class="font-medium text-gray-700">Status:</span>
          <span class="ml-2 text-green-600">✓ Complete</span>
        </div>
        <div>
          <span class="font-medium text-gray-700">Category:</span>
          <span class="ml-2 text-gray-600">{{ categoryLabel }}</span>
        </div>
        <div>
          <span class="font-medium text-gray-700">API Endpoints:</span>
          <span class="ml-2 text-gray-600">{{ feature.endpoints }}</span>
        </div>
        <div>
          <span class="font-medium text-gray-700">Frontend:</span>
          <span class="ml-2 text-green-600">✓ Ready</span>
        </div>
      </div>
      
      <!-- Quick Actions -->
      <div class="mt-4 flex space-x-2">
        <button class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
          <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
          </svg>
          Open Feature
        </button>
        <button class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
          <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          View Docs
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

interface Feature {
  id: number
  title: string
  description: string
  icon: string
  status: string
  route: string
  category: string
  endpoints: number
  color: string
}

const props = defineProps<{
  feature: Feature
}>()

const emit = defineEmits<{
  navigate: [route: string]
}>()

const showDetails = ref(false)

const categoryClass = computed(() => {
  switch (props.feature.category) {
    case 'core':
      return 'bg-blue-100 text-blue-800'
    case 'ai':
      return 'bg-purple-100 text-purple-800'
    case 'advanced':
      return 'bg-green-100 text-green-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
})

const categoryLabel = computed(() => {
  switch (props.feature.category) {
    case 'core':
      return 'Core Feature'
    case 'ai':
      return 'AI-Powered'
    case 'advanced':
      return 'Advanced'
    default:
      return 'Feature'
  }
})

const handleClick = () => {
  emit('navigate', props.feature.route)
}

const toggleDetails = () => {
  showDetails.value = !showDetails.value
}
</script>

<style scoped>
.transition-shadow {
  transition: box-shadow 0.2s ease;
}

.transition-colors {
  transition: background-color 0.2s ease, color 0.2s ease;
}

.transition-all {
  transition: all 0.3s ease;
}
</style>
