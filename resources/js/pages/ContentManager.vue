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
        <button class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
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
              <span class="text-blue-600 font-semibold text-sm">📝</span>
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-500">Total Posts</p>
            <p class="text-2xl font-semibold text-gray-900">24</p>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 bg-yellow-100 rounded-full flex items-center justify-center">
              <span class="text-yellow-600 font-semibold text-sm">⏳</span>
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-500">Pending Review</p>
            <p class="text-2xl font-semibold text-gray-900">3</p>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
              <span class="text-green-600 font-semibold text-sm">✅</span>
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-500">Published</p>
            <p class="text-2xl font-semibold text-gray-900">21</p>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 bg-purple-100 rounded-full flex items-center justify-center">
              <span class="text-purple-600 font-semibold text-sm">📅</span>
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-500">Scheduled</p>
            <p class="text-2xl font-semibold text-gray-900">5</p>
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
              <button class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Edit</button>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const posts = ref([
  { id: 1, title: 'New Product Launch - KSA Market', platform: 'facebook', status: 'published', created_at: '2025-01-15' },
  { id: 2, title: 'Ramadan Campaign 2025', platform: 'instagram', status: 'scheduled', created_at: '2025-01-14' },
  { id: 3, title: 'Brand Awareness - UAE', platform: 'twitter', status: 'draft', created_at: '2025-01-13' },
  { id: 4, title: 'National Day Celebration', platform: 'linkedin', status: 'published', created_at: '2025-01-12' },
  { id: 5, title: 'TikTok Video Campaign', platform: 'tiktok', status: 'pending_review', created_at: '2025-01-11' }
])

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
</script>
