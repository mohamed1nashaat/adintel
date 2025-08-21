<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
          <div class="flex items-center">
            <button @click="$router.go(-1)" class="mr-4 p-2 text-gray-400 hover:text-gray-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
            </button>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Content Management & Moderation</h1>
              <p class="text-gray-600 mt-1">AI-powered content workflows with approval processes</p>
            </div>
          </div>
          <div class="flex items-center space-x-3">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
              <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
              </svg>
              Feature Complete
            </span>
            <button @click="showCreateModal = true" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
              Create Content
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="flex items-center">
            <div class="bg-blue-100 rounded-lg p-3">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-2xl font-bold text-gray-900">{{ stats.totalPosts }}</p>
              <p class="text-gray-600 text-sm">Total Posts</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="flex items-center">
            <div class="bg-green-100 rounded-lg p-3">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-2xl font-bold text-gray-900">{{ stats.approvedPosts }}</p>
              <p class="text-gray-600 text-sm">Approved</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="flex items-center">
            <div class="bg-yellow-100 rounded-lg p-3">
              <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-2xl font-bold text-gray-900">{{ stats.pendingPosts }}</p>
              <p class="text-gray-600 text-sm">Pending Review</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="flex items-center">
            <div class="bg-purple-100 rounded-lg p-3">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-2xl font-bold text-gray-900">{{ stats.aiModerated }}</p>
              <p class="text-gray-600 text-sm">AI Moderated</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters and Search -->
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
          <div class="flex items-center space-x-4">
            <div class="relative">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search content..."
                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
              <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            
            <select v-model="selectedStatus" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              <option value="">All Status</option>
              <option value="draft">Draft</option>
              <option value="pending">Pending Review</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
              <option value="published">Published</option>
            </select>

            <select v-model="selectedPlatform" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              <option value="">All Platforms</option>
              <option value="facebook">Facebook</option>
              <option value="instagram">Instagram</option>
              <option value="twitter">Twitter</option>
              <option value="linkedin">LinkedIn</option>
              <option value="tiktok">TikTok</option>
            </select>
          </div>

          <div class="flex items-center space-x-2">
            <button @click="refreshData" class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
            </button>
            <button class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Content Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <ContentPostCard
          v-for="post in filteredPosts"
          :key="post.id"
          :post="post"
          @edit="editPost"
          @delete="deletePost"
          @moderate="moderatePost"
          @publish="publishPost"
        />
      </div>

      <!-- Empty State -->
      <div v-if="filteredPosts.length === 0" class="text-center py-12">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No content found</h3>
        <p class="text-gray-600 mb-4">Get started by creating your first content post.</p>
        <button @click="showCreateModal = true" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
          Create Content
        </button>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <ContentCreateModal
      v-if="showCreateModal"
      :post="selectedPost"
      @close="showCreateModal = false"
      @save="savePost"
    />

    <!-- Moderation Modal -->
    <ModerationModal
      v-if="showModerationModal"
      :post="selectedPost"
      @close="showModerationModal = false"
      @approve="approvePost"
      @reject="rejectPost"
    />
  </div>
</template>

<script setup lang="ts">
import ContentCreateModal from '@/components/ContentCreateModal.vue'
import ContentPostCard from '@/components/ContentPostCard.vue'
import ModerationModal from '@/components/ModerationModal.vue'
import { computed, onMounted, ref } from 'vue'

// Reactive data
const searchQuery = ref('')
const selectedStatus = ref('')
const selectedPlatform = ref('')
const showCreateModal = ref(false)
const showModerationModal = ref(false)
const selectedPost = ref(null)

const stats = ref({
  totalPosts: 156,
  approvedPosts: 142,
  pendingPosts: 8,
  aiModerated: 134
})

