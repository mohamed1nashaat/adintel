<template>
  <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200">
    <!-- Image -->
    <div v-if="post.image" class="aspect-w-16 aspect-h-9">
      <img :src="post.image" :alt="post.title" class="w-full h-48 object-cover rounded-t-lg">
    </div>
    
    <!-- Content -->
    <div class="p-6">
      <!-- Header -->
      <div class="flex items-start justify-between mb-3">
        <div class="flex items-center space-x-2">
          <span :class="statusClass" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
            {{ statusLabel }}
          </span>
          <span :class="platformClass" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
            {{ platformLabel }}
          </span>
        </div>
        <div class="flex items-center space-x-1">
          <button @click="$emit('edit', post)" class="p-1 text-gray-400 hover:text-gray-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
          </button>
          <button @click="$emit('delete', post.id)" class="p-1 text-gray-400 hover:text-red-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
          </button>
        </div>
      </div>

      <!-- Title and Content -->
      <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ post.title }}</h3>
      <p class="text-gray-600 text-sm mb-4 line-clamp-3">{{ post.content }}</p>

      <!-- AI Score -->
      <div class="flex items-center mb-4">
        <span class="text-sm text-gray-500 mr-2">AI Score:</span>
        <div class="flex items-center">
          <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
            <div :class="aiScoreColor" class="h-2 rounded-full transition-all duration-300" :style="{ width: post.aiScore + '%' }"></div>
          </div>
          <span class="text-sm font-medium" :class="aiScoreTextColor">{{ post.aiScore }}%</span>
        </div>
      </div>

      <!-- Engagement Stats -->
      <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
        <div class="flex items-center space-x-4">
          <span class="flex items-center">
            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
            </svg>
            {{ post.engagement.likes }}
          </span>
          <span class="flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
            {{ post.engagement.comments }}
          </span>
          <span class="flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
            </svg>
            {{ post.engagement.shares }}
          </span>
        </div>
        <span class="text-xs">{{ formatDate(post.createdAt) }}</span>
      </div>

      <!-- Author and Actions -->
      <div class="flex items-center justify-between">
        <span class="text-sm text-gray-500">by {{ post.author }}</span>
        <div class="flex items-center space-x-2">
          <button
            v-if="post.status === 'pending'"
            @click="$emit('moderate', post)"
            class="px-3 py-1 text-xs font-medium text-yellow-700 bg-yellow-100 rounded-full hover:bg-yellow-200 transition-colors"
          >
            Review
          </button>
          <button
            v-if="post.status === 'approved'"
            @click="$emit('publish', post.id)"
            class="px-3 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-full hover:bg-green-200 transition-colors"
          >
            Publish
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  post: {
    id: number
    title: string
    content: string
    platform: string
    status: string
    author: string
    createdAt: string
    scheduledAt?: string
    image?: string
    aiScore: number
    engagement: {
      likes: number
      comments: number
      shares: number
    }
  }
}>()

defineEmits<{
  edit: [post: any]
  delete: [id: number]
  moderate: [post: any]
  publish: [id: number]
}>()

const statusClass = computed(() => {
  switch (props.post.status) {
    case 'draft':
      return 'bg-gray-100 text-gray-800'
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    case 'approved':
      return 'bg-green-100 text-green-800'
    case 'rejected':
      return 'bg-red-100 text-red-800'
    case 'published':
      return 'bg-blue-100 text-blue-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
})

const statusLabel = computed(() => {
  return props.post.status.charAt(0).toUpperCase() + props.post.status.slice(1)
})

const platformClass = computed(() => {
  switch (props.post.platform) {
    case 'facebook':
      return 'bg-blue-100 text-blue-800'
    case 'instagram':
      return 'bg-pink-100 text-pink-800'
    case 'twitter':
      return 'bg-sky-100 text-sky-800'
    case 'linkedin':
      return 'bg-indigo-100 text-indigo-800'
    case 'tiktok':
      return 'bg-purple-100 text-purple-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
})

const platformLabel = computed(() => {
  return props.post.platform.charAt(0).toUpperCase() + props.post.platform.slice(1)
})

const aiScoreColor = computed(() => {
  if (props.post.aiScore >= 90) return 'bg-green-500'
  if (props.post.aiScore >= 70) return 'bg-yellow-500'
  return 'bg-red-500'
})

const aiScoreTextColor = computed(() => {
  if (props.post.aiScore >= 90) return 'text-green-600'
  if (props.post.aiScore >= 70) return 'text-yellow-600'
  return 'text-red-600'
})

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<style scoped>
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.transition-shadow {
  transition: box-shadow 0.2s ease;
}

.transition-colors {
  transition: background-color 0.2s ease, color 0.2s ease;
}

.transition-all {
  transition: all 0.3s ease;
}
</style>
