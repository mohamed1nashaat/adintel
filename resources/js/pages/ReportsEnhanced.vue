<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          Reports & Analytics
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Generate comprehensive reports across all platforms and campaigns
        </p>
      </div>
      <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
        <button
          @click="refreshReports"
          :disabled="loading"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          <ArrowPathIcon 
            :class="['h-4 w-4 mr-2', loading ? 'animate-spin' : '']" 
            aria-hidden="true" 
          />
          Refresh
        </button>
        <button
          @click="showCreateReport = true"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <PlusIcon class="h-4 w-4 mr-2" aria-hidden="true" />
          Create Report
        </button>
      </div>
    </div>

    <!-- Report Stats -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <DocumentTextIcon class="h-6 w-6 text-blue-400" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Reports</dt>
                <dd class="text-lg font-medium text-gray-900">{{ totalReports }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ClockIcon class="h-6 w-6 text-yellow-400" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Scheduled Reports</dt>
                <dd class="text-lg font-medium text-gray-900">{{ scheduledReports }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ArrowDownTrayIcon class="h-6 w-6 text-green-400" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Downloads (30d)</dt>
                <dd class="text-lg font-medium text-gray-900">{{ monthlyDownloads }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ChartBarIcon class="h-6 w-6 text-purple-400" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Data Points</dt>
                <dd class="text-lg font-medium text-gray-900">{{ dataPoints.toLocaleString() }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Report Templates -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
          Quick Report Templates
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
          Generate reports instantly with pre-built templates
        </p>
      </div>
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 p-6">
        <!-- Performance Overview -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-6 hover:shadow-md transition-shadow cursor-pointer" @click="generateReport('performance')">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center">
              <ChartBarIcon class="w-6 h-6 text-white" />
            </div>
            <span class="text-xs bg-blue-600 text-white px-2 py-1 rounded-full">Popular</span>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Performance Overview</h3>
          <p class="text-sm text-gray-600 mb-4">Complete performance metrics across all platforms and campaigns</p>
          <div class="flex items-center justify-between text-xs text-gray-500">
            <span>All Platforms</span>
            <span>30 days</span>
          </div>
        </div>

        <!-- GCC Market Analysis -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-6 hover:shadow-md transition-shadow cursor-pointer" @click="generateReport('gcc')">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center">
              <GlobeAltIcon class="w-6 h-6 text-white" />
            </div>
            <span class="text-xs bg-green-600 text-white px-2 py-1 rounded-full">Regional</span>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">GCC Market Analysis</h3>
          <p class="text-sm text-gray-600 mb-4">Regional performance analysis for Gulf Cooperation Council markets</p>
          <div class="flex items-center justify-between text-xs text-gray-500">
            <span>KSA, UAE, Qatar+</span>
            <span>Custom period</span>
          </div>
        </div>

        <!-- ROI & ROAS Report -->
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-lg p-6 hover:shadow-md transition-shadow cursor-pointer" @click="generateReport('roi')">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center">
              <CurrencyDollarIcon class="w-6 h-6 text-white" />
            </div>
            <span class="text-xs bg-purple-600 text-white px-2 py-1 rounded-full">Financial</span>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">ROI & ROAS Report</h3>
          <p class="text-sm text-gray-600 mb-4">Return on investment and ad spend analysis with profitability insights</p>
          <div class="flex items-center justify-between text-xs text-gray-500">
            <span>Revenue Focus</span>
            <span>Quarterly</span>
          </div>
        </div>

        <!-- Campaign Comparison -->
        <div class="bg-gradient-to-br from-orange-50 to-orange-100 border border-orange-200 rounded-lg p-6 hover:shadow-md transition-shadow cursor-pointer" @click="generateReport('comparison')">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center">
              <ArrowsRightLeftIcon class="w-6 h-6 text-white" />
            </div>
            <span class="text-xs bg-orange-600 text-white px-2 py-1 rounded-full">Analysis</span>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Campaign Comparison</h3>
          <p class="text-sm text-gray-600 mb-4">Side-by-side campaign performance comparison and optimization insights</p>
          <div class="flex items-center justify-between text-xs text-gray-500">
            <span>Multi-campaign</span>
            <span>Flexible</span>
          </div>
        </div>

        <!-- Audience Insights -->
        <div class="bg-gradient-to-br from-pink-50 to-pink-100 border border-pink-200 rounded-lg p-6 hover:shadow-md transition-shadow cursor-pointer" @click="generateReport('audience')">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-pink-600 rounded-lg flex items-center justify-center">
              <UsersIcon class="w-6 h-6 text-white" />
            </div>
            <span class="text-xs bg-pink-600 text-white px-2 py-1 rounded-full">Insights</span>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Audience Insights</h3>
          <p class="text-sm text-gray-600 mb-4">Demographic analysis and audience behavior patterns across platforms</p>
          <div class="flex items-center justify-between text-xs text-gray-500">
            <span>Demographics</span>
            <span>Behavioral</span>
          </div>
        </div>

        <!-- Custom Report Builder -->
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow cursor-pointer" @click="showCustomBuilder = true">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-gray-600 rounded-lg flex items-center justify-center">
              <Cog6ToothIcon class="w-6 h-6 text-white" />
            </div>
            <span class="text-xs bg-gray-600 text-white px-2 py-1 rounded-full">Custom</span>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Custom Report Builder</h3>
          <p class="text-sm text-gray-600 mb-4">Build your own custom report with specific metrics and dimensions</p>
          <div class="flex items-center justify-between text-xs text-gray-500">
            <span>Fully customizable</span>
            <span>Advanced</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Reports -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
          Recent Reports
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
          Your recently generated and scheduled reports
        </p>
      </div>
      <ul class="divide-y divide-gray-200">
        <li v-for="report in recentReports" :key="report.id" class="px-6 py-4 hover:bg-gray-50">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div :class="[
                  'w-10 h-10 rounded-lg flex items-center justify-center',
                  report.status === 'completed' ? 'bg-green-100' : 
                  report.status === 'processing' ? 'bg-yellow-100' : 'bg-gray-100'
                ]">
                  <DocumentTextIcon :class="[
                    'w-5 h-5',
                    report.status === 'completed' ? 'text-green-600' : 
                    report.status === 'processing' ? 'text-yellow-600' : 'text-gray-600'
                  ]" />
                </div>
              </div>
              <div class="ml-4">
                <div class="flex items-center">
                  <p class="text-sm font-medium text-gray-900">{{ report.name }}</p>
                  <span :class="[
                    'ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    report.status === 'completed' ? 'bg-green-100 text-green-800' :
                    report.status === 'processing' ? 'bg-yellow-100 text-yellow-800' :
                    'bg-gray-100 text-gray-800'
                  ]">
                    {{ report.status }}
                  </span>
                </div>
                <div class="flex items-center text-sm text-gray-500 space-x-4">
                  <span>{{ report.type }}</span>
                  <span>•</span>
                  <span>{{ report.period }}</span>
                  <span>•</span>
                  <span>{{ report.createdAt }}</span>
                </div>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <button
                v-if="report.status === 'completed'"
                @click="downloadReport(report.id)"
                class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <ArrowDownTrayIcon class="w-4 h-4 mr-1" />
                Download
              </button>
              <button
                @click="duplicateReport(report.id)"
                class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <DocumentDuplicateIcon class="w-4 h-4 mr-1" />
                Duplicate
              </button>
              <button
                @click="deleteReport(report.id)"
                class="inline-flex items-center px-3 py-1.5 border border-red-300 shadow-sm text-xs font-medium rounded text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
              >
                <TrashIcon class="w-4 h-4 mr-1" />
                Delete
              </button>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- Scheduled Reports -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">
              Scheduled Reports
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
              Automated reports that run on a schedule
            </p>
          </div>
          <button
            @click="showScheduleModal = true"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            Schedule Report
          </button>
        </div>
      </div>
      <ul class="divide-y divide-gray-200">
        <li v-for="schedule in scheduledReportsList" :key="schedule.id" class="px-6 py-4 hover:bg-gray-50">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                  <ClockIcon class="w-5 h-5 text-indigo-600" />
                </div>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-900">{{ schedule.name }}</p>
                <div class="flex items-center text-sm text-gray-500 space-x-4">
                  <span>{{ schedule.frequency }}</span>
                  <span>•</span>
                  <span>Next run: {{ schedule.nextRun }}</span>
                  <span>•</span>
                  <span>{{ schedule.recipients }} recipients</span>
                </div>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <button
                @click="toggleSchedule(schedule.id)"
                :class="[
                  'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
                  schedule.active ? 'bg-indigo-600' : 'bg-gray-200'
                ]"
              >
                <span
                  :class="[
                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                    schedule.active ? 'translate-x-5' : 'translate-x-0'
                  ]"
                />
              </button>
              <button
                @click="editSchedule(schedule.id)"
                class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50"
              >
                <PencilIcon class="w-4 h-4 mr-1" />
                Edit
              </button>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
    ArrowDownTrayIcon,
    ArrowPathIcon,
    ArrowsRightLeftIcon,
    ChartBarIcon,
    ClockIcon,
    Cog6ToothIcon,
    CurrencyDollarIcon,
    DocumentDuplicateIcon,
    DocumentTextIcon,
    GlobeAltIcon,
    PencilIcon,
    PlusIcon,
    TrashIcon,
    UsersIcon
} from '@heroicons/vue/24/outline'
import { onMounted, ref } from 'vue'