const posts = ref([
  {
    id: 1,
    title: 'Summer Campaign Launch',
    content: 'Exciting new summer collection is here! Check out our latest designs and get ready for the season.',
    platform: 'facebook',
    status: 'approved',
    author: 'John Doe',
    createdAt: '2024-01-15T10:30:00Z',
    scheduledAt: '2024-01-16T14:00:00Z',
    image: 'https://via.placeholder.com/400x300',
    aiScore: 95,
    engagement: {
      likes: 245,
      comments: 32,
      shares: 18
    }
  },
  {
    id: 2,
    title: 'Product Feature Highlight',
    content: 'Discover the innovative features that make our product stand out from the competition.',
    platform: 'linkedin',
    status: 'pending',
    author: 'Jane Smith',
    createdAt: '2024-01-15T09:15:00Z',
    scheduledAt: null,
    image: 'https://via.placeholder.com/400x300',
    aiScore: 88,
    engagement: {
      likes: 0,
      comments: 0,
      shares: 0
    }
  },
  {
    id: 3,
    title: 'Behind the Scenes',
    content: 'Take a look behind the scenes at our creative process and meet the team that makes it all happen.',
    platform: 'instagram',
    status: 'published',
    author: 'Mike Johnson',
    createdAt: '2024-01-14T16:45:00Z',
    scheduledAt: '2024-01-15T12:00:00Z',
    image: 'https://via.placeholder.com/400x300',
    aiScore: 92,
    engagement: {
      likes: 567,
      comments: 89,
      shares: 45
    }
  },
  {
    id: 4,
    title: 'Customer Success Story',
    content: 'Read how our solution helped transform this customer\'s business and achieve their goals.',
    platform: 'twitter',
    status: 'draft',
    author: 'Sarah Wilson',
    createdAt: '2024-01-15T11:20:00Z',
    scheduledAt: null,
    image: null,
    aiScore: 85,
    engagement: {
      likes: 0,
      comments: 0,
      shares: 0
    }
  },
  {
    id: 5,
    title: 'Industry Insights',
    content: 'Our latest research reveals key trends shaping the industry. Download the full report now.',
    platform: 'linkedin',
    status: 'approved',
    author: 'David Brown',
    createdAt: '2024-01-15T08:30:00Z',
    scheduledAt: '2024-01-17T10:00:00Z',
    image: 'https://via.placeholder.com/400x300',
    aiScore: 90,
    engagement: {
      likes: 123,
      comments: 15,
      shares: 28
    }
  },
  {
    id: 6,
    title: 'Event Announcement',
    content: 'Join us for our upcoming webinar where we\'ll discuss the future of digital marketing.',
    platform: 'facebook',
    status: 'rejected',
    author: 'Lisa Garcia',
    createdAt: '2024-01-15T07:45:00Z',
    scheduledAt: null,
    image: 'https://via.placeholder.com/400x300',
    aiScore: 72,
    engagement: {
      likes: 0,
      comments: 0,
      shares: 0
    }
  }
])

// Computed properties
const filteredPosts = computed(() => {
  let filtered = posts.value

  if (searchQuery.value) {
    filtered = filtered.filter(post =>
      post.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      post.content.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  }

  if (selectedStatus.value) {
    filtered = filtered.filter(post => post.status === selectedStatus.value)
  }

  if (selectedPlatform.value) {
    filtered = filtered.filter(post => post.platform === selectedPlatform.value)
  }

  return filtered
})

// Methods
const refreshData = () => {
  // Simulate API call
  console.log('Refreshing content data...')
}

const editPost = (post: any) => {
  selectedPost.value = post
  showCreateModal.value = true
}

const deletePost = (postId: number) => {
  if (confirm('Are you sure you want to delete this post?')) {
    posts.value = posts.value.filter(post => post.id !== postId)
  }
}

const moderatePost = (post: any) => {
  selectedPost.value = post
  showModerationModal.value = true
}

const publishPost = (postId: number) => {
  const post = posts.value.find(p => p.id === postId)
  if (post) {
    post.status = 'published'
  }
}

const savePost = (postData: any) => {
  if (postData.id) {
    // Update existing post
    const index = posts.value.findIndex(p => p.id === postData.id)
    if (index !== -1) {
      posts.value[index] = { ...posts.value[index], ...postData }
    }
  } else {
    // Create new post
    const newPost = {
      ...postData,
      id: Date.now(),
      createdAt: new Date().toISOString(),
      engagement: { likes: 0, comments: 0, shares: 0 }
    }
    posts.value.unshift(newPost)
  }
  showCreateModal.value = false
  selectedPost.value = null
}

const approvePost = (postId: number) => {
  const post = posts.value.find(p => p.id === postId)
  if (post) {
    post.status = 'approved'
  }
  showModerationModal.value = false
}

const rejectPost = (postId: number, reason: string) => {
  const post = posts.value.find(p => p.id === postId)
  if (post) {
    post.status = 'rejected'
    post.rejectionReason = reason
  }
  showModerationModal.value = false
}

onMounted(() => {
  // Initialize component
})
</script>

<style scoped>
.transition-colors {
  transition: color 0.2s ease;
}
</style>
