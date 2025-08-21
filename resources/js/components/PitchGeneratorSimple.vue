<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">AI-Powered Pitch Generator</h1>
        <p class="mt-2 text-gray-600">Generate compelling advertising pitches using artificial intelligence</p>
      </div>

      <!-- Generator Form -->
      <div class="bg-white shadow rounded-lg p-6 mb-8">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Generate New Pitch</h2>
        
        <form @submit.prevent="generatePitch" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Industry Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Industry</label>
              <select v-model="form.industry" required class="w-full border border-gray-300 rounded-md px-3 py-2">
                <option value="">Select Industry</option>
                <option value="ecommerce">E-commerce</option>
                <option value="saas">SaaS</option>
                <option value="finance">Finance</option>
                <option value="healthcare">Healthcare</option>
                <option value="education">Education</option>
                <option value="real_estate">Real Estate</option>
                <option value="automotive">Automotive</option>
                <option value="travel">Travel & Tourism</option>
              </select>
            </div>

            <!-- Ad Type Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Campaign Objective</label>
              <select v-model="form.ad_type" required class="w-full border border-gray-300 rounded-md px-3 py-2">
                <option value="">Select Objective</option>
                <option value="awareness">Brand Awareness</option>
                <option value="conversion">Conversions</option>
                <option value="engagement">Engagement</option>
                <option value="traffic">Website Traffic</option>
                <option value="leads">Lead Generation</option>
                <option value="sales">Sales</option>
              </select>
            </div>

            <!-- Company Name -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
              <input v-model="form.company_name" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2" placeholder="Your company name">
            </div>

            <!-- Budget -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Budget Range</label>
              <select v-model="form.budget" class="w-full border border-gray-300 rounded-md px-3 py-2">
                <option value="">Select Budget</option>
                <option value="$1,000-$5,000">$1,000 - $5,000</option>
                <option value="$5,000-$10,000">$5,000 - $10,000</option>
                <option value="$10,000-$25,000">$10,000 - $25,000</option>
                <option value="$25,000-$50,000">$25,000 - $50,000</option>
                <option value="$50,000+">$50,000+</option>
              </select>
            </div>
          </div>

          <!-- Product/Service -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Product/Service Description</label>
            <textarea v-model="form.product_service" rows="3" class="w-full border border-gray-300 rounded-md px-3 py-2" placeholder="Describe your product or service..."></textarea>
          </div>

          <!-- Target Audience -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Target Audience</label>
            <textarea v-model="form.target_audience" rows="2" class="w-full border border-gray-300 rounded-md px-3 py-2" placeholder="Describe your target audience..."></textarea>
          </div>

          <!-- Generate Button -->
          <div class="flex justify-end">
            <button type="submit" :disabled="loading" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50 flex items-center">
              <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ loading ? 'Generating...' : 'Generate Pitch' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Generated Pitch Display -->
      <div v-if="generatedPitch" class="bg-white shadow rounded-lg p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-bold text-gray-900">Generated Pitch</h3>
          <button @click="generatedPitch = null" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        
        <div class="space-y-6">
          <div>
            <h4 class="font-semibold text-gray-900 mb-2">Title</h4>
            <p class="text-gray-700">{{ generatedPitch.title }}</p>
          </div>
          
          <div>
            <h4 class="font-semibold text-gray-900 mb-2">Executive Summary</h4>
            <p class="text-gray-700">{{ generatedPitch.executive_summary }}</p>
          </div>
          
          <div>
            <h4 class="font-semibold text-gray-900 mb-2">Strategy</h4>
            <p class="text-gray-700">{{ generatedPitch.strategy }}</p>
          </div>
          
          <div v-if="generatedPitch.key_messaging">
            <h4 class="font-semibold text-gray-900 mb-2">Key Messaging</h4>
            <div class="bg-gray-50 p-4 rounded-lg">
              <p><strong>Primary:</strong> {{ generatedPitch.key_messaging.primary }}</p>
              <p><strong>Secondary:</strong> {{ generatedPitch.key_messaging.secondary }}</p>
              <p><strong>Call to Action:</strong> {{ generatedPitch.key_messaging.call_to_action }}</p>
            </div>
          </div>
          
          <div v-if="generatedPitch.expected_outcomes">
            <h4 class="font-semibold text-gray-900 mb-2">Expected Outcomes</h4>
            <div class="grid grid-cols-2 gap-4">
              <div class="bg-blue-50 p-3 rounded-lg">
                <p class="text-sm text-blue-600 font-medium">Reach</p>
                <p class="text-blue-900">{{ generatedPitch.expected_outcomes.reach }}</p>
              </div>
              <div class="bg-green-50 p-3 rounded-lg">
                <p class="text-sm text-green-600 font-medium">Engagement Rate</p>
                <p class="text-green-900">{{ generatedPitch.expected_outcomes.engagement_rate }}</p>
              </div>
              <div class="bg-purple-50 p-3 rounded-lg">
                <p class="text-sm text-purple-600 font-medium">Conversion Rate</p>
                <p class="text-purple-900">{{ generatedPitch.expected_outcomes.conversion_rate }}</p>
              </div>
              <div class="bg-orange-50 p-3 rounded-lg">
                <p class="text-sm text-orange-600 font-medium">ROI Estimate</p>
                <p class="text-orange-900">{{ generatedPitch.expected_outcomes.roi_estimate }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Pitches -->
      <div v-if="recentPitches.length > 0" class="bg-white shadow rounded-lg p-6 mt-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Pitches</h3>
        <div class="space-y-4">
          <div v-for="pitch in recentPitches" :key="pitch.id" class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 cursor-pointer" @click="viewPitch(pitch)">
            <h4 class="font-medium text-gray-900">{{ pitch.title }}</h4>
            <p class="text-sm text-gray-500 mt-1">{{ pitch.industry }} • {{ pitch.ad_type }}</p>
            <p class="text-xs text-gray-400 mt-2">{{ new Date(pitch.created_at).toLocaleDateString() }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios'
import { onMounted, ref } from 'vue'

// Reactive data
const loading = ref(false)
const generatedPitch = ref(null)
const recentPitches = ref([])

// Form data
const form = ref({
  industry: '',
  ad_type: '',
  company_name: '',
  budget: '',
  product_service: '',
  target_audience: '',
  goals: ''
})

// Methods
const generatePitch = async () => {
  if (!form.value.industry || !form.value.ad_type) {
    alert('Please select industry and campaign objective')
    return
  }

  loading.value = true
  
  try {
    const response = await axios.post('/api/pitches/generate', form.value)
    
    if (response.data.success) {
      generatedPitch.value = response.data.data.content
      loadRecentPitches()
    } else {
      alert('Failed to generate pitch: ' + response.data.message)
    }
  } catch (error) {
    console.error('Error generating pitch:', error)
    alert('Failed to generate pitch. Please try again.')
  } finally {
    loading.value = false
  }
}

const loadRecentPitches = async () => {
  try {
    const response = await axios.get('/api/pitches/stats')
    if (response.data.success) {
      recentPitches.value = response.data.data.recent_pitches || []
    }
  } catch (error) {
    console.error('Error loading recent pitches:', error)
  }
}

const viewPitch = (pitch) => {
  generatedPitch.value = pitch.content
}

// Load data on component mount
onMounted(() => {
  console.log('AI Pitch Generator loaded')
  loadRecentPitches()
})
</script>
