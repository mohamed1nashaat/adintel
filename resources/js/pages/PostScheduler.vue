<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          📅 Post Scheduler
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Schedule posts across all social platforms with calendar view
        </p>
      </div>
      <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
        <button class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
          Bulk Schedule
        </button>
        <button class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
          Schedule Post
        </button>
      </div>
    </div>

    <!-- View Toggle -->
    <div class="flex space-x-1 bg-gray-100 p-1 rounded-lg w-fit">
      <button 
        @click="currentView = 'calendar'"
        :class="currentView === 'calendar' ? 'bg-white shadow' : ''"
        class="px-3 py-2 text-sm font-medium rounded-md transition-colors"
      >
        📅 Calendar View
      </button>
      <button 
        @click="currentView = 'list'"
        :class="currentView === 'list' ? 'bg-white shadow' : ''"
        class="px-3 py-2 text-sm font-medium rounded-md transition-colors"
      >
        📋 List View
      </button>
    </div>

    <!-- Calendar View -->
    <div v-if="currentView === 'calendar'" class="bg-white shadow rounded-lg p-6">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-gray-900">{{ currentMonthYear }}</h3>
        <div class="flex space-x-2">
          <button @click="previousMonth" class="p-2 hover:bg-gray-100 rounded">
            ←
          </button>
          <button @click="nextMonth" class="p-2 hover:bg-gray-100 rounded">
            →
          </button>
        </div>
      </div>

      <!-- Calendar Grid -->
      <div class="grid grid-cols-7 gap-1">
        <!-- Day Headers -->
        <div v-for="day in dayHeaders" :key="day" class="p-2 text-center text-sm font-medium text-gray-500">
          {{ day }}
        </div>
        
        <!-- Calendar Days -->
        <div v-for="day in calendarDays" :key="day.date" 
             class="min-h-24 p-2 border border-gray-200 hover:bg-gray-50"
             :class="{ 'bg-gray-100': !day.isCurrentMonth, 'bg-blue-50': day.isToday }">
          <div class="text-sm font-medium text-gray-900 mb-1">{{ day.day }}</div>
          
          <!-- Scheduled Posts for this day -->
          <div class="space-y-1">
            <div v-for="post in getPostsForDay(day.date)" :key="post.id" 
                 class="text-xs p-1 rounded truncate cursor-pointer hover:opacity-80"
                 :class="getPlatformColor(post.platform)"
                 :title="post.title">
              <span class="mr-1">{{ getPlatformEmoji(post.platform) }}</span>
              {{ post.title }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- List View -->
    <div v-if="currentView === 'list'" class="bg-white shadow overflow-hidden sm:rounded-md">
      <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Scheduled Posts</h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">All your scheduled content</p>
      </div>
      <ul class="divide-y divide-gray-200">
        <li v-for="post in scheduledPosts" :key="post.id" class="px-4 py-4 hover:bg-gray-50">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <span class="text-2xl">{{ getPlatformEmoji(post.platform) }}</span>
              </div>
              <div class="ml-4">
                <div class="text-sm font-medium text-gray-900">{{ post.title }}</div>
                <div class="text-sm text-gray-500">
                  {{ post.platform }} • {{ formatDateTime(post.scheduled_at) }}
                </div>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <span :class="getStatusClass(post.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                {{ post.status }}
              </span>
              <button class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Edit</button>
              <button class="text-red-600 hover:text-red-900 text-sm font-medium">Delete</button>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
              <span class="text-blue-600 font-semibold text-sm">📅</span>
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-500">Scheduled Today</p>
            <p class="text-2xl font-semibold text-gray-900">{{ todayPosts }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
              <span class="text-green-600 font-semibold text-sm">📊</span>
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-500">This Week</p>
            <p class="text-2xl font-semibold text-gray-900">{{ weekPosts }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 bg-purple-100 rounded-full flex items-center justify-center">
              <span class="text-purple-600 font-semibold text-sm">🎯</span>
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-500">Best Time</p>
            <p class="text-2xl font-semibold text-gray-900">2:00 PM</p>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 bg-orange-100 rounded-full flex items-center justify-center">
              <span class="text-orange-600 font-semibold text-sm">📈</span>
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-500">Success Rate</p>
            <p class="text-2xl font-semibold text-gray-900">98.5%</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Optimal Posting Times -->
    <div class="bg-white shadow rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">🕐 Optimal Posting Times (GCC Timezone)</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="platform in optimalTimes" :key="platform.name" class="border rounded-lg p-4">
          <div class="flex items-center mb-2">
            <span class="text-xl mr-2">{{ platform.emoji }}</span>
            <span class="font-medium text-gray-900">{{ platform.name }}</span>
          </div>
          <div class="space-y-1">
            <div v-for="time in platform.times" :key="time" class="text-sm text-gray-600">
              {{ time }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

const currentView = ref('calendar')
const currentDate = ref(new Date())

const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

const scheduledPosts = ref([
  { id: 1, title: 'New Product Launch - KSA Market', platform: 'facebook', status: 'scheduled', scheduled_at: '2025-01-16T14:00:00' },
  { id: 2, title: 'Ramadan Campaign 2025', platform: 'instagram', status: 'scheduled', scheduled_at: '2025-01-17T10:30:00' },
  { id: 3, title: 'Brand Awareness - UAE', platform: 'twitter', status: 'scheduled', scheduled_at: '2025-01-18T16:15:00' },
  { id: 4, title: 'National Day Celebration', platform: 'linkedin', status: 'scheduled', scheduled_at: '2025-01-19T12:00:00' },
  { id: 5, title: 'TikTok Video Campaign', platform: 'tiktok', status: 'scheduled', scheduled_at: '2025-01-20T18:30:00' },
  { id: 6, title: 'YouTube Tutorial Series', platform: 'youtube', status: 'scheduled', scheduled_at: '2025-01-21T15:45:00' }
])

const optimalTimes = ref([
  { name: 'Facebook', emoji: '📘', times: ['9:00 AM', '1:00 PM', '7:00 PM'] },
  { name: 'Instagram', emoji: '📷', times: ['11:00 AM', '2:00 PM', '8:00 PM'] },
  { name: 'Twitter', emoji: '🐦', times: ['8:00 AM', '12:00 PM', '6:00 PM'] },
  { name: 'LinkedIn', emoji: '💼', times: ['10:00 AM', '3:00 PM', '5:00 PM'] }
])

const currentMonthYear = computed(() => {
  return currentDate.value.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
})

const calendarDays = computed(() => {
  const year = currentDate.value.getFullYear()
  const month = currentDate.value.getMonth()
  
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  const startDate = new Date(firstDay)
  startDate.setDate(startDate.getDate() - firstDay.getDay())
  
  const days = []
  const today = new Date()
  
  for (let i = 0; i < 42; i++) {
    const date = new Date(startDate)
    date.setDate(startDate.getDate() + i)
    
    days.push({
      date: date.toISOString().split('T')[0],
      day: date.getDate(),
      isCurrentMonth: date.getMonth() === month,
      isToday: date.toDateString() === today.toDateString()
    })
  }
  
  return days
})

const todayPosts = computed(() => {
  const today = new Date().toISOString().split('T')[0]
  return scheduledPosts.value.filter(post => post.scheduled_at.startsWith(today)).length
})

const weekPosts = computed(() => {
  const today = new Date()
  const weekFromNow = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000)
  return scheduledPosts.value.filter(post => {
    const postDate = new Date(post.scheduled_at)
    return postDate >= today && postDate <= weekFromNow
  }).length
})

const previousMonth = () => {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1)
}

const nextMonth = () => {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1)
}

const getPostsForDay = (date: string) => {
  return scheduledPosts.value.filter(post => post.scheduled_at.startsWith(date))
}

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

const getPlatformColor = (platform: string) => {
  const colorMap: { [key: string]: string } = {
    'facebook': 'bg-blue-100 text-blue-800',
    'instagram': 'bg-pink-100 text-pink-800',
    'twitter': 'bg-blue-100 text-blue-800',
    'linkedin': 'bg-blue-100 text-blue-800',
    'tiktok': 'bg-gray-100 text-gray-800',
    'youtube': 'bg-red-100 text-red-800',
    'snapchat': 'bg-yellow-100 text-yellow-800',
    'whatsapp': 'bg-green-100 text-green-800'
  }
  return colorMap[platform] || 'bg-gray-100 text-gray-800'
}

const getStatusClass = (status: string) => {
  const classMap: { [key: string]: string } = {
    'scheduled': 'bg-blue-100 text-blue-800',
    'published': 'bg-green-100 text-green-800',
    'failed': 'bg-red-100 text-red-800',
    'draft': 'bg-gray-100 text-gray-800'
  }
  return classMap[status] || 'bg-gray-100 text-gray-800'
}

const formatDateTime = (dateTime: string) => {
  return new Date(dateTime).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  })
}
</script>
