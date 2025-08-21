<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          Post Scheduler
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Schedule and manage your social media posts across all platforms
        </p>
      </div>
      <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <PlusIcon class="h-4 w-4 mr-2" aria-hidden="true" />
          Schedule Post
        </button>
        <button
          @click="refreshData"
          :disabled="loading"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          <ArrowPathIcon 
            :class="['h-4 w-4 mr-2', loading ? 'animate-spin' : '']" 
            aria-hidden="true" 
          />
          Refresh
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ClockIcon class="h-6 w-6 text-gray-400" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Scheduled</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.scheduled || 0 }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <CheckCircleIcon class="h-6 w-6 text-green-400" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Published</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.published || 0 }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ExclamationTriangleIcon class="h-6 w-6 text-yellow-400" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Pending Approval</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.pending_approval || 0 }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <XCircleIcon class="h-6 w-6 text-red-400" aria-hidden="true" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Failed</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.failed || 0 }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select
            v-model="filters.status"
            @change="applyFilters"
            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
          >
            <option value="">All Statuses</option>
            <option value="scheduled">Scheduled</option>
            <option value="publishing">Publishing</option>
            <option value="published">Published</option>
            <option value="failed">Failed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Platform</label>
          <select
            v-model="filters.platform"
            @change="applyFilters"
            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
          >
            <option value="">All Platforms</option>
            <option value="facebook">Facebook</option>
            <option value="instagram">Instagram</option>
            <option value="twitter">Twitter</option>
            <option value="linkedin">LinkedIn</option>
            <option value="tiktok">TikTok</option>
            <option value="youtube">YouTube</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Date From</label>
          <input
            v-model="filters.date_from"
            @change="applyFilters"
            type="date"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Date To</label>
          <input
            v-model="filters.date_to"
            @change="applyFilters"
            type="date"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
        </div>
      </div>

      <div class="mt-4">
        <input
          v-model="filters.search"
          @input="debounceSearch"
          type="text"
          placeholder="Search posts..."
          class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
        />
      </div>
    </div>

    <!-- Posts Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            Scheduled Posts
          </h3>
          <div class="flex space-x-2">
            <button
              @click="retryFailedPosts"
              :disabled="loading"
              class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
            >
              <ArrowPathIcon class="h-4 w-4 mr-1" />
              Retry Failed
            </button>
          </div>
        </div>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <div class="inline-flex items-center">
          <ArrowPathIcon class="animate-spin h-5 w-5 mr-3" />
          Loading posts...
        </div>
      </div>

      <div v-else-if="posts.length === 0" class="p-8 text-center text-gray-500">
        No scheduled posts found
      </div>

      <ul v-else class="divide-y divide-gray-200">
        <li v-for="post in posts" :key="post.id" class="px-4 py-4 hover:bg-gray-50">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="flex-shrink-0">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    getStatusColor(post.status)
                  ]"
                >
                  {{ getStatusLabel(post.status) }}
                </span>
              </div>
              
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">
                  {{ post.content_post?.title || 'Untitled' }}
                </p>
                <p class="text-sm text-gray-500 truncate">
                  {{ formatDate(post.scheduled_at) }}
                </p>
                <div class="flex items-center space-x-2 mt-1">
                  <span
                    v-for="platform in post.platforms"
                    :key="platform"
                    :class="[
                      'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                      getPlatformColor(platform)
                    ]"
                  >
                    {{ platform }}
                  </span>
                </div>
              </div>
            </div>

            <div class="flex items-center space-x-2">
              <button
                v-if="post.status === 'scheduled' && !post.preview_approved"
                @click="approvePost(post)"
                class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-green-700 bg-green-100 hover:bg-green-200"
              >
                Approve
              </button>
              
              <button
                v-if="post.status === 'scheduled'"
                @click="publishPost(post)"
                :disabled="post.preview_approved === false"
                class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 disabled:opacity-50"
              >
                Publish Now
              </button>
              
              <button
                v-if="post.status === 'failed'"
                @click="retryPost(post)"
                class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-yellow-700 bg-yellow-100 hover:bg-yellow-200"
              >
                Retry
              </button>
              
              <button
                @click="viewPost(post)"
                class="inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50"
              >
                View
              </button>
              
              <button
                v-if="!post.status === 'published'"
                @click="deletePost(post)"
                class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200"
              >
                Delete
              </button>
            </div>
          </div>
        </li>
      </ul>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
        <div class="flex items-center justify-between">
          <div class="flex-1 flex justify-between sm:hidden">
            <button
              @click="previousPage"
              :disabled="meta.current_page === 1"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
            >
              Previous
            </button>
            <button
              @click="nextPage"
              :disabled="meta.current_page === meta.last_page"
              class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
            >
              Next
            </button>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-700">
                Showing {{ (meta.current_page - 1) * meta.per_page + 1 }} to 
                {{ Math.min(meta.current_page * meta.per_page, meta.total) }} of 
                {{ meta.total }} results
              </p>
            </div>
            <div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                <button
                  @click="previousPage"
                  :disabled="meta.current_page === 1"
                  class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                >
                  Previous
                </button>
                <button
                  @click="nextPage"
                  :disabled="meta.current_page === meta.last_page"
                  class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                >
                  Next
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showCreateModal = false"></div>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
              Schedule New Post
            </h3>
            
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Content Post</label>
                <select
                  v-model="newPost.content_post_id"
                  class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                >
                  <option value="">Select a content post</option>
                  <option v-for="contentPost in contentPosts" :key="contentPost.id" :value="contentPost.id">
                    {{ contentPost.title }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Platforms</label>
                <div class="mt-2 space-y-2">
                  <label v-for="platform in availablePlatforms" :key="platform" class="flex items-center">
                    <input
                      v-model="newPost.platforms"
                      :value="platform"
                      type="checkbox"
                      class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                    />
                    <span class="ml-2 text-sm text-gray-700">{{ platform }}</span>
                  </label>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Scheduled Date & Time</label>
                <input
                  v-model="newPost.scheduled_at"
                  type="datetime-local"
                  :min="minDateTime"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
            </div>
          </div>
          
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              @click="createScheduledPost"
              :disabled="!canCreatePost"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
            >
              Schedule Post
            </button>
            <button
              @click="showCreateModal = false"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
    ArrowPathIcon,
    CheckCircleIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    PlusIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline'
import { computed, onMounted, ref } from 'vue'

// Reactive data
const loading = ref(false)
const posts = ref([])
const contentPosts = ref([])
const stats = ref({})
const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
})

