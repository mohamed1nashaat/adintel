<template>
  <div class="space-y-6">
    <!-- Header with Real-time Status -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          AdIntel Dashboard
        </h2>
        <div class="mt-1 flex items-center space-x-4">
          <p class="text-sm text-gray-500">
            {{ formatDateRange(dateRange) }}
          </p>
          <div class="flex items-center">
            <div :class="[
              'h-2 w-2 rounded-full mr-2',
              isConnected ? 'bg-green-400' : 'bg-red-400'
            ]"></div>
            <span class="text-xs text-gray-500">
              {{ isConnected ? 'Live Data' : 'Demo Mode' }}
            </span>
          </div>
          <div class="text-xs text-gray-500">
            Last updated: {{ lastUpdated }}
          </div>
        </div>
      </div>
      <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
        <button
          @click="refreshData"
          :disabled="loading"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          <ArrowPathIcon 
            :class="['h-4 w-4 mr-2', loading ? 'animate-spin' : '']" 
            aria-hidden="true" 
          />
          {{ loading ? 'Refreshing...' : 'Refresh' }}
        </button>
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <PlusIcon class="h-4 w-4 mr-2" aria-hidden="true" />
          New Project
        </button>
      </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <FolderIcon class="h-6 w-6 text-gray-400" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Active Projects</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.activeProjects }}</dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
          <div class="text-sm">
            <router-link to="/projects" class="font-medium text-indigo-700 hover:text-indigo-900">
              View all projects
            </router-link>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <DocumentTextIcon class="h-6 w-6 text-gray-400" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Content Posts</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.contentPosts }}</dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
          <div class="text-sm">
            <router-link to="/content" class="font-medium text-indigo-700 hover:text-indigo-900">
              Manage content
            </router-link>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <UserGroupIcon class="h-6 w-6 text-gray-400" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Leads</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.totalLeads }}</dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
          <div class="text-sm">
            <router-link to="/leads" class="font-medium text-indigo-700 hover:text-indigo-900">
              View leads
            </router-link>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <CalendarIcon class="h-6 w-6 text-gray-400" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Scheduled Posts</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.scheduledPosts }}</dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
          <div class="text-sm">
            <router-link to="/scheduler" class="font-medium text-indigo-700 hover:text-indigo-900">
              View schedule
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Phase 2 Features Grid -->
    <div class="bg-white shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6">Phase 2 Features</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Content Management -->
          <div class="relative group bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-lg border border-blue-200 hover:shadow-md transition-shadow cursor-pointer" @click="navigateTo('/content')">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <DocumentTextIcon class="h-8 w-8 text-blue-600" />
              </div>
              <div class="ml-4">
                <h4 class="text-lg font-medium text-gray-900">Content Management</h4>
                <p class="text-sm text-gray-500">Create, moderate, and publish content</p>
              </div>
            </div>
            <div class="mt-4 flex items-center justify-between">
              <span class="text-sm font-medium text-blue-600">{{ stats.contentPosts }} posts</span>
              <ArrowRightIcon class="h-4 w-4 text-gray-400 group-hover:text-blue-600" />
            </div>
          </div>

          <!-- Lead Management -->
          <div class="relative group bg-gradient-to-r from-green-50 to-emerald-50 p-6 rounded-lg border border-green-200 hover:shadow-md transition-shadow cursor-pointer" @click="navigateTo('/leads')">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <UserGroupIcon class="h-8 w-8 text-green-600" />
              </div>
              <div class="ml-4">
                <h4 class="text-lg font-medium text-gray-900">Lead Management</h4>
                <p class="text-sm text-gray-500">Capture and manage leads</p>
              </div>
            </div>
            <div class="mt-4 flex items-center justify-between">
              <span class="text-sm font-medium text-green-600">{{ stats.totalLeads }} leads</span>
              <ArrowRightIcon class="h-4 w-4 text-gray-400 group-hover:text-green-600" />
            </div>
          </div>

          <!-- Post Scheduler -->
          <div class="relative group bg-gradient-to-r from-purple-50 to-violet-50 p-6 rounded-lg border border-purple-200 hover:shadow-md transition-shadow cursor-pointer" @click="navigateTo('/scheduler')">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <CalendarIcon class="h-8 w-8 text-purple-600" />
              </div>
              <div class="ml-4">
                <h4 class="text-lg font-medium text-gray-900">Post Scheduler</h4>
                <p class="text-sm text-gray-500">Schedule posts across platforms</p>
              </div>
            </div>
            <div class="mt-4 flex items-center justify-between">
              <span class="text-sm font-medium text-purple-600">{{ stats.scheduledPosts }} scheduled</span>
              <ArrowRightIcon class="h-4 w-4 text-gray-400 group-hover:text-purple-600" />
            </div>
          </div>

          <!-- Communications Hub -->
          <div class="relative group bg-gradient-to-r from-yellow-50 to-amber-50 p-6 rounded-lg border border-yellow-200 hover:shadow-md transition-shadow cursor-pointer" @click="navigateTo('/communications')">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <ChatBubbleLeftRightIcon class="h-8 w-8 text-yellow-600" />
              </div>
              <div class="ml-4">
                <h4 class="text-lg font-medium text-gray-900">Communications</h4>
                <p class="text-sm text-gray-500">Unified messaging hub</p>
              </div>
            </div>
            <div class="mt-4 flex items-center justify-between">
              <span class="text-sm font-medium text-yellow-600">{{ stats.unreadMessages }} unread</span>
              <ArrowRightIcon class="h-4 w-4 text-gray-400 group-hover:text-yellow-600" />
            </div>
          </div>

          <!-- GCC Benchmarks -->
          <div class="relative group bg-gradient-to-r from-red-50 to-rose-50 p-6 rounded-lg border border-red-200 hover:shadow-md transition-shadow cursor-pointer" @click="navigateTo('/benchmarks')">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <ChartBarIcon class="h-8 w-8 text-red-600" />
              </div>
              <div class="ml-4">
                <h4 class="text-lg font-medium text-gray-900">GCC Benchmarks</h4>
                <p class="text-sm text-gray-500">Regional performance analysis</p>
              </div>
            </div>
            <div class="mt-4 flex items-center justify-between">
              <span class="text-sm font-medium text-red-600">7 regions</span>
              <ArrowRightIcon class="h-4 w-4 text-gray-400 group-hover:text-red-600" />
            </div>
          </div>

          <!-- Project Management -->
          <div class="relative group bg-gradient-to-r from-indigo-50 to-blue-50 p-6 rounded-lg border border-indigo-200 hover:shadow-md transition-shadow cursor-pointer" @click="navigateTo('/projects')">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <FolderIcon class="h-8 w-8 text-indigo-600" />
              </div>
              <div class="ml-4">
                <h4 class="text-lg font-medium text-gray-900">Project Management</h4>
                <p class="text-sm text-gray-500">Manage marketing projects</p>
              </div>
            </div>
            <div class="mt-4 flex items-center justify-between">
              <span class="text-sm font-medium text-indigo-600">{{ stats.activeProjects }} active</span>
              <ArrowRightIcon class="h-4 w-4 text-gray-400 group-hover:text-indigo-600" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Social Platforms Status -->
    <div class="bg-white shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6">Connected Platforms</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
          <div v-for="platform in platforms" :key="platform.name" class="text-center">
            <div :class="[
              'w-12 h-12 mx-auto rounded-lg flex items-center justify-center mb-2',
              platform.connected ? 'bg-green-100' : 'bg-gray-100'
            ]">
              <component :is="platform.icon" :class="[
                'h-6 w-6',
                platform.connected ? 'text-green-600' : 'text-gray-400'
              ]" />
            </div>
            <p class="text-xs font-medium text-gray-900">{{ platform.name }}</p>
            <p :class="[
              'text-xs',
              platform.connected ? 'text-green-600' : 'text-gray-500'
            ]">
              {{ platform.connected ? 'Connected' : 'Not Connected' }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6">Recent Activity</h3>
        <div class="flow-root">
          <ul class="-mb-8">
            <li v-for="(activity, index) in recentActivity" :key="activity.id" class="relative pb-8">
              <div v-if="index !== recentActivity.length - 1" class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"></div>
              <div class="relative flex space-x-3">
                <div>
                  <span :class="[
                    'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white',
                    activity.type === 'project' ? 'bg-blue-500' :
                    activity.type === 'content' ? 'bg-green-500' :
                    activity.type === 'lead' ? 'bg-yellow-500' :
                    'bg-gray-500'
                  ]">
                    <component :is="getActivityIcon(activity.type)" class="h-4 w-4 text-white" />
                  </span>
                </div>
                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                  <div>
                    <p class="text-sm text-gray-500">{{ activity.description }}</p>
                  </div>
                  <div class="text-right text-sm whitespace-nowrap text-gray-500">
                    {{ formatTimeAgo(activity.created_at) }}
                  </div>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Create Project Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showCreateModal = false"></div>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <form @submit.prevent="createProject">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
              <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Create New Project</h3>
              
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Project Name *</label>
                  <input
                    v-model="newProject.name"
                    type="text"
                    required
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Enter project name"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Description</label>
                  <textarea
                    v-model="newProject.description"
                    rows="3"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Project description"
                  ></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Type *</label>
                    <select
                      v-model="newProject.type"
                      required
                      class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    >
                      <option value="campaign">Campaign</option>
                      <option value="brand">Brand</option>
                      <option value="product">Product</option>
                      <option value="event">Event</option>
                      <option value="ongoing">Ongoing</option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Region</label>
                    <select
                      v-model="newProject.region"
                      class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    >
                      <option value="GCC">GCC</option>
                      <option value="KSA">Saudi Arabia</option>
                      <option value="UAE">UAE</option>
                      <option value="Kuwait">Kuwait</option>
                      <option value="Qatar">Qatar</option>
                      <option value="Bahrain">Bahrain</option>
                      <option value="Oman">Oman</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
              <button
                type="submit"
                :disabled="creating"
                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
              >
                {{ creating ? 'Creating...' : 'Create Project' }}
              </button>
              <button
                type="button"
                @click="showCreateModal = false"
                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
    ArrowPathIcon,
    ArrowRightIcon,
    CalendarIcon,
    ChartBarIcon,
    ChatBubbleLeftRightIcon,
    DocumentTextIcon,
    FolderIcon,
    PlusIcon,
    UserGroupIcon
} from '@heroicons/vue/24/outline'
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// State
const loading = ref(false)
const creating = ref(false)
const showCreateModal = ref(false)
const isConnected = ref(false)
const lastUpdated = ref(new Date().toLocaleTimeString())

