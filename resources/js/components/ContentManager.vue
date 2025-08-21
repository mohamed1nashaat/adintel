<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          {{ $t('content.title') }}
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          {{ $t('content.subtitle') }}
        </p>
      </div>
      <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <PlusIcon class="h-4 w-4 mr-2" aria-hidden="true" />
          {{ $t('content.create_post') }}
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">{{ $t('content.status') }}</label>
          <select
            v-model="filters.status"
            @change="fetchPosts"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          >
            <option value="">{{ $t('common.all') }}</option>
            <option value="draft">{{ $t('content.status_draft') }}</option>
            <option value="pending_review">{{ $t('content.status_pending_review') }}</option>
            <option value="approved">{{ $t('content.status_approved') }}</option>
            <option value="scheduled">{{ $t('content.status_scheduled') }}</option>
            <option value="published">{{ $t('content.status_published') }}</option>
            <option value="rejected">{{ $t('content.status_rejected') }}</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">{{ $t('content.post_type') }}</label>
          <select
            v-model="filters.post_type"
            @change="fetchPosts"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          >
            <option value="">{{ $t('common.all') }}</option>
            <option value="text">{{ $t('content.type_text') }}</option>
            <option value="image">{{ $t('content.type_image') }}</option>
            <option value="video">{{ $t('content.type_video') }}</option>
            <option value="carousel">{{ $t('content.type_carousel') }}</option>
            <option value="story">{{ $t('content.type_story') }}</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">{{ $t('content.platform') }}</label>
          <select
            v-model="filters.platform"
            @change="fetchPosts"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          >
            <option value="">{{ $t('common.all') }}</option>
            <option value="facebook">Facebook</option>
            <option value="instagram">Instagram</option>
            <option value="twitter">Twitter</option>
            <option value="linkedin">LinkedIn</option>
            <option value="tiktok">TikTok</option>
            <option value="youtube">YouTube</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">{{ $t('common.search') }}</label>
          <input
            v-model="filters.search"
            @input="debouncedSearch"
            type="text"
            :placeholder="$t('content.search_placeholder')"
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
                  {{ $t(`content.status_${post.status}`) }}
                </span>
              </div>
              
              <p class="mt-1 text-sm text-gray-500 line-clamp-2">
                {{ post.content }}
              </p>
              
              <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                <span>{{ $t(`content.type_${post.post_type}`) }}</span>
                <span>•</span>
                <span>{{ formatDate(post.created_at) }}</span>
                <span>•</span>
                <span>{{ post.creator?.name }}</span>
                <span v-if="post.scheduled_at">•</span>
                <span v-if="post.scheduled_at" class="text-blue-600">
                  {{ $t('content.scheduled_for') }} {{ formatDate(post.scheduled_at) }}
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
                {{ $t('common.edit') }}
              </button>
              
              <button
                @click="duplicatePost(post)"
                class="text-gray-600 hover:text-gray-900 text-sm font-medium"
              >
                {{ $t('common.duplicate') }}
              </button>
              
              <button
                @click="deletePost(post)"
                v-if="post.status !== 'published'"
                class="text-red-600 hover:text-red-900 text-sm font-medium"
              >
                {{ $t('common.delete') }}
              </button>
            </div>
          </div>
        </li>
      </ul>
      
      <div v-else class="p-6 text-center">
        <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('content.no_posts') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ $t('content.no_posts_description') }}</p>
        <div class="mt-6">
          <button
            @click="showCreateModal = true"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
          >
            <PlusIcon class="h-4 w-4 mr-2" aria-hidden="true" />
            {{ $t('content.create_first_post') }}
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
          {{ $t('common.previous') }}
        </button>
        <button
          @click="nextPage"
          :disabled="pagination.current_page === pagination.last_page"
          class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
        >
          {{ $t('common.next') }}
        </button>
      </div>
      
      <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
          <p class="text-sm text-gray-700">
            {{ $t('common.showing') }}
            <span class="font-medium">{{ (pagination.current_page - 1) * pagination.per_page + 1 }}</span>
            {{ $t('common.to') }}
            <span class="font-medium">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</span>
            {{ $t('common.of') }}
            <span class="font-medium">{{ pagination.total }}</span>
            {{ $t('common.results') }}
          </p>
        </div>
        <div>
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
            <button
              @click="previousPage"
              :disabled="pagination.current_page === 1"
              class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
            >
              <ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
            </button>
            
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="goToPage(page)"
              :class="[
                page === pagination.current_page
                  ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                  : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
              ]"
            >
              {{ page }}
            </button>
            
            <button
              @click="nextPage"
              :disabled="pagination.current_page === pagination.last_page"
              class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
            >
              <ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
            </button>
          </nav>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <ContentPostModal
      v-if="showCreateModal"
      :post="selectedPost"
      @close="closeModal"
      @saved="handlePostSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ChevronLeftIcon, ChevronRightIcon, DocumentTextIcon, PlusIcon } from '@heroicons/vue/24/outline'
import { debounce } from 'lodash-es'
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import ContentPostModal from './ContentPostModal.vue'

const { t } = useI18n()

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

// Computed
const visiblePages = computed(() => {
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const pages = []
  
  const start = Math.max(1, current - 2)
  const end = Math.min(last, current + 2)
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
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
    
    posts.value = data.data
    pagination.value = data.meta
  } catch (error) {
    console.error('Error fetching posts:', error)
  } finally {
    loading.value = false
  }
}

const debouncedSearch = debounce(() => {
  fetchPosts(1)
}, 300)

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
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
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
  if (!confirm(t('content.confirm_delete'))) return
  
  try {
    const response = await fetch(`/api/content/posts/${post.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
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

const handlePostSaved = () => {
  closeModal()
  fetchPosts(pagination.value.current_page)
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

const goToPage = (page) => {
  fetchPosts(page)
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