const showCreateModal = ref(false)
const newPost = ref({
  content_post_id: '',
  platforms: [],
  scheduled_at: '',
})

const filters = ref({
  status: '',
  platform: '',
  date_from: '',
  date_to: '',
  search: '',
})

const availablePlatforms = ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'youtube']

// Computed properties
const minDateTime = computed(() => {
  const now = new Date()
  now.setMinutes(now.getMinutes() + 30) // Minimum 30 minutes from now
  return now.toISOString().slice(0, 16)
})

const canCreatePost = computed(() => {
  return newPost.value.content_post_id && 
         newPost.value.platforms.length > 0 && 
         newPost.value.scheduled_at
})

// Methods
const fetchPosts = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    
    Object.entries(filters.value).forEach(([key, value]) => {
      if (value) params.append(key, value)
    })
    
    params.append('page', meta.value.current_page.toString())
    
    const response = await fetch(`/api/posts?${params}`)
    const data = await response.json()
    
    posts.value = data.data
    meta.value = data.meta
  } catch (error) {
    console.error('Error fetching posts:', error)
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    const response = await fetch('/api/posts/stats')
    const data = await response.json()
    stats.value = data.data
  } catch (error) {
    console.error('Error fetching stats:', error)
  }
}

const fetchContentPosts = async () => {
  try {
    const response = await fetch('/api/content-posts?status=approved')
    const data = await response.json()
    contentPosts.value = data.data
  } catch (error) {
    console.error('Error fetching content posts:', error)
  }
}