const dateRange = reactive({
  from: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  to: new Date().toISOString().split('T')[0]
})

const stats = reactive({
  activeProjects: 0,
  contentPosts: 0,
  totalLeads: 0,
  scheduledPosts: 0,
  unreadMessages: 0
})

const newProject = reactive({
  name: '',
  description: '',
  type: 'campaign',
  region: 'GCC'
})

const platforms = ref([
  { name: 'Facebook', icon: 'div', connected: true },
  { name: 'Instagram', icon: 'div', connected: true },
  { name: 'Twitter', icon: 'div', connected: false },
  { name: 'LinkedIn', icon: 'div', connected: true },
  { name: 'TikTok', icon: 'div', connected: false },
  { name: 'YouTube', icon: 'div', connected: false },
  { name: 'Snapchat', icon: 'div', connected: false },
  { name: 'WhatsApp', icon: 'div', connected: true }
])

const recentActivity = ref([
  {
    id: 1,
    type: 'project',
    description: 'New project "KSA E-commerce Campaign" created',
    created_at: new Date(Date.now() - 2 * 60 * 60 * 1000)
  },
  {
    id: 2,
    type: 'content',
    description: 'Content post published to Facebook and Instagram',
    created_at: new Date(Date.now() - 4 * 60 * 60 * 1000)
  },
  {
    id: 3,
    type: 'lead',
    description: '5 new leads captured from Google Ads campaign',
    created_at: new Date(Date.now() - 6 * 60 * 60 * 1000)
  }
])

