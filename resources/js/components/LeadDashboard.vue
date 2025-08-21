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
          <PlusIcon class="h-4 w-4 mr-2" aria-hidden="true" />
          Add Lead
        </button>
        <button
          @click="exportLeads"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <ArrowDownTrayIcon class="h-4 w-4 mr-2" aria-hidden="true" />
          Export
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <div v-for="stat in stats" :key="stat.name" class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <component :is="stat.icon" class="h-6 w-6 text-gray-400" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">{{ stat.name }}</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stat.value }}</dd>
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
              @input="debouncedSearch"
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
        <div class="flex items-center justify-between">
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            Leads ({{ pagination.total }})
          </h3>
          <div class="flex items-center space-x-2">
            <button
              v-if="selectedLeads.length > 0"
              @click="showBulkActions = !showBulkActions"
              class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Bulk Actions ({{ selectedLeads.length }})
              <ChevronDownIcon class="ml-2 -mr-0.5 h-4 w-4" aria-hidden="true" />
            </button>
          </div>
        </div>
        
        <!-- Bulk Actions Dropdown -->
        <div v-if="showBulkActions && selectedLeads.length > 0" class="mt-4 p-4 bg-gray-50 rounded-md">
          <div class="flex items-center space-x-4">
            <select v-model="bulkAction.type" class="block w-40 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
              <option value="">Select Action</option>
              <option value="status">Update Status</option>
              <option value="quality">Update Quality</option>
              <option value="assign">Assign To</option>
              <option value="sync">Sync to Sheets</option>
            </select>
            
            <select v-if="bulkAction.type === 'status'" v-model="bulkAction.value" class="block w-40 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
              <option value="new">New</option>
              <option value="contacted">Contacted</option>
              <option value="qualified">Qualified</option>
              <option value="converted">Converted</option>
              <option value="lost">Lost</option>
            </select>
            
            <select v-if="bulkAction.type === 'quality'" v-model="bulkAction.value" class="block w-40 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
              <option value="hot">Hot</option>
              <option value="warm">Warm</option>
              <option value="cold">Cold</option>
              <option value="unqualified">Unqualified</option>
            </select>
            
            <button
              @click="executeBulkAction"
              :disabled="!bulkAction.type || (bulkAction.type !== 'sync' && !bulkAction.value)"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
            >
              Apply
            </button>
          </div>
        </div>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="relative px-6 py-3">
                <input
                  type="checkbox"
                  :checked="selectedLeads.length === leads.length && leads.length > 0"
                  @change="toggleSelectAll"
                  class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
              </th>
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
              <td class="relative px-6 py-4">
                <input
                  type="checkbox"
                  :value="lead.id"
                  v-model="selectedLeads"
                  class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
              </td>
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
      
      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
        <div class="flex-1 flex justify-between sm:hidden">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            Previous
          </button>
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            Next
          </button>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700">
              Showing {{ (pagination.current_page - 1) * pagination.per_page + 1 }} to 
              {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} of 
              {{ pagination.total }} results
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
              <button
                @click="changePage(pagination.current_page - 1)"
                :disabled="pagination.current_page === 1"
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
              >
                <ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
              </button>
              <button
                @click="changePage(pagination.current_page + 1)"
                :disabled="pagination.current_page === pagination.last_page"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
              >
                <ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
    ArrowDownTrayIcon,
    CheckCircleIcon,
    ChevronDownIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ClockIcon,
    FireIcon,
    PlusIcon,
    UserGroupIcon
} from '@heroicons/vue/24/outline'
import { debounce } from 'lodash-es'
import { computed, onMounted, ref } from 'vue'

// Data
const leads = ref([])
const leadSources = ref([])
const loading = ref(false)
const selectedLeads = ref([])
const showBulkActions = ref(false)
const showCreateModal = ref(false)

const filters = ref({
  status: '',
  quality: '',
  source_id: '',
  search: '',
})

const bulkAction = ref({
  type: '',
  value: ''
})

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0
})

const statsData = ref({
  total: 0,
  new: 0,
  converted: 0,
  hot: 0
})

// Computed
const stats = computed(() => [
  {
    name: 'Total Leads',
    value: statsData.value.total,
    icon: UserGroupIcon
  },
  {
    name: 'New Leads',
    value: statsData.value.new,
    icon: ClockIcon
  },
  {
    name: 'Hot Leads',
    value: statsData.value.hot,
    icon: FireIcon
  },
  {
    name: 'Converted',
    value: statsData.value.converted,
    icon: CheckCircleIcon
  }
])

// Methods
const fetchLeads = async (page = 1) => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: page.toString(),
      per_page: pagination.value.per_page.toString(),
      ...filters.value
    })
    
    const response = await fetch(`/api/leads?${params}`)
    const data = await response.json()
    
    leads.value = data.data
    pagination.value = data.meta
  } catch (error) {
    console.error('Error fetching leads:', error)
  } finally {
    loading.value = false
  }
}

const fetchLeadSources = async () => {
  try {
    const response = await fetch('/api/lead-sources')
    const data = await response.json()
    leadSources.value = data.data
  } catch (error) {
    console.error('Error fetching lead sources:', error)
  }
}

const fetchStats = async () => {
  try {
    const response = await fetch('/api/leads/stats')
    const data = await response.json()
    statsData.value = data.data
  } catch (error) {
    console.error('Error fetching stats:', error)
  }
}

const debouncedSearch = debounce(() => {
  fetchLeads(1)
}, 300)

const changePage = (page: number) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    fetchLeads(page)
  }
}

const toggleSelectAll = () => {
  if (selectedLeads.value.length === leads.value.length) {
    selectedLeads.value = []
  } else {
    selectedLeads.value = leads.value.map(lead => lead.id)
  }
}

const executeBulkAction = async () => {
  if (!bulkAction.value.type || selectedLeads.value.length === 0) return
  
  try {
    if (bulkAction.value.type === 'sync') {
      await fetch('/api/leads/bulk-sync-sheets', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ lead_ids: selectedLeads.value })
      })
    } else {
      const updates = {}
      updates[bulkAction.value.type] = bulkAction.value.value
      
      await fetch('/api/leads/bulk-update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          lead_ids: selectedLeads.value,
          updates
        })
      })
    }
    
    selectedLeads.value = []
    showBulkActions.value = false
    bulkAction.value = { type: '', value: '' }
    fetchLeads()
  } catch (error) {
    console.error('Error executing bulk action:', error)
  }
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
    // Show success message
  } catch (error) {
    console.error('Error exporting leads:', error)
  }
}

const viewLead = (lead) => {
  // Navigate to lead detail view
  console.log('View lead:', lead)
}

const editLead = (lead) => {
  // Open edit modal
  console.log('Edit lead:', lead)
}

const getStatusBadgeClass = (status: string) => {
  const classes = {
    new: 'bg-blue-100 text-blue-800',
    contacted: 'bg-yellow-100 text-yellow-800',
    qualified: 'bg-green-100 text-green-800',
    converted: 'bg-purple-100 text-purple-800',
    lost: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getQualityBadgeClass = (quality: string) => {
  const classes = {
    hot: 'bg-red-100 text-red-800',
    warm: 'bg-orange-100 text-orange-800',
    cold: 'bg-blue-100 text-blue-800',
    unqualified: 'bg-gray-100 text-gray-800'
  }
  return classes[quality] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString()
}

// Lifecycle
onMounted(() => {
  fetchLeads()
  fetchLeadSources()
  fetchStats()
})
</script>
</template>
