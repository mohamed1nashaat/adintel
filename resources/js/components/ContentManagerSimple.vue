<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          Content Management
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Create, manage and schedule your social media content
        </p>
      </div>
      <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Create Post
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select
            v-model="filters.status"
            @change="fetchPosts"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          >
            <option value="">All</option>
            <option value="draft">Draft</option>
            <option value="pending_review">Pending Review</option>
            <option value="approved">Approved</option>
            <option value="scheduled">Scheduled</option>
            <option value="published">Published</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Post Type</label>
          <select
            v-model="filters.post_type"
            @change="fetchPosts"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          >
            <option value="">All</option>
            <option value="text">Text</option>
            <option value="image">Image</option>
            <option value="video">Video</option>
            <option value="carousel">Carousel</option>
            <option value="story">Story</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Platform</label>
          <select
            v-model="filters.platform"
            @change="fetchPosts"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          >
            <option value="">All</option>
            <option value="facebook">Facebook</option>
            <option value="instagram">Instagram</option>
            <option value="twitter">Twitter</option>
            <option value="linkedin">LinkedIn</option>
            <option value="tiktok">TikTok</option>
            <option value="youtube">YouTube</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Search</label>
          <input
            v-model="filters.search"
            @input="handleSearch"
            type="text"
            placeholder="Search posts..."
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>
      </div>
    </div>

    <!-- Content Grid -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <div v-if="loading" class="p-6">
        <div class="animate-pulse space-y-4">
          <div v-for="i in 5" :key="i" class="flex space-x-4">
            <div class="rounded-full bg-gray-200 h-10 w-10"></div>
            <div class="flex-1 space-y-2 py-1">
              <div class="h-4 bg-gray-200 rounded w-3/4"></div>
              <div class="h-4 bg-gray-200 rounded w-1/2"></div>
            </div>
          </div>
        </div>
      </div>
      
      <ul v-else-if="posts.length > 0" class="divide-y divide-gray-200">
        <li v-for="post in posts" :key="post.id" class="p-6 hover:bg-gray-50">
          <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0">
              <div class="flex items-center space-x-3">
                <h3 class="text-lg font-medium text-gray-900 truncate">
                  {{ post.title }}
                </h3>
                <span
                  :class="getStatusBadgeClass(post.status)"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ getStatusLabel(post.status) }}
                </span>
              </div>
              
              <p class="mt-1 text-sm text-gray-500 line-clamp-2">
                {{ post.content }}
              </p>
              
              <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                <span>{{ getTypeLabel(post.post_type) }}</span>
                <span>•</span>
                <span>{{ formatDate(post.created_at) }}</span>
                <span>•</span>
                <span>{{ post.creator?.name || 'Unknown' }}</span>
                <span v-if="post.scheduled_at">•</span>
                <span v-if="post.scheduled_at" class="text-blue-600">
                  Scheduled for {{ formatDate(post.scheduled_at) }}
                </span>
              </div>
              
              <div class="mt-2 flex flex-wrap gap-1">
                <span
                  v-for="platform in post.platforms"
                  :key="platform"
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                >
                  {{ platform }}
                </span>
              </div>
            </div>
            
            <div class="flex items-center space-x-2">
              <button
                @click="editPost(post)"
                v-if="post.status === 'draft' || post.status === 'rejected'"
                class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
              >
                Edit
              </button>
              
              <button
                @click="duplicatePost(post)"
                class="text-gray-600 hover:text-gray-900 text-sm font-medium"
              >
                Duplicate
              </button>
              
              <button
                @click="deletePost(post)"
                v-if="post.status !== 'published'"
                class="text-red-600 hover:text-red-900 text-sm font-medium"
              >
                Delete
              </button>
            </div>
          </div>
        </li>
      </ul>
      
      <div v-else class="p-6 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No posts found</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by creating your first post</p>
        <div class="mt-6">
          <button
            @click="showCreateModal = true"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
          >
            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create your first post
          </button>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.total > pagination.per_page" class="flex items-center justify-between">
      <div class="flex-1 flex justify-between sm:hidden">
        <button
          @click="previousPage"
          :disabled="pagination.current_page === 1"
          class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
        >
          Previous
        </button>
        <button
          @click="nextPage"
          :disabled="pagination.current_page === pagination.last_page"
          class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
        >
          Next
        </button>
      </div>
    </div>

    <!-- Simple Modal Placeholder -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
          <h3 class="text-lg font-medium text-gray-900">Create New Post</h3>
          <div class="mt-2 px-7 py-3">
            <p class="text-sm text-gray-500">
              Content creation modal would go here. This is a placeholder for the full implementation.
            </p>
          </div>
          <div class="items-center px-4 py-3">
            <button
              @click="closeModal"
              class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-600"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'

// Reactive data
const posts = ref([])
const loading = ref(false)
const showCreateModal = ref(false)
const selectedPost = ref(null)

const filters = ref({
  status: '',
  post_type: '',
  platform: '',
  search: ''
})

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0
})

// Methods
const fetchPosts = async (page = 1) => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: page.toString(),
      per_page: pagination.value.per_page.toString(),
      ...filters.value
    })
    
    const response = await fetch(`/api/content/posts?${params}`)
    const data = await response.json()
    
    posts.value = data.data || []
    pagination.value = data.meta || pagination.value
  } catch (error) {
    console.error('Error fetching posts:', error)
    posts.value = []
  } finally {
    loading.value = false
  }
}

let searchTimeout
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchPosts(1)
  }, 300)
}

const editPost = (post) => {
  selectedPost.value = post
  showCreateModal.value = true
}

const duplicatePost = async (post) => {
  try {
    const response = await fetch(`/api/content/posts/${post.id}/duplicate`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    if (response.ok) {
      fetchPosts(pagination.value.current_page)
    }
  } catch (error) {
    console.error('Error duplicating post:', error)
  }
}

const deletePost = async (post) => {
  if (!confirm('Are you sure you want to delete this post?')) return
  
  try {
    const response = await fetch(`/api/content/posts/${post.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    if (response.ok) {
      fetchPosts(pagination.value.current_page)
    }
  } catch (error) {
    console.error('Error deleting post:', error)
  }
}

const closeModal = () => {
  showCreateModal.value = false
  selectedPost.value = null
}

const previousPage = () => {
  if (pagination.value.current_page > 1) {
    fetchPosts(pagination.value.current_page - 1)
  }
}

const nextPage = () => {
  if (pagination.value.current_page < pagination.value.last_page) {
    fetchPosts(pagination.value.current_page + 1)
  }
}

const getStatusBadgeClass = (status) => {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    pending_review: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    scheduled: 'bg-blue-100 text-blue-800',
    published: 'bg-purple-100 text-purple-800',
    rejected: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status) => {
  const labels = {
    draft: 'Draft',
    pending_review: 'Pending Review',
    approved: 'Approved',
    scheduled: 'Scheduled',
    published: 'Published',
    rejected: 'Rejected'
  }
  return labels[status] || status
}

const getTypeLabel = (type) => {
  const labels = {
    text: 'Text',
    image: 'Image',
    video: 'Video',
    carousel: 'Carousel',
    story: 'Story'
  }
  return labels[type] || type
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}

// Lifecycle
onMounted(() => {
  fetchPosts()
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
