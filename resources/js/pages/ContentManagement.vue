<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <!-- Page header -->
      <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
          <h1 class="text-3xl font-bold leading-tight text-gray-900">
            Content Management
          </h1>
          <p class="mt-1 text-sm text-gray-500">
            Create, manage, and schedule your social media content across all platforms
          </p>
        </div>
      </div>

      <!-- Navigation tabs -->
      <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8">
          <button
            @click="activeTab = 'posts'"
            :class="[
              activeTab === 'posts'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
            ]"
          >
            Posts
            <span
              :class="[
                activeTab === 'posts'
                  ? 'bg-indigo-100 text-indigo-600'
                  : 'bg-gray-100 text-gray-900',
                'ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium'
              ]"
            >
              {{ postStats.total || 0 }}
            </span>
          </button>
          
          <button
            @click="activeTab = 'moderation'"
            :class="[
              activeTab === 'moderation'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
            ]"
          >
            Moderation Queue
            <span
              :class="[
                activeTab === 'moderation'
                  ? 'bg-indigo-100 text-indigo-600'
                  : moderationStats.pending_reviews > 0
                  ? 'bg-red-100 text-red-600'
                  : 'bg-gray-100 text-gray-900',
                'ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium'
              ]"
            >
              {{ moderationStats.pending_reviews || 0 }}
            </span>
          </button>
          
          <button
            @click="activeTab = 'templates'"
            :class="[
              activeTab === 'templates'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
            ]"
          >
            Templates
            <span
              :class="[
                activeTab === 'templates'
                  ? 'bg-indigo-100 text-indigo-600'
                  : 'bg-gray-100 text-gray-900',
                'ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium'
              ]"
            >
              {{ templateStats.total || 0 }}
            </span>
          </button>
          
          <button
            @click="activeTab = 'analytics'"
            :class="[
              activeTab === 'analytics'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
            ]"
          >
            Analytics
          </button>
        </nav>
      </div>

      <!-- Tab content -->
      <div class="bg-white shadow rounded-lg">
        <!-- Posts Tab -->
        <div v-if="activeTab === 'posts'" class="p-6">
          <ContentManagerSimple />
        </div>

        <!-- Moderation Tab -->
        <div v-else-if="activeTab === 'moderation'" class="p-6">
          <ModerationQueue />
        </div>

        <!-- Templates Tab -->
        <div v-else-if="activeTab === 'templates'" class="p-6">
          <TemplateManager />
        </div>

        <!-- Analytics Tab -->
        <div v-else-if="activeTab === 'analytics'" class="p-6">
          <ContentAnalytics />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import ContentManagerSimple from '@/components/ContentManagerSimple.vue'
import { onMounted, ref } from 'vue'

// Reactive data
const activeTab = ref('posts')
const postStats = ref({})
const moderationStats = ref({})
const templateStats = ref({})

// Placeholder components (these would be implemented separately)
const ModerationQueue = {
  template: `
    <div class="text-center py-12">
      <div class="mx-auto h-12 w-12 text-gray-400">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <h3 class="mt-2 text-sm font-medium text-gray-900">Moderation Queue</h3>
      <p class="mt-1 text-sm text-gray-500">Review and approve content before publishing</p>
      <div class="mt-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-yellow-50 p-4 rounded-lg">
            <div class="text-2xl font-bold text-yellow-600">{{ moderationStats.pending_reviews || 0 }}</div>
            <div class="text-sm text-yellow-800">Pending Reviews</div>
          </div>
          <div class="bg-red-50 p-4 rounded-lg">
            <div class="text-2xl font-bold text-red-600">{{ moderationStats.high_priority || 0 }}</div>
            <div class="text-sm text-red-800">High Priority</div>
          </div>
          <div class="bg-green-50 p-4 rounded-lg">
            <div class="text-2xl font-bold text-green-600">{{ moderationStats.approval_rate || 0 }}%</div>
            <div class="text-sm text-green-800">Approval Rate</div>
          </div>
        </div>
      </div>
    </div>
  `
}

const TemplateManager = {
  template: `
    <div class="text-center py-12">
      <div class="mx-auto h-12 w-12 text-gray-400">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
      <h3 class="mt-2 text-sm font-medium text-gray-900">Content Templates</h3>
      <p class="mt-1 text-sm text-gray-500">Create and manage reusable content templates</p>
      <div class="mt-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="bg-blue-50 p-4 rounded-lg">
            <div class="text-2xl font-bold text-blue-600">{{ templateStats.total || 0 }}</div>
            <div class="text-sm text-blue-800">Total Templates</div>
          </div>
          <div class="bg-green-50 p-4 rounded-lg">
            <div class="text-2xl font-bold text-green-600">{{ templateStats.active || 0 }}</div>
            <div class="text-sm text-green-800">Active Templates</div>
          </div>
          <div class="bg-purple-50 p-4 rounded-lg">
            <div class="text-2xl font-bold text-purple-600">{{ Object.keys(templateStats.by_category || {}).length }}</div>
            <div class="text-sm text-purple-800">Categories</div>
          </div>
          <div class="bg-orange-50 p-4 rounded-lg">
            <div class="text-2xl font-bold text-orange-600">{{ Object.keys(templateStats.by_type || {}).length }}</div>
            <div class="text-sm text-orange-800">Post Types</div>
          </div>
        </div>
      </div>
    </div>
  `
}

const ContentAnalytics = {
  template: `
    <div class="text-center py-12">
      <div class="mx-auto h-12 w-12 text-gray-400">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
      </div>
      <h3 class="mt-2 text-sm font-medium text-gray-900">Content Analytics</h3>
      <p class="mt-1 text-sm text-gray-500">Track performance and engagement across all platforms</p>
      <div class="mt-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="bg-indigo-50 p-4 rounded-lg">
            <div class="text-2xl font-bold text-indigo-600">{{ postStats.published || 0 }}</div>
            <div class="text-sm text-indigo-800">Published Posts</div>
          </div>
          <div class="bg-green-50 p-4 rounded-lg">
            <div class="text-2xl font-bold text-green-600">{{ postStats.scheduled || 0 }}</div>
            <div class="text-sm text-green-800">Scheduled Posts</div>
          </div>
          <div class="bg-yellow-50 p-4 rounded-lg">
            <div class="text-2xl font-bold text-yellow-600">{{ postStats.draft || 0 }}</div>
            <div class="text-sm text-yellow-800">Draft Posts</div>
          </div>
          <div class="bg-red-50 p-4 rounded-lg">
            <div class="text-2xl font-bold text-red-600">{{ postStats.rejected || 0 }}</div>
            <div class="text-sm text-red-800">Rejected Posts</div>
          </div>
        </div>
      </div>
    </div>
  `
}

// Methods
const fetchStats = async () => {
  try {
    // Fetch post statistics
    const postResponse = await fetch('/api/content/posts/statistics')
    if (postResponse.ok) {
      postStats.value = await postResponse.json()
    }

    // Fetch moderation statistics
    const moderationResponse = await fetch('/api/content/moderation/statistics')
    if (moderationResponse.ok) {
      moderationStats.value = await moderationResponse.json()
    }

    // Fetch template statistics
    const templateResponse = await fetch('/api/content/template-statistics')
    if (templateResponse.ok) {
      templateStats.value = await templateResponse.json()
    }
  } catch (error) {
    console.error('Error fetching statistics:', error)
  }
}

// Lifecycle
onMounted(() => {
  fetchStats()
})
</script>
