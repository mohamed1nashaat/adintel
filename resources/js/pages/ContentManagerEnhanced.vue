<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          📝 Content Manager
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Create, moderate, and publish content across all social platforms
        </p>
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
          Refresh
        </button>
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <PlusIcon class="h-4 w-4 mr-2" aria-hidden="true" />
          Create New Post
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
              <DocumentTextIcon class="h-5 w-5 text-blue-600" />
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-500">Total Posts</p>
            <p class="text-2xl font-semibold text-gray-900">{{ totalPosts }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 bg-yellow-100 rounded-full flex items-center justify-center">
              <ClockIcon class="h-5 w-5 text-yellow-600" />
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-500">Pending Review</p>
            <p class="text-2xl font-semibold text-gray-900">{{ pendingReview }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
              <CheckCircleIcon class="h-5 w-5 text-green-600" />
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-500">Published</p>
            <p class="text-2xl font-semibold text-gray-900">{{ published }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 bg-purple-100 rounded-full flex items-center justify-center">
              <CalendarIcon class="h-5 w-5 text-purple-600" />
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-500">Scheduled</p>
            <p class="text-2xl font-semibold text-gray-900">{{ scheduled }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Content List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Content</h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage your content across all platforms</p>
      </div>
      <ul class="divide-y divide-gray-200">
        <li v-for="post in posts" :key="post.id" class="px-4 py-4 hover:bg-gray-50">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <span class="text-2xl">{{ getPlatformEmoji(post.platform) }}</span>
              </div>
              <div class="ml-4">
                <div class="text-sm font-medium text-gray-900">{{ post.title }}</div>
                <div class="text-sm text-gray-500">{{ post.platform }} • {{ post.created_at }}</div>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <span :class="getStatusClass(post.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                {{ post.status }}
              </span>
              <button 
                @click="editPost(post.id)"
                class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
              >
                Edit
              </button>
              <button 
                @click="deletePost(post.id)"
                class="text-red-600 hover:text-red-900 text-sm font-medium"
              >
                Delete
              </button>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- Create Post Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="showCreateModal = false"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                <PlusIcon class="h-6 w-6 text-indigo-600" aria-hidden="true" />
              </div>
              <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                  Create New Post
                </h3>
                <div class="mt-4 space-y-4">
                  <div>
                    <label for="post-title" class="block text-sm font-medium text-gray-700">Title</label>
                    <input
                      v-model="newPost.title"
                      type="text"
                      id="post-title"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                      placeholder="Enter post title"
                    />
                  </div>
                  <div>
                    <label for="post-content" class="block text-sm font-medium text-gray-700">Content</label>
                    <textarea
                      v-model="newPost.content"
                      id="post-content"
                      rows="4"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                      placeholder="Write your post content..."
                    ></textarea>
                  </div>
                  <div>
                    <label for="post-platform" class="block text-sm font-medium text-gray-700">Platform</label>
                    <select
                      v-model="newPost.platform"
                      id="post-platform"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    >
                      <option value="">Select Platform</option>
                      <option value="facebook">Facebook</option>
                      <option value="instagram">Instagram</option>
                      <option value="twitter">Twitter</option>
                      <option value="linkedin">LinkedIn</option>
                      <option value="tiktok">TikTok</option>
                      <option value="youtube">YouTube</option>
                      <option value="snapchat">Snapchat</option>
                      <option value="whatsapp">WhatsApp</option>
                    </select>
                  </div>
                  <div>
                    <label for="post-status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select
                      v-model="newPost.status"
                      id="post-status"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    >
                      <option value="draft">Draft</option>
                      <option value="pending_review">Pending Review</option>
                      <option value="scheduled">Scheduled</option>
                      <option value="published">Published</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              @click="createPost"
              :disabled="creating"
              type="button"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
            >
              {{ creating ? 'Creating...' : 'Create Post' }}
            </button>
            <button
              @click="showCreateModal = false"
              type="button"
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
    CalendarIcon,
    CheckCircleIcon,
    ClockIcon,
    DocumentTextIcon,
    PlusIcon
} from '@heroicons/vue/24/outline'
import { computed, onMounted, ref } from 'vue'

// Reactive data
const loading = ref(false)
const creating = ref(false)
const showCreateModal = ref(false)
const newPost = ref({
  title: '',
  content: '',
  platform: '',
  status: 'draft'
})

const posts = ref([
  { id: 1, title: 'New Product Launch - KSA Market', platform: 'facebook', status: 'published', created_at: '2025-01-15' },
  { id: 2, title: 'Ramadan Campaign 2025', platform: 'instagram', status: 'scheduled', created_at: '2025-01-14' },
  { id: 3, title: 'Brand Awareness - UAE', platform: 'twitter', status: 'draft', created_at: '2025-01-13' },
  { id: 4, title: 'National Day Celebration', platform: 'linkedin', status: 'published', created_at: '2025-01-12' },
  { id: 5, title: 'TikTok Video Campaign', platform: 'tiktok', status: 'pending_review', created_at: '2025-01-11' }
])

// Computed properties
const totalPosts = computed(() => posts.value.length)
const pendingReview = computed(() => posts.value.filter(p => p.status === 'pending_review').length)
const published = computed(() => posts.value.filter(p => p.status === 'published').length)
const scheduled = computed(() => posts.value.filter(p => p.status === 'scheduled').length)

// Methods
const getPlatformEmoji = (platform: string) => {
  const emojiMap: { [key: string]: string } = {
    'facebook': '📘',
    'instagram': '📷',
    'twitter': '🐦',
    'linkedin': '💼',
    'tiktok': '🎵',
    'youtube': '📺',
    'snapchat': '👻',
    'whatsapp': '💬'
  }
  return emojiMap[platform] || '📝'
}

const getStatusClass = (status: string) => {
  const classMap: { [key: string]: string } = {
    'published': 'bg-green-100 text-green-800',
    'scheduled': 'bg-blue-100 text-blue-800',
    'draft': 'bg-gray-100 text-gray-800',
    'pending_review': 'bg-yellow-100 text-yellow-800'
  }
  return classMap[status] || 'bg-gray-100 text-gray-800'
}

const refreshData = async () => {
  loading.value = true
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    console.log('Content data refreshed')
  } catch (error) {
    console.error('Error refreshing data:', error)
  } finally {
    loading.value = false
  }
}

const createPost = async () => {
  if (!newPost.value.title || !newPost.value.content || !newPost.value.platform) {
    alert('Please fill in all required fields')
    return
  }

  creating.value = true
  try {
    console.log('Creating post:', newPost.value)
    
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Add new post to the list
    const newId = Math.max(...posts.value.map(p => p.id)) + 1
    posts.value.unshift({
      id: newId,
      title: newPost.value.title,
      platform: newPost.value.platform,
      status: newPost.value.status,
      created_at: new Date().toISOString().split('T')[0]
    })
    
    // Reset form
    newPost.value = {
      title: '',
      content: '',
      platform: '',
      status: 'draft'
    }
    
    // Close modal
    showCreateModal.value = false
    
    // Show success message
    alert('Post created successfully!')
    
  } catch (error) {
    console.error('Error creating post:', error)
    alert('Error creating post. Please try again.')
  } finally {
    creating.value = false
  }
}

const editPost = (postId: number) => {
  console.log('Editing post:', postId)
  // Add edit functionality here
}

const deletePost = (postId: number) => {
  if (confirm('Are you sure you want to delete this post?')) {
    posts.value = posts.value.filter(p => p.id !== postId)
    console.log('Post deleted:', postId)
  }
}

onMounted(() => {
  console.log('Enhanced Content Manager loaded with working Add New functionality')
})
</script>