// Reactive data
const loading = ref(false)
const showCreateReport = ref(false)
const showCustomBuilder = ref(false)
const showScheduleModal = ref(false)

// Mock data
const totalReports = ref(47)
const scheduledReports = ref(8)
const monthlyDownloads = ref(156)
const dataPoints = ref(2847392)

const recentReports = ref([
  {
    id: 1,
    name: 'Q4 Performance Overview',
    type: 'Performance Report',
    period: 'Oct-Dec 2024',
    status: 'completed',
    createdAt: '2 hours ago'
  },
  {
    id: 2,
    name: 'GCC Market Analysis - KSA Focus',
    type: 'Regional Report',
    period: 'Last 30 days',
    status: 'completed',
    createdAt: '1 day ago'
  },
  {
    id: 3,
    name: 'Campaign ROI Analysis',
    type: 'Financial Report',
    period: 'December 2024',
    status: 'processing',
    createdAt: '3 hours ago'
  },
  {
    id: 4,
    name: 'WhatsApp Business Performance',
    type: 'Platform Report',
    period: 'Last 7 days',
    status: 'completed',
    createdAt: '2 days ago'
  },
  {
    id: 5,
    name: 'Audience Demographics - UAE',
    type: 'Audience Report',
    period: 'November 2024',
    status: 'completed',
    createdAt: '1 week ago'
  }
])