// Methods
const refreshData = async () => {
  loading.value = true
  try {
    await Promise.all([
      fetchStats(),
      fetchRecentActivity()
    ])
    lastUpdated.value = new Date().toLocaleTimeString()
  } catch (error) {
    console.error('Error refreshing data:', error)
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    const token = localStorage.getItem('auth_token')
    const response = await fetch('/api/dashboard/stats', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    })

    if (response.ok) {
      const data = await response.json()
      Object.assign(stats, data)
      isConnected.value = true
    } else {
      // Fallback to demo data
      loadDemoStats()
      isConnected.value = false
    }
  } catch (error) {
    console.error('Error fetching stats:', error)
    loadDemoStats()
    isConnected.value = false
  }
}

const loadDemoStats = () => {
  Object.assign(stats, {
    activeProjects: 3,
    contentPosts: 24,
    totalLeads: 156,
    scheduledPosts: 8,
    unreadMessages: 12
  })
}

const fetchRecentActivity = async () => {
  try {
    const token = localStorage.getItem('auth_token')
    const response = await fetch('/api/dashboard/activity', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    })

    if (response.ok) {
      const data = await response.json()
      recentActivity.value = data.data || recentActivity.value
    }
  } catch (error) {
    console.error('Error fetching activity:', error)
    // Keep demo data
  }
}

