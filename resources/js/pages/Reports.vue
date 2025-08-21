<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          {{ $t('navigation.reports') }}
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Export and download your ad performance reports
        </p>
      </div>
      <div class="mt-4 flex md:mt-0 md:ml-4">
        <ExportButton />
      </div>
    </div>

    <!-- Export History -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
          Export History
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
          Your recent report exports and downloads
        </p>
      </div>
      
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      </div>
      
      <div v-else-if="exports.length === 0" class="text-center py-12">
        <DocumentIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">No exports yet</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by creating your first export.</p>
      </div>
      
      <ul v-else class="divide-y divide-gray-200">
        <li
          v-for="exportItem in exports"
          :key="exportItem.id"
          class="px-4 py-4 sm:px-6"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <component
                  :is="getStatusIcon(exportItem.status)"
                  :class="[
                    'h-5 w-5',
                    getStatusColor(exportItem.status)
                  ]"
                />
              </div>
              <div class="ml-4">
                <div class="flex items-center">
                  <p class="text-sm font-medium text-gray-900 truncate">
                    {{ formatExportTitle(exportItem) }}
                  </p>
                  <span
                    :class="[
                      'ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      getStatusBadgeColor(exportItem.status)
                    ]"
                  >
                    {{ exportItem.status }}
                  </span>
                </div>
                <div class="mt-1 flex items-center text-sm text-gray-500">
                  <CalendarIcon class="flex-shrink-0 mr-1.5 h-4 w-4 text-gray-400" />
                  <p>
                    {{ formatDate(exportItem.created_at) }}
                  </p>
                  <span class="mx-2">•</span>
                  <p class="uppercase">{{ exportItem.format }}</p>
                  <span v-if="exportItem.params" class="mx-2">•</span>
                  <p v-if="exportItem.params">
                    {{ formatDateRange(exportItem.params) }}
                  </p>
                </div>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <button
                v-if="exportItem.status === 'completed'"
                @click="downloadExport(exportItem)"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <ArrowDownTrayIcon class="h-4 w-4 mr-1" />
                Download
              </button>
              <button
                @click="deleteExport(exportItem.id)"
                class="inline-flex items-center p-2 border border-transparent rounded-md text-gray-400 hover:text-red-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
              >
                <TrashIcon class="h-4 w-4" />
              </button>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- Quick Export Templates -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
          Quick Export Templates
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
          Pre-configured reports for common use cases
        </p>
      </div>
      
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 p-6">
        <div
          v-for="template in exportTemplates"
          :key="template.id"
          class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500"
        >
          <div class="flex-shrink-0">
            <component :is="template.icon" class="h-8 w-8 text-indigo-600" />
          </div>
          <div class="flex-1 min-w-0">
            <button @click="exportTemplate(template)" class="focus:outline-none">
              <span class="absolute inset-0" aria-hidden="true" />
              <p class="text-sm font-medium text-gray-900">{{ template.name }}</p>
              <p class="text-sm text-gray-500 truncate">{{ template.description }}</p>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import {
  DocumentIcon,
  CalendarIcon,
  ArrowDownTrayIcon,
  TrashIcon,
  CheckCircleIcon,
  ClockIcon,
  ExclamationCircleIcon,
  ChartBarIcon,
  CurrencyDollarIcon,
  UserGroupIcon,
  PhoneIcon
} from '@heroicons/vue/24/outline'
import ExportButton from '@/components/ExportButton.vue'
import axios from 'axios'

interface ExportItem {
  id: string
  format: string
  status: 'queued' | 'processing' | 'completed' | 'failed'
  created_at: string
  file_path?: string
  params?: any
}

interface ExportTemplate {
  id: string
  name: string
  description: string
  icon: any
  params: any
}

const exports = ref<ExportItem[]>([])
const loading = ref(true)

const exportTemplates: ExportTemplate[] = [
  {
    id: 'awareness-report',
    name: 'Awareness Report',
    description: 'CPM, reach, and impression metrics',
    icon: ChartBarIcon,
    params: { objective: 'awareness', format: 'xlsx' }
  },
  {
    id: 'leads-report',
    name: 'Leads Report',
    description: 'Lead generation and conversion metrics',
    icon: UserGroupIcon,
    params: { objective: 'leads', format: 'xlsx' }
  },
  {
    id: 'sales-report',
    name: 'Sales Report',
    description: 'Revenue, ROAS, and purchase metrics',
    icon: CurrencyDollarIcon,
    params: { objective: 'sales', format: 'xlsx' }
  },
  {
    id: 'calls-report',
    name: 'Calls Report',
    description: 'Phone call and conversion metrics',
    icon: PhoneIcon,
    params: { objective: 'calls', format: 'xlsx' }
  }
]

const fetchExports = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/reports')
    exports.value = response.data.data
  } catch (error) {
    console.error('Error fetching exports:', error)
  } finally {
    loading.value = false
  }
}

const downloadExport = (exportItem: ExportItem) => {
  if (exportItem.file_path) {
    const link = document.createElement('a')
    link.href = exportItem.file_path
    link.download = ''
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  }
}

const deleteExport = async (exportId: string) => {
  if (confirm('Are you sure you want to delete this export?')) {
    try {
      await axios.delete(`/api/reports/${exportId}`)
      exports.value = exports.value.filter(exp => exp.id !== exportId)
    } catch (error) {
      console.error('Error deleting export:', error)
    }
  }
}

const exportTemplate = async (template: ExportTemplate) => {
  try {
    const response = await axios.post('/api/reports/export', template.params)
    // Refresh the exports list
    fetchExports()
  } catch (error) {
    console.error('Error creating export:', error)
  }
}

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'completed':
      return CheckCircleIcon
    case 'failed':
      return ExclamationCircleIcon
    default:
      return ClockIcon
  }
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'completed':
      return 'text-green-500'
    case 'failed':
      return 'text-red-500'
    default:
      return 'text-yellow-500'
  }
}

const getStatusBadgeColor = (status: string) => {
  switch (status) {
    case 'completed':
      return 'bg-green-100 text-green-800'
    case 'failed':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-yellow-100 text-yellow-800'
  }
}

const formatExportTitle = (exportItem: ExportItem) => {
  const objective = exportItem.params?.objective || 'general'
  return `${objective.charAt(0).toUpperCase() + objective.slice(1)} Report`
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatDateRange = (params: any) => {
  if (params?.from && params?.to) {
    const from = new Date(params.from).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
    const to = new Date(params.to).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
    return `${from} - ${to}`
  }
  return ''
}

onMounted(() => {
  fetchExports()
})
</script>
