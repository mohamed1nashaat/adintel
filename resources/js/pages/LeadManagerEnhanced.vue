<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
          <div class="flex items-center">
            <button @click="$router.go(-1)" class="mr-4 p-2 text-gray-400 hover:text-gray-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
            </button>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Enhanced Lead Management</h1>
              <p class="text-gray-600 mt-1">Custom audiences, file upload, and advanced lead analytics</p>
            </div>
          </div>
          <div class="flex items-center space-x-3">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
              <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
              </svg>
              Enhanced Features Active
            </span>
            <button @click="showUploadModal = true" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
              Upload Leads
            </button>
            <button @click="showAudienceModal = true" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
              Create Audience
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              activeTab === tab.id
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
            ]"
          >
            {{ tab.name }}
            <span v-if="tab.count" class="ml-2 bg-gray-100 text-gray-900 py-0.5 px-2.5 rounded-full text-xs">
              {{ tab.count }}
            </span>
          </button>
        </nav>
      </div>

      <!-- Leads Tab -->
      <div v-if="activeTab === 'leads'">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
              <div class="bg-blue-100 rounded-lg p-3">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-2xl font-bold text-gray-900">{{ stats.totalLeads }}</p>
                <p class="text-gray-600 text-sm">Total Leads</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
              <div class="bg-green-100 rounded-lg p-3">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-2xl font-bold text-gray-900">{{ stats.qualifiedLeads }}</p>
                <p class="text-gray-600 text-sm">Qualified</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
              <div class="bg-purple-100 rounded-lg p-3">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-2xl font-bold text-gray-900">{{ stats.convertedLeads }}</p>
                <p class="text-gray-600 text-sm">Converted</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
              <div class="bg-yellow-100 rounded-lg p-3">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-2xl font-bold text-gray-900">${{ stats.totalValue }}</p>
                <p class="text-gray-600 text-sm">Total Value</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
            <div class="flex items-center space-x-4">
              <div class="relative">
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search leads..."
                  class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
              </div>
              
              <select v-model="selectedStatus" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Status</option>
                <option value="new">New</option>
                <option value="qualified">Qualified</option>
                <option value="contacted">Contacted</option>
                <option value="converted">Converted</option>
              </select>

              <select v-model="selectedSource" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Sources</option>
                <option value="facebook">Facebook</option>
                <option value="google">Google</option>
                <option value="website">Website</option>
                <option value="upload">File Upload</option>
              </select>
            </div>

            <div class="flex items-center space-x-2">
              <button @click="exportLeads" class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
              </button>
              <button @click="refreshData" class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Leads Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lead</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="lead in filteredLeads" :key="lead.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                      <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                        <span class="text-sm font-medium text-gray-700">{{ lead.name.charAt(0) }}</span>
                      </div>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">{{ lead.name }}</div>
                      <div class="text-sm text-gray-500">{{ lead.email }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="sourceClass(lead.source)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ lead.source }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="statusClass(lead.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ lead.status }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  ${{ lead.value }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(lead.createdAt) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <button @click="editLead(lead)" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                  <button @click="deleteLead(lead.id)" class="text-red-600 hover:text-red-900">Delete</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Custom Audiences Tab -->
      <div v-if="activeTab === 'audiences'">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="audience in customAudiences"
            :key="audience.id"
            class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow"
          >
            <div class="flex items-start justify-between mb-4">
              <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ audience.name }}</h3>
                <p class="text-gray-600 text-sm">{{ audience.description }}</p>
              </div>
              <div class="flex items-center space-x-1">
                <button @click="editAudience(audience)" class="p-1 text-gray-400 hover:text-gray-600">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                  </svg>
                </button>
                <button @click="deleteAudience(audience.id)" class="p-1 text-gray-400 hover:text-red-600">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                </button>
              </div>
            </div>

            <div class="space-y-3">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Size:</span>
                <span class="font-medium">{{ audience.size }} leads</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Created:</span>
                <span class="font-medium">{{ formatDate(audience.createdAt) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Last Sync:</span>
                <span class="font-medium">{{ audience.lastSync || 'Never' }}</span>
              </div>
            </div>

            <div class="mt-4 flex space-x-2">
              <button
                @click="syncAudience(audience.id)"
                class="flex-1 px-3 py-2 text-xs font-medium text-blue-700 bg-blue-100 rounded-md hover:bg-blue-200 transition-colors"
              >
                Sync to Platforms
              </button>
              <button
                @click="exportAudience(audience.id)"
                class="flex-1 px-3 py-2 text-xs font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors"
              >
                Export
              </button>
            </div>
          </div>

          <!-- Create New Audience Card -->
          <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-6 flex flex-col items-center justify-center hover:border-gray-400 transition-colors cursor-pointer" @click="showAudienceModal = true">
            <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Create New Audience</h3>
            <p class="text-gray-600 text-sm text-center">Build custom audiences based on lead criteria and date ranges</p>
          </div>
        </div>
      </div>

      <!-- Analytics Tab -->
      <div v-if="activeTab === 'analytics'">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Lead Sources Chart -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Lead Sources</h3>
            <div class="space-y-4">
              <div v-for="source in leadSources" :key="source.name" class="flex items-center justify-between">
                <div class="flex items-center">
                  <div :class="source.color" class="w-4 h-4 rounded mr-3"></div>
                  <span class="text-sm text-gray-700">{{ source.name }}</span>
                </div>
                <div class="flex items-center space-x-2">
                  <span class="text-sm font-medium text-gray-900">{{ source.count }}</span>
                  <span class="text-xs text-gray-500">({{ source.percentage }}%)</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Conversion Funnel -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Conversion Funnel</h3>
            <div class="space-y-4">
              <div v-for="stage in conversionFunnel" :key="stage.name" class="relative">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm text-gray-700">{{ stage.name }}</span>
                  <span class="text-sm font-medium text-gray-900">{{ stage.count }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div :class="stage.color" class="h-2 rounded-full transition-all duration-300" :style="{ width: stage.percentage + '%' }"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Upload Modal -->
    <FileUploadModal
      v-if="showUploadModal"
      @close="showUploadModal = false"
      @upload="handleFileUpload"
    />

    <!-- Audience Creation Modal -->
    <AudienceCreateModal
      v-if="showAudienceModal"
      :audience="selectedAudience"
      @close="showAudienceModal = false"
      @save="saveAudience"
    />
  </div>
</template>

<script setup lang="ts">
import AudienceCreateModal from '@/components/AudienceCreateModal.vue'
import FileUploadModal from '@/components/FileUploadModal.vue'
import { computed, onMounted, ref } from 'vue'

// Reactive data
const activeTab = ref('leads')
const searchQuery = ref('')
const selectedStatus = ref('')
const selectedSource = ref('')
const showUploadModal = ref(false)
const showAudienceModal = ref(false)
const selectedAudience = ref(null)

const tabs = ref([
  { id: 'leads', name: 'Leads', count: 1247 },
  { id: 'audiences', name: 'Custom Audiences', count: 8 },
  { id: 'analytics', name: 'Analytics', count: null }
])

const stats = ref({
  totalLeads: 1247,
  qualifiedLeads: 892,
  convertedLeads: 156,
  totalValue: '45,230'
})

const leads = ref([
  {
    id: 1,
    name: 'John Smith',
    email: 'john@example.com',
    source: 'facebook',
    status: 'qualified',
    value: 1200,
    createdAt: '2024-01-15T10:30:00Z'
  },
  {
    id: 2,
    name: 'Sarah Johnson',
    email: 'sarah@example.com',
    source: 'google',
    status: 'new',
    value: 800,
    createdAt: '2024-01-15T09:15:00Z'
  },
  {
    id: 3,
    name: 'Mike Wilson',
    email: 'mike@example.com',
    source: 'website',
    status: 'converted',
    value: 2500,
    createdAt: '2024-01-14T16:45:00Z'
  },
  {
    id: 4,
    name: 'Lisa Brown',
    email: 'lisa@example.com',
    source: 'upload',
    status: 'contacted',
    value: 950,
    createdAt: '2024-01-14T14:20:00Z'
  }
])

const customAudiences = ref([
  {
    id: 1,
    name: 'High-Value Prospects',
    description: 'Leads with value > $1000 from last 30 days',
    size: 234,
    createdAt: '2024-01-10T10:00:00Z',
    lastSync: '2024-01-15T08:30:00Z'
  },
  {
    id: 2,
    name: 'Facebook Leads - Q1',
    description: 'All Facebook leads from Q1 2024',
    size: 567,
    createdAt: '2024-01-05T15:30:00Z',
    lastSync: '2024-01-14T12:15:00Z'
  },
  {
    id: 3,
    name: 'Converted Customers',
    description: 'Leads that have converted to customers',
    size: 156,
    createdAt: '2024-01-01T09:00:00Z',
    lastSync: null
  }
])

const leadSources = ref([
  { name: 'Facebook', count: 456, percentage: 37, color: 'bg-blue-500' },
  { name: 'Google', count: 342, percentage: 27, color: 'bg-green-500' },
  { name: 'Website', count: 289, percentage: 23, color: 'bg-purple-500' },
  { name: 'File Upload', count: 160, percentage: 13, color: 'bg-orange-500' }
])

const conversionFunnel = ref([
  { name: 'Total Leads', count: 1247, percentage: 100, color: 'bg-blue-500' },
  { name: 'Qualified', count: 892, percentage: 72, color: 'bg-green-500' },
  { name: 'Contacted', count: 445, percentage: 36, color: 'bg-yellow-500' },
  { name: 'Converted', count: 156, percentage: 13, color: 'bg-purple-500' }
])

// Computed properties
const filteredLeads = computed(() => {
  let filtered = leads.value

  if (searchQuery.value) {
    filtered = filtered.filter(lead =>
      lead.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      lead.email.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  }

  if (selectedStatus.value) {
    filtered = filtered.filter(lead => lead.status === selectedStatus.value)
  }

  if (selectedSource.value) {
    filtered = filtered.filter(lead => lead.source === selectedSource.value)
  }

  return filtered
})

// Methods
const sourceClass = (source: string) => {
  const classes = {
    facebook: 'bg-blue-100 text-blue-800',
    google: 'bg-green-100 text-green-800',
    website: 'bg-purple-100 text-purple-800',
    upload: 'bg-orange-100 text-orange-800'
  }
  return classes[source] || 'bg-gray-100 text-gray-800'
}

const statusClass = (status: string) => {
  const classes = {
    new: 'bg-gray-100 text-gray-800',
    qualified: 'bg-blue-100 text-blue-800',
    contacted: 'bg-yellow-100 text-yellow-800',
    converted: 'bg-green-100 text-green-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const refreshData = () => {
  console.log('Refreshing lead data...')
}

const exportLeads = () => {
  console.log('Exporting leads...')
}

const editLead = (lead: any) => {
  console.log('Editing lead:', lead)
}

const deleteLead = (leadId: number) => {
  if (confirm('Are you sure you want to delete this lead?')) {
    leads.value = leads.value.filter(lead => lead.id !== leadId)
  }
}

const editAudience = (audience: any) => {
  selectedAudience.value = audience
  showAudienceModal.value = true
}

const deleteAudience = (audienceId: number) => {
  if (confirm('Are you sure you want to delete this audience?')) {
    customAudiences.value = customAudiences.value.filter(audience => audience.id !== audienceId)
  }
}

const syncAudience = (audienceId: number) => {
  console.log('Syncing audience to platforms:', audienceId)
  // Update last sync time
  const audience = customAudiences.value.find(a => a.id === audienceId)
  if (audience) {
    audience.lastSync = new Date().toISOString()
  }
}

const exportAudience = (audienceId: number) => {
  console.log('Exporting audience:', audienceId)
}

const handleFileUpload = (fileData: any) => {
  console.log('File uploaded:', fileData)
  showUploadModal.value = false
  // Add uploaded leads to the list
  // This would typically make an API call
}

const saveAudience = (audienceData: any) => {
  if (audienceData.id) {
    // Update existing audience
    const index = customAudiences.value.findIndex(a => a.id === audienceData.id)
    if (index !== -1) {
      customAudiences.value[index] = { ...customAudiences.value[index], ...audienceData }
    }
  } else {
    // Create new audience
    const newAudience = {
      ...audienceData,
      id: Date.now(),
      createdAt: new Date().toISOString(),
      lastSync: null
    }
    customAudiences.value.unshift(newAudience)
  }
  showAudienceModal.value = false
  selectedAudience.value = null
}

onMounted(() => {
  // Initialize component
})
</script>

<style scoped>
.transition-colors {
  transition: color 0.2s ease;
}

.transition-shadow {
  transition: box-shadow 0.2s ease;
}
</style>