const refreshData = async () => {
  await Promise.all([
    fetchPosts(),
    fetchStats(),
  ])
}

const applyFilters = () => {
  meta.value.current_page = 1
  fetchPosts()
}

let searchTimeout: NodeJS.Timeout
const debounceSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 500)
}

const createScheduledPost = async () => {
  try {
    const response = await fetch('/api/posts', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify(newPost.value),
    })
    
    if (response.ok) {
      showCreateModal.value = false
      newPost.value = {
        content_post_id: '',
        platforms: [],
        scheduled_at: '',
      }
      refreshData()
    } else {
      const error = await response.json()
      alert('Error creating scheduled post: ' + (error.message || 'Unknown error'))
    }
  } catch (error) {
    console.error('Error creating scheduled post:', error)
    alert('Error creating scheduled post')
  }
}

const approvePost = async (post: any) => {
  try {
    const response = await fetch(`/api/posts/${post.id}/approve`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    })
    
    if (response.ok) {
      refreshData()
    }
  } catch (error) {
    console.error('Error approving post:', error)
  }
}

const publishPost = async (post: any) => {
  try {
    const response = await fetch(`/api/posts/${post.id}/publish`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    })
    
    if (response.ok) {
      refreshData()
    }
  } catch (error) {
    console.error('Error publishing post:', error)
  }
}

const retryPost = async (post: any) => {
  try {
    const response = await fetch(`/api/posts/${post.id}/publish`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    })
    
    if (response.ok) {
      refreshData()
    }
  } catch (error) {
    console.error('Error retrying post:', error)
  }
}

const deletePost = async (post: any) => {
  if (!confirm('Are you sure you want to delete this scheduled post?')) {
    return
  }
  
  try {
    const response = await fetch(`/api/posts/${post.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    })
    
    if (response.ok) {
      refreshData()
    }
  } catch (error) {
    console.error('Error deleting post:', error)
  }
}

const retryFailedPosts = async () => {
  try {
    const response = await fetch('/api/posts/retry-failed', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    })
    
    if (response.ok) {
      const data = await response.json()
      alert(data.message)
      refreshData()
    }
  } catch (error) {
    console.error('Error retrying failed posts:', error)
  }
}

const viewPost = (post: any) => {
  // Navigate to post detail view
  window.location.href = `/posts/${post.id}`
}

const previousPage = () => {
  if (meta.value.current_page > 1) {
    meta.value.current_page--
    fetchPosts()
  }
}

const nextPage = () => {
  if (meta.value.current_page < meta.value.last_page) {
    meta.value.current_page++
    fetchPosts()
  }
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleString()
}

const getStatusColor = (status: string) => {
  const colors = {
    scheduled: 'bg-blue-100 text-blue-800',
    publishing: 'bg-yellow-100 text-yellow-800',
    published: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800',
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status: string) => {
  const labels = {
    scheduled: 'Scheduled',
    publishing: 'Publishing',
    published: 'Published',
    failed: 'Failed',
    cancelled: 'Cancelled',
  }
  return labels[status] || status
}

const getPlatformColor = (platform: string) => {
  const colors = {
    facebook: 'bg-blue-100 text-blue-800',
    instagram: 'bg-pink-100 text-pink-800',
    twitter: 'bg-sky-100 text-sky-800',
    linkedin: 'bg-indigo-100 text-indigo-800',
    tiktok: 'bg-gray-100 text-gray-800',
    youtube: 'bg-red-100 text-red-800',
  }
  return colors[platform] || 'bg-gray-100 text-gray-800'
}

// Lifecycle
onMounted(() => {
  refreshData()
  fetchContentPosts()
})
</script>
