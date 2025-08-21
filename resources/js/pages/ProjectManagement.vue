<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          {{ $t('projects.title', 'Project Management') }}
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          {{ $t('projects.subtitle', 'Manage your marketing projects and campaigns') }}
        </p>
      </div>
      <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <PlusIcon class="h-4 w-4 mr-2" aria-hidden="true" />
          {{ $t('projects.create', 'New Project') }}
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Search projects..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select
            v-model="filters.status"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="archived">Archived</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
          <select
            v-model="filters.type"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">All Types</option>
            <option value="campaign">Campaign</option>
            <option value="brand">Brand</option>
            <option value="product">Product</option>
            <option value="event">Event</option>
            <option value="ongoing">Ongoing</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Region</label>
          <select
            v-model="filters.region"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">All Regions</option>
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

    <!-- Projects Grid -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      <p class="mt-2 text-sm text-gray-500">Loading projects...</p>
    </div>

    <div v-else-if="projects.length === 0" class="text-center py-12">
      <FolderIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">No projects</h3>
      <p class="mt-1 text-sm text-gray-500">Get started by creating a new project.</p>
      <div class="mt-6">
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
        >
          <PlusIcon class="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
          New Project
        </button>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="project in projects"
        :key="project.id"
        class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow cursor-pointer"
        @click="viewProject(project)"
      >
        <div class="p-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="h-10 w-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                  <FolderIcon class="h-6 w-6 text-indigo-600" />
                </div>
              </div>
              <div class="ml-4">
                <h3 class="text-lg font-medium text-gray-900 truncate">{{ project.name }}</h3>
                <p class="text-sm text-gray-500">{{ project.type }} • {{ project.region }}</p>
              </div>
            </div>
            <div class="flex-shrink-0">
              <span
                :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  project.status === 'active' ? 'bg-green-100 text-green-800' :
                  project.status === 'inactive' ? 'bg-yellow-100 text-yellow-800' :
                  'bg-gray-100 text-gray-800'
                ]"
              >
                {{ project.status }}
              </span>
            </div>
          </div>

          <div class="mt-4">
            <p class="text-sm text-gray-600 line-clamp-2">{{ project.description || 'No description' }}</p>
          </div>

          <!-- Progress Bar -->
          <div class="mt-4">
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-500">Progress</span>
              <span class="font-medium">{{ Math.round(project.progress_percentage || 0) }}%</span>
            </div>
            <div class="mt-1 w-full bg-gray-200 rounded-full h-2">
              <div
                class="bg-indigo-600 h-2 rounded-full transition-all duration-300"
                :style="{ width: `${project.progress_percentage || 0}%` }"
              ></div>
            </div>
          </div>

          <!-- Stats -->
          <div class="mt-4 grid grid-cols-3 gap-4 text-center">
            <div>
              <p class="text-lg font-semibold text-gray-900">{{ project.content_posts_count || 0 }}</p>
              <p class="text-xs text-gray-500">Posts</p>
            </div>
            <div>
              <p class="text-lg font-semibold text-gray-900">{{ project.leads_count || 0 }}</p>
              <p class="text-xs text-gray-500">Leads</p>
            </div>
            <div>
              <p class="text-lg font-semibold text-gray-900">{{ project.users_count || 0 }}</p>
              <p class="text-xs text-gray-500">Team</p>
            </div>
          </div>

          <!-- Budget Info -->
          <div v-if="project.budget" class="mt-4 pt-4 border-t border-gray-200">
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-500">Budget</span>
              <span class="font-medium">
                {{ formatCurrency(project.budget_remaining || 0, project.currency) }} remaining
              </span>
            </div>
          </div>

          <!-- Team Members -->
          <div class="mt-4 flex items-center">
            <div class="flex -space-x-2">
              <div
                v-for="(user, index) in project.users?.slice(0, 3)"
                :key="user.id"
                class="inline-block h-8 w-8 rounded-full ring-2 ring-white bg-gray-300 flex items-center justify-center text-xs font-medium text-gray-700"
              >
                {{ user.name?.charAt(0) || '?' }}
              </div>
              <div
                v-if="(project.users?.length || 0) > 3"
                class="inline-block h-8 w-8 rounded-full ring-2 ring-white bg-gray-100 flex items-center justify-center text-xs font-medium text-gray-500"
              >
                +{{ (project.users?.length || 0) - 3 }}
              </div>
            </div>
            <span class="ml-2 text-sm text-gray-500">{{ project.user_role }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.total > pagination.per_page" class="flex items-center justify-between">
      <div class="text-sm text-gray-700">
        Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} projects
      </div>
      <div class="flex space-x-2">
        <button
          @click="changePage(pagination.current_page - 1)"
          :disabled="pagination.current_page <= 1"
          class="px-3 py-2 text-sm border border-gray-300 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
        >
          Previous
        </button>
        <button
          @click="changePage(pagination.current_page + 1)"
          :disabled="pagination.current_page >= pagination.last_page"
          class="px-3 py-2 text-sm border border-gray-300 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
        >
          Next
        </button>
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

                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Start Date</label>
                    <input
                      v-model="newProject.start_date"
                      type="date"
                      class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">End Date</label>
                    <input
                      v-model="newProject.end_date"
                      type="date"
                      class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    />
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Budget</label>
                    <input
                      v-model="newProject.budget"
                      type="number"
                      step="0.01"
                      min="0"
                      class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="0.00"
                    />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Currency</label>
                    <select
                      v-model="newProject.currency"
                      class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    >
                      <option value="USD">USD</option>
                      <option value="SAR">SAR</option>
                      <option value="AED">AED</option>
                      <option value="KWD">KWD</option>
                      <option value="QAR">QAR</option>
                      <option value="BHD">BHD</option>
                      <option value="OMR">OMR</option>
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
import { FolderIcon, PlusIcon } from '@heroicons/vue/24/outline'
import { onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

interface Project {
  id: number
  name: string
  description?: string
  type: string
  region: string
  status: string
  progress_percentage?: number
  budget?: number
  budget_remaining?: number
  currency?: string
  content_posts_count?: number
  leads_count?: number
  users_count?: number
  user_role?: string
  users?: Array<{
    id: number
    name: string
  }>
}

interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

// State
const loading = ref(true)
const creating = ref(false)
const showCreateModal = ref(false)
const projects = ref<Project[]>([])
const pagination = ref<Pagination>({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0
})

const filters = reactive({
  search: '',
  status: '',
  type: '',
  region: ''
})

const newProject = reactive({
  name: '',
  description: '',
  type: 'campaign',
  region: 'GCC',
  start_date: '',
  end_date: '',
  budget: '',
  currency: 'USD'
})

// Methods
const fetchProjects = async (page = 1) => {
  try {
    loading.value = true
    const params = new URLSearchParams({
      page: page.toString(),
      per_page: pagination.value.per_page.toString(),
      ...Object.fromEntries(Object.entries(filters).filter(([_, v]) => v))
    })

    const token = localStorage.getItem('auth_token')
    const response = await fetch(`/api/projects?${params}`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      projects.value = data.data || []
      pagination.value = {
        current_page: data.current_page || 1,
        last_page: data.last_page || 1,
        per_page: data.per_page || 15,
        total: data.total || 0,
        from: data.from || 0,
        to: data.to || 0
      }
    } else if (response.status === 401) {
      // Handle authentication error - redirect to login or show demo data
      console.warn('Authentication required, showing demo data')
      loadDemoProjects()
    } else {
      console.error('Failed to fetch projects:', response.statusText)
      loadDemoProjects()
    }
  } catch (error) {
    console.error('Error fetching projects:', error)
    loadDemoProjects()
  } finally {
    loading.value = false
  }
}

const loadDemoProjects = () => {
  projects.value = [
    {
      id: 1,
      name: 'KSA E-commerce Campaign',
      description: 'Digital marketing campaign for e-commerce expansion in Saudi Arabia',
      type: 'campaign',
      region: 'KSA',
      status: 'active',
      progress_percentage: 75,
      budget: 50000,
      budget_remaining: 12500,
      currency: 'SAR',
      content_posts_count: 24,
      leads_count: 156,
      users_count: 5,
      user_role: 'Manager',
      users: [
        { id: 1, name: 'Ahmed Al-Rashid' },
        { id: 2, name: 'Sarah Johnson' },
        { id: 3, name: 'Mohammed Hassan' },
        { id: 4, name: 'Lisa Chen' },
        { id: 5, name: 'Omar Abdullah' }
      ]
    },
    {
      id: 2,
      name: 'UAE Brand Awareness',
      description: 'Multi-platform brand awareness campaign targeting UAE market',
      type: 'brand',
      region: 'UAE',
      status: 'active',
      progress_percentage: 45,
      budget: 75000,
      budget_remaining: 41250,
      currency: 'AED',
      content_posts_count: 18,
      leads_count: 89,
      users_count: 3,
      user_role: 'Owner',
      users: [
        { id: 6, name: 'Fatima Al-Zahra' },
        { id: 7, name: 'David Smith' },
        { id: 8, name: 'Aisha Mohammed' }
      ]
    },
    {
      id: 3,
      name: 'GCC Product Launch',
      description: 'Regional product launch campaign across all GCC countries',
      type: 'product',
      region: 'GCC',
      status: 'inactive',
      progress_percentage: 30,
      budget: 100000,
      budget_remaining: 70000,
      currency: 'USD',
      content_posts_count: 12,
      leads_count: 45,
      users_count: 7,
      user_role: 'Editor',
      users: [
        { id: 9, name: 'Khalid Al-Mansoori' },
        { id: 10, name: 'Jennifer Wilson' },
        { id: 11, name: 'Hassan Al-Thani' },
        { id: 12, name: 'Maria Garcia' }
      ]
    }
  ]
  
  pagination.value = {
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 3,
    from: 1,
    to: 3
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
      await fetchProjects()
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
    region: 'GCC',
    start_date: '',
    end_date: '',
    budget: '',
    currency: 'USD'
  })
}

const viewProject = (project: any) => {
  router.push(`/projects/${project.id}`)
}

const changePage = (page: number) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    fetchProjects(page)
  }
}

const formatCurrency = (amount: number, currency?: string) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency || 'USD'
  }).format(amount)
}

// Watchers
watch(filters, () => {
  fetchProjects(1)
}, { deep: true })

// Lifecycle
onMounted(() => {
  fetchProjects()
})
</script>
