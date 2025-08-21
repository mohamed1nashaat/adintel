<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          Lead Management
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Manage and track your leads from all sources
        </p>
      </div>
      <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Add Lead
        </button>
        <button
          @click="exportLeads"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Export
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-6 w-6 text-gray-400">👥</div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Leads</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.total }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-6 w-6 text-gray-400">🆕</div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">New Leads</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.new }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-6 w-6 text-gray-400">🔥</div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Hot Leads</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.hot }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-6 w-6 text-gray-400">✅</div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Converted</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.converted }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <label for="status-filter" class="block text-sm font-medium text-gray-700">Status</label>
            <select
              id="status-filter"
              v-model="filters.status"
              @change="fetchLeads"
              class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
            >
              <option value="">All Statuses</option>
              <option value="new">New</option>
              <option value="contacted">Contacted</option>
              <option value="qualified">Qualified</option>
              <option value="converted">Converted</option>
              <option value="lost">Lost</option>
            </select>
          </div>
          
          <div>
            <label for="quality-filter" class="block text-sm font-medium text-gray-700">Quality</label>
            <select
              id="quality-filter"
              v-model="filters.quality"
              @change="fetchLeads"
              class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
            >
              <option value="">All Qualities</option>
              <option value="hot">Hot</option>
              <option value="warm">Warm</option>
              <option value="cold">Cold</option>
              <option value="unqualified">Unqualified</option>
            </select>
          </div>
          
          <div>
            <label for="source-filter" class="block text-sm font-medium text-gray-700">Source</label>
            <select
              id="source-filter"
              v-model="filters.source_id"
              @change="fetchLeads"
              class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
            >
              <option value="">All Sources</option>
              <option v-for="source in leadSources" :key="source.id" :value="source.id">
                {{ source.name }}
              </option>
            </select>
          </div>
          
          <div>
            <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
            <input
              id="search"
              v-model="filters.search"
              @input="handleSearch"
              type="text"
              placeholder="Search leads..."
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Leads Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
          Leads ({{ leads.length }})
        </h3>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Lead
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Quality
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Source
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Created
              </th>
              <th scope="col" class="relative px-6 py-3">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="lead in leads" :key="lead.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div>
                    <div class="text-sm font-medium text-gray-900">
                      {{ lead.first_name }} {{ lead.last_name }}
                    </div>
                    <div class="text-sm text-gray-500">{{ lead.email }}</div>
                    <div v-if="lead.company" class="text-sm text-gray-500">{{ lead.company }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusBadgeClass(lead.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                  {{ lead.status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="lead.quality" :class="getQualityBadgeClass(lead.quality)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                  {{ lead.quality }}
                </span>
                <span v-else class="text-gray-400">-</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ lead.lead_source?.name || 'Unknown' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(lead.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button
                  @click="viewLead(lead)"
                  class="text-indigo-600 hover:text-indigo-900 mr-4"
                >
                  View
                </button>
                <button
                  @click="editLead(lead)"
                  class="text-indigo-600 hover:text-indigo-900"
                >
                  Edit
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'

// Data
const leads = ref([])
const leadSources = ref([])
const loading = ref(false)
const showCreateModal = ref(false)

const filters = ref({
  status: '',
  quality: '',
  source_id: '',
  search: '',
})

const stats = ref({
  total: 0,
  new: 0,
  converted: 0,
  hot: 0
})

// Methods
const fetchLeads = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams(filters.value)
    const response = await fetch(`/api/leads?${params}`)
    const data = await response.json()
    leads.value = data.data || []
  } catch (error) {
    console.error('Error fetching leads:', error)
    leads.value = []
  } finally {
    loading.value = false
  }
}

const fetchLeadSources = async () => {
  try {
    const response = await fetch('/api/lead-sources')
    const data = await response.json()
    leadSources.value = data.data || []
  } catch (error) {
    console.error('Error fetching lead sources:', error)
    leadSources.value = []
  }
}

const fetchStats = async () => {
  try {
    const response = await fetch('/api/leads/stats')
    const data = await response.json()
    stats.value = data.data || { total: 0, new: 0, converted: 0, hot: 0 }
  } catch (error) {
    console.error('Error fetching stats:', error)
    stats.value = { total: 0, new: 0, converted: 0, hot: 0 }
  }
}

let searchTimeout
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchLeads()
  }, 300)
}

const exportLeads = async () => {
  try {
    await fetch('/api/leads/export', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        format: 'csv',
        filters: filters.value
      })
    })
    alert('Export started! You will receive an email when ready.')
  } catch (error) {
    console.error('Error exporting leads:', error)
    alert('Export failed. Please try again.')
  }
}

const viewLead = (lead) => {
  console.log('View lead:', lead)
  // TODO: Navigate to lead detail view
}

const editLead = (lead) => {
  console.log('Edit lead:', lead)
  // TODO: Open edit modal
}

const getStatusBadgeClass = (status) => {
  const classes = {
    new: 'bg-blue-100 text-blue-800',
    contacted: 'bg-yellow-100 text-yellow-800',
    qualified: 'bg-green-100 text-green-800',
    converted: 'bg-purple-100 text-purple-800',
    lost: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getQualityBadgeClass = (quality) => {
  const classes = {
    hot: 'bg-red-100 text-red-800',
    warm: 'bg-orange-100 text-orange-800',
    cold: 'bg-blue-100 text-blue-800',
    unqualified: 'bg-gray-100 text-gray-800'
  }
  return classes[quality] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}

// Lifecycle
onMounted(() => {
  fetchLeads()
  fetchLeadSources()
  fetchStats()
})
</script>