const createProject = async () => {
  try {
    creating.value = true
    const token = localStorage.getItem('auth_token')
    const response = await fetch('/api/projects', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(newProject)
    })

    if (response.ok) {
      showCreateModal.value = false
      resetNewProject()
      await refreshData()
      alert('Project created successfully!')
    } else if (response.status === 401) {
      alert('Authentication required. Please log in.')
    } else {
      const error = await response.json().catch(() => ({ message: 'Failed to create project' }))
      alert(error.message || 'Failed to create project')
    }
  } catch (error) {
    console.error('Error creating project:', error)
    alert('Failed to create project. Please try again.')
  } finally {
    creating.value = false
  }
}

const resetNewProject = () => {
  Object.assign(newProject, {
    name: '',
    description: '',
    type: 'campaign',
    region: 'GCC'
  })
}

const navigateTo = (path: string) => {
  router.push(path)
}

const formatDateRange = (range: any) => {
  const from = new Date(range.from).toLocaleDateString()
  const to = new Date(range.to).toLocaleDateString()
  return `${from} - ${to}`
}

const formatTimeAgo = (date: Date) => {
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const hours = Math.floor(diff / (1000 * 60 * 60))
  
  if (hours < 1) {
    const minutes = Math.floor(diff / (1000 * 60))
    return `${minutes}m ago`
  } else if (hours < 24) {
    return `${hours}h ago`
  } else {
    const days = Math.floor(hours / 24)
    return `${days}d ago`
  }
}

const getActivityIcon = (type: string) => {
  switch (type) {
    case 'project': return FolderIcon
    case 'content': return DocumentTextIcon
    case 'lead': return UserGroupIcon
    default: return CalendarIcon
  }
}

// Auto-refresh every 5 minutes
setInterval(() => {
  if (!loading.value) {
    refreshData()
  }
}, 5 * 60 * 1000)

// Lifecycle
onMounted(() => {
  refreshData()
})
</script>