const scheduledReportsList = ref([
  {
    id: 1,
    name: 'Weekly Performance Summary',
    frequency: 'Weekly (Monday)',
    nextRun: 'Jan 20, 2025',
    recipients: 3,
    active: true
  },
  {
    id: 2,
    name: 'Monthly GCC Market Report',
    frequency: 'Monthly (1st)',
    nextRun: 'Feb 1, 2025',
    recipients: 5,
    active: true
  },
  {
    id: 3,
    name: 'Daily Campaign Alerts',
    frequency: 'Daily (9 AM)',
    nextRun: 'Tomorrow',
    recipients: 2,
    active: false
  }
])

// Methods
const refreshReports = async () => {
  loading.value = true
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    console.log('Reports refreshed')
  } catch (error) {
    console.error('Error refreshing reports:', error)
  } finally {
    loading.value = false
  }
}

const generateReport = (type: string) => {
  console.log(`Generating ${type} report`)
  // Add report generation logic here
}

const downloadReport = (reportId: number) => {
  console.log(`Downloading report ${reportId}`)
  // Add download logic here
}

const duplicateReport = (reportId: number) => {
  console.log(`Duplicating report ${reportId}`)
  // Add duplication logic here
}

const deleteReport = (reportId: number) => {
  console.log(`Deleting report ${reportId}`)
  // Add deletion logic here
}

const toggleSchedule = (scheduleId: number) => {
  const schedule = scheduledReportsList.value.find(s => s.id === scheduleId)
  if (schedule) {
    schedule.active = !schedule.active
  }
}

const editSchedule = (scheduleId: number) => {
  console.log(`Editing schedule ${scheduleId}`)
  // Add edit logic here
}

onMounted(() => {
  console.log('Enhanced Reports page loaded')
})
</script>
