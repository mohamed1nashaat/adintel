<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">AI-Powered Pitch Generator</h1>
        <p class="mt-2 text-gray-600">Generate compelling advertising pitches using artificial intelligence</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Total Pitches</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.total_pitches || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">This Month</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.pitches_this_month || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Templates</p>
              <p class="text-2xl font-semibold text-gray-900">{{ templates.length || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Industries</p>
              <p class="text-2xl font-semibold text-gray-900">{{ Object.keys(industries).length }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Pitch Generator Form -->
        <div class="lg:col-span-2">
          <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-medium text-gray-900">Generate New Pitch</h2>
              <p class="text-sm text-gray-500">Create a customized advertising pitch using AI</p>
            </div>
            
            <form @submit.prevent="generatePitch" class="p-6 space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Industry Selection -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Industry</label>
                  <select v-model="pitchForm.industry" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Industry</option>
                    <option v-for="(label, value) in industries" :key="value" :value="value">{{ label }}</option>
                  </select>
                </div>

                <!-- Ad Type Selection -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Campaign Objective</label>
                  <select v-model="pitchForm.ad_type" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                  <input v-model="pitchForm.company_name" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Your company name">
                </div>

                <!-- Budget -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Budget Range</label>
                  <select v-model="pitchForm.budget" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                <textarea v-model="pitchForm.product_service" rows="3" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Describe your product or service..."></textarea>
              </div>

              <!-- Target Audience -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Target Audience</label>
                <textarea v-model="pitchForm.target_audience" rows="2" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Describe your target audience..."></textarea>
              </div>

              <!-- Goals -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Campaign Goals</label>
                <textarea v-model="pitchForm.goals" rows="2" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="What do you want to achieve with this campaign?"></textarea>
              </div>

              <!-- Generate Button -->
              <div class="flex justify-end">
                <button type="submit" :disabled="generating" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                  <svg v-if="generating" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ generating ? 'Generating...' : 'Generate Pitch' }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Recent Pitches & Templates -->
        <div class="space-y-6">
          <!-- Recent Pitches -->
          <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">Recent Pitches</h3>
            </div>
            <div class="p-6">
              <div v-if="recentPitches.length === 0" class="text-center text-gray-500 py-4">
                No pitches generated yet
              </div>
              <div v-else class="space-y-4">
                <div v-for="pitch in recentPitches" :key="pitch.id" class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 cursor-pointer" @click="viewPitch(pitch)">
                  <h4 class="font-medium text-gray-900 truncate">{{ pitch.title }}</h4>
                  <p class="text-sm text-gray-500 mt-1">{{ pitch.industry }} • {{ pitch.ad_type }}</p>
                  <p class="text-xs text-gray-400 mt-2">{{ formatDate(pitch.created_at) }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">Quick Actions</h3>
            </div>
            <div class="p-6 space-y-3">
              <button @click="showAdCopyGenerator = true" class="w-full text-left px-4 py-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <div class="flex items-center">
                  <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                    </svg>
                  </div>
                  <div>
                    <p class="font-medium text-gray-900">Generate Ad Copy</p>
                    <p class="text-sm text-gray-500">Create compelling ad variations</p>
                  </div>
                </div>
              </button>

              <button @click="showStrategyGenerator = true" class="w-full text-left px-4 py-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <div class="flex items-center">
                  <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                      <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 102 0V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 2a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                    </svg>
                  </div>
                  <div>
                    <p class="font-medium text-gray-900">Campaign Strategy</p>
                    <p class="text-sm text-gray-500">Get strategic recommendations</p>
                  </div>
                </div>
              </button>

              <button @click="showAudienceInsights = true" class="w-full text-left px-4 py-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <div class="flex items-center">
                  <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                    <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
                    </svg>
                  </div>
                  <div>
                    <p class="font-medium text-gray-900">Audience Insights</p>
                    <p class="text-sm text-gray-500">Understand your target market</p>
                  </div>
                </div>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Generated Pitch Modal -->
      <div v-if="generatedPitch" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="generatedPitch = null">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white" @click.stop>
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">Generated Pitch</h3>
            <button @click="generatedPitch = null" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <div class="max-h-96 overflow-y-auto">
            <div class="space-y-6">
              <div>
                <h4 class="font-semibold text-gray-900 mb-2">Executive Summary</h4>
                <p class="text-gray-700">{{ generatedPitch.executive_summary }}</p>
              </div>
              
              <div>
                <h4 class="font-semibold text-gray-900 mb-2">Strategy</h4>
                <p class="text-gray-700">{{ generatedPitch.strategy }}</p>
              </div>
              
              <div>
                <h4 class="font-semibold text-gray-900 mb-2">Key Messaging</h4>
                <div class="bg-gray-50 p-4 rounded-lg">
                  <p><strong>Primary:</strong> {{ generatedPitch.key_messaging?.primary }}</p>
                  <p><strong>Secondary:</strong> {{ generatedPitch.key_messaging?.secondary }}</p>
                  <p><strong>Call to Action:</strong> {{ generatedPitch.key_messaging?.call_to_action }}</p>
                </div>
              </div>
              
              <div>
                <h4 class="font-semibold text-gray-900 mb-2">Expected Outcomes</h4>
                <div class="grid grid-cols-2 gap-4">
                  <div class="bg-blue-50 p-3 rounded-lg">
                    <p class="text-sm text-blue-600 font-medium">Reach</p>
                    <p class="text-blue-900">{{ generatedPitch.expected_outcomes?.reach }}</p>
                  </div>
                  <div class="bg-green-50 p-3 rounded-lg">
                    <p class="text-sm text-green-600 font-medium">Engagement Rate</p>
                    <p class="text-green-900">{{ generatedPitch.expected_outcomes?.engagement_rate }}</p>
                  </div>
                  <div class="bg-purple-50 p-3 rounded-lg">
                    <p class="text-sm text-purple-600 font-medium">Conversion Rate</p>
                    <p class="text-purple-900">{{ generatedPitch.expected_outcomes?.conversion_rate }}</p>
                  </div>
                  <div class="bg-orange-50 p-3 rounded-lg">
                    <p class="text-sm text-orange-600 font-medium">ROI Estimate</p>
                    <p class="text-orange-900">{{ generatedPitch.expected_outcomes?.roi_estimate }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="flex justify-end mt-6 space-x-3">
            <button @click="generatedPitch = null" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
              Close
            </button>
            <button @click="savePitch" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
              Save Pitch
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import axios from 'axios'
import { onMounted, ref } from 'vue'

// Reactive data
const stats = ref({})
const industries = ref({})
const templates = ref([])
const recentPitches = ref([])
const generating = ref(false)
const generatedPitch = ref(null)

// Form data
const pitchForm = ref({
  industry: '',
  ad_type: '',
  company_name: '',
  budget: '',
  product_service: '',
  target_audience: '',
  goals: ''
})

// Modal states
const showAdCopyGenerator = ref(false)
const showStrategyGenerator = ref(false)
const showAudienceInsights = ref(false)

// Methods
const loadData = async () => {
  try {
    // Load stats
    const statsResponse = await axios.get('/api/pitches/stats')
    stats.value = statsResponse.data.data

    // Load industries
    const industriesResponse = await axios.get('/api/pitches/industries')
    industries.value = industriesResponse.data.data

    // Load templates
    const templatesResponse = await axios.get('/api/pitches/templates')
    templates.value = templatesResponse.data.data

    // Set recent pitches from stats
    recentPitches.value = stats.value.recent_pitches || []
  } catch (error) {
    console.error('Error loading data:', error)
  }
}

const generatePitch = async () => {
  if (!pitchForm.value.industry || !pitchForm.value.ad_type) {
    alert('Please select industry and campaign objective')
    return
  }

  generating.value = true
  
  try {
    const response = await axios.post('/api/pitches/generate', pitchForm.value)
    
    if (response.data.success) {
      generatedPitch.value = response.data.data.content
      // Refresh recent pitches
      await loadData()
    } else {
      alert('Failed to generate pitch: ' + response.data.message)
    }
  } catch (error) {
    console.error('Error generating pitch:', error)
    alert('Failed to generate pitch. Please try again.')
  } finally {
    generating.value = false
  }
}

const savePitch = async () => {
  // Pitch is already saved by the generate endpoint
  generatedPitch.value = null
  alert('Pitch saved successfully!')
}

const viewPitch = (pitch) => {
  generatedPitch.value = pitch.content
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}

// Load data on component mount
onMounted(() => {
  console.log('AI Pitch Generator loaded')
  loadData()
})
</script>
</template>
