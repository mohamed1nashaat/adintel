<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">AI Feature Suggestion Engine</h1>
        <p class="mt-2 text-gray-600">Get intelligent recommendations to optimize your advertising performance</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Total Suggestions</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.total_suggestions || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Pending</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.pending_suggestions || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Implemented</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.implemented_suggestions || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Avg Impact Score</p>
              <p class="text-2xl font-semibold text-gray-900">{{ Math.round(stats.avg_impact_score || 0) }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Generate New Suggestions -->
        <div class="lg:col-span-2">
          <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-medium text-gray-900">Generate AI Suggestions</h2>
              <p class="text-sm text-gray-500">Get personalized recommendations based on your data and performance</p>
            </div>
            
            <div class="p-6">
              <button @click="generateSuggestions" :disabled="generating" class="w-full bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 disabled:opacity-50 flex items-center justify-center">
                <svg v-if="generating" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
                </svg>
                {{ generating ? 'Analyzing Your Data...' : 'Generate AI Suggestions' }}
              </button>
            </div>
          </div>

          <!-- Suggestions List -->
          <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
              <h3 class="text-lg font-medium text-gray-900">Your Suggestions</h3>
              <div class="flex space-x-2">
                <select v-model="filterCategory" class="text-sm border border-gray-300 rounded-md px-3 py-1">
                  <option value="">All Categories</option>
                  <option v-for="(label, value) in categories" :key="value" :value="value">{{ label }}</option>
                </select>
                <select v-model="filterPriority" class="text-sm border border-gray-300 rounded-md px-3 py-1">
                  <option value="">All Priorities</option>
                  <option value="high">High Priority</option>
                  <option value="medium">Medium Priority</option>
                  <option value="low">Low Priority</option>
                </select>
              </div>
            </div>
            
            <div class="divide-y divide-gray-200">
              <div v-if="filteredSuggestions.length === 0" class="p-6 text-center text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                <p class="mt-2">No suggestions available. Click "Generate AI Suggestions" to get started.</p>
              </div>
              
              <div v-for="suggestion in filteredSuggestions" :key="suggestion.id" class="p-6 hover:bg-gray-50">
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <div class="flex items-center space-x-2 mb-2">
                      <h4 class="text-lg font-medium text-gray-900">{{ suggestion.title }}</h4>
                      <span :class="getPriorityClass(suggestion.priority)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                        {{ suggestion.priority.toUpperCase() }}
                      </span>
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ categories[suggestion.category] || suggestion.category }}
                      </span>
                    </div>
                    <p class="text-gray-600 mb-3">{{ suggestion.description }}</p>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                      <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ suggestion.impact_score }}</div>
                        <div class="text-xs text-gray-500">Impact Score</div>
                      </div>
                      <div class="text-center">
                        <div class="text-sm font-medium text-green-600">{{ suggestion.metadata?.estimated_roi || 'N/A' }}</div>
                        <div class="text-xs text-gray-500">Est. ROI</div>
                      </div>
                      <div class="text-center">
                        <div class="text-sm font-medium text-purple-600">{{ suggestion.implementation_effort }}</div>
                        <div class="text-xs text-gray-500">Effort</div>
                      </div>
                      <div class="text-center">
                        <div class="text-sm font-medium text-orange-600">{{ suggestion.metadata?.implementation_timeline || 'N/A' }}</div>
                        <div class="text-xs text-gray-500">Timeline</div>
                      </div>
                    </div>
                    
                    <div v-if="suggestion.metadata?.benefits" class="mb-4">
                      <h5 class="text-sm font-medium text-gray-700 mb-2">Benefits:</h5>
                      <div class="flex flex-wrap gap-2">
                        <span v-for="benefit in suggestion.metadata.benefits" :key="benefit" class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-800">
                          {{ benefit }}
                        </span>
                      </div>
                    </div>
                  </div>
                  
                  <div class="flex flex-col space-y-2 ml-4">
                    <button v-if="suggestion.status === 'pending'" @click="implementSuggestion(suggestion)" class="bg-green-600 text-white px-3 py-1 rounded-md text-sm hover:bg-green-700">
                      Implement
                    </button>
                    <button v-if="suggestion.status === 'pending'" @click="dismissSuggestion(suggestion)" class="bg-gray-600 text-white px-3 py-1 rounded-md text-sm hover:bg-gray-700">
                      Dismiss
                    </button>
                    <span v-if="suggestion.status === 'implemented'" class="inline-flex items-center px-3 py-1 rounded-md text-sm font-medium bg-green-100 text-green-800">
                      ✓ Implemented
                    </span>
                    <span v-if="suggestion.status === 'dismissed'" class="inline-flex items-center px-3 py-1 rounded-md text-sm font-medium bg-gray-100 text-gray-800">
                      Dismissed
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Categories -->
          <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">Categories</h3>
            </div>
            <div class="p-6">
              <div class="space-y-2">
                <div v-for="(count, category) in stats.by_category" :key="category" class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">{{ categories[category] || category }}</span>
                  <span class="text-sm font-medium text-gray-900">{{ count }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Priority Distribution -->
          <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">Priority Distribution</h3>
            </div>
            <div class="p-6">
              <div class="space-y-2">
                <div v-for="(count, priority) in stats.by_priority" :key="priority" class="flex justify-between items-center">
                  <span :class="getPriorityClass(priority)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ priority.toUpperCase() }}
                  </span>
                  <span class="text-sm font-medium text-gray-900">{{ count }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Activity -->
          <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">Recent Suggestions</h3>
            </div>
            <div class="p-6">
              <div class="space-y-3">
                <div v-for="suggestion in stats.recent_suggestions" :key="suggestion.id" class="text-sm">
                  <div class="font-medium text-gray-900 truncate">{{ suggestion.title }}</div>
                  <div class="text-gray-500">{{ new Date(suggestion.created_at).toLocaleDateString() }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'

// Reactive data
const generating = ref(false)
const suggestions = ref([])
const stats = ref({})
const categories = ref({})
const filterCategory = ref('')
const filterPriority = ref('')

// Computed
const filteredSuggestions = computed(() => {
  return suggestions.value.filter(suggestion => {
    const categoryMatch = !filterCategory.value || suggestion.category === filterCategory.value
    const priorityMatch = !filterPriority.value || suggestion.priority === filterPriority.value
    return categoryMatch && priorityMatch
  })
})

// Methods
const loadData = async () => {
  try {
    // Load suggestions
    const suggestionsResponse = await axios.get('/api/feature-suggestions')
    suggestions.value = suggestionsResponse.data.data.data || []

    // Load stats
    const statsResponse = await axios.get('/api/feature-suggestions/stats')
    stats.value = statsResponse.data.data

    // Load categories
    const categoriesResponse = await axios.get('/api/feature-suggestions/categories')
    categories.value = categoriesResponse.data.data
  } catch (error) {
    console.error('Error loading data:', error)
  }
}

const generateSuggestions = async () => {
  generating.value = true
  
  try {
    const response = await axios.post('/api/feature-suggestions/generate')
    
    if (response.data.success) {
      await loadData() // Refresh data
      alert('AI suggestions generated successfully!')
    } else {
      alert('Failed to generate suggestions: ' + response.data.message)
    }
  } catch (error) {
    console.error('Error generating suggestions:', error)
    alert('Failed to generate suggestions. Please try again.')
  } finally {
    generating.value = false
  }
}

const implementSuggestion = async (suggestion) => {
  try {
    const response = await axios.post(`/api/feature-suggestions/${suggestion.id}/implement`)
    
    if (response.data.success) {
      suggestion.status = 'implemented'
      await loadData() // Refresh stats
      alert('Suggestion marked as implemented!')
    } else {
      alert('Failed to implement suggestion: ' + response.data.message)
    }
  } catch (error) {
    console.error('Error implementing suggestion:', error)
    alert('Failed to implement suggestion. Please try again.')
  }
}

const dismissSuggestion = async (suggestion) => {
  const reason = prompt('Why are you dismissing this suggestion? (optional)')
  
  try {
    const response = await axios.post(`/api/feature-suggestions/${suggestion.id}/dismiss`, {
      reason: reason
    })
    
    if (response.data.success) {
      suggestion.status = 'dismissed'
      await loadData() // Refresh stats
      alert('Suggestion dismissed!')
    } else {
      alert('Failed to dismiss suggestion: ' + response.data.message)
    }
  } catch (error) {
    console.error('Error dismissing suggestion:', error)
    alert('Failed to dismiss suggestion. Please try again.')
  }
}

const getPriorityClass = (priority) => {
  const classes = {
    high: 'bg-red-100 text-red-800',
    medium: 'bg-yellow-100 text-yellow-800',
    low: 'bg-green-100 text-green-800'
  }
  return classes[priority] || 'bg-gray-100 text-gray-800'
}

// Load data on component mount
onMounted(() => {
  console.log('Feature Suggestion Engine loaded')
  loadData()
})
</script>
