<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          Content Calendar
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Schedule and manage your social media posts with calendar view
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
        <select
          v-model="selectedView"
          @change="changeView"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <option value="month">Month View</option>
          <option value="week">Week View</option>
          <option value="day">Day View</option>
        </select>
      </div>
    </div>

    <!-- Calendar Navigation -->
    <div class="bg-white shadow rounded-lg p-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <button
            @click="previousPeriod"
            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <ChevronLeftIcon class="h-4 w-4" />
          </button>
          <h3 class="text-lg font-medium text-gray-900">
            {{ formatPeriodTitle() }}
          </h3>
          <button
            @click="nextPeriod"
            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <ChevronRightIcon class="h-4 w-4" />
          </button>
          <button
            @click="goToToday"
            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Today
          </button>
        </div>

        <!-- Platform Filter -->
        <div class="flex items-center space-x-2">
          <label class="text-sm font-medium text-gray-700">Platforms:</label>
          <div class="flex space-x-2">
            <button
              v-for="platform in availablePlatforms"
              :key="platform"
              @click="togglePlatformFilter(platform)"
              :class="[
                'inline-flex items-center px-2 py-1 rounded text-xs font-medium',
                platformFilters.includes(platform)
                  ? getPlatformActiveColor(platform)
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              ]"
            >
              {{ platform }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Calendar Grid -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
      <!-- Month View -->
      <div v-if="selectedView === 'month'" class="calendar-month">
        <!-- Days of Week Header -->
        <div class="grid grid-cols-7 bg-gray-50 border-b border-gray-200">
          <div
            v-for="day in daysOfWeek"
            :key="day"
            class="px-4 py-3 text-sm font-medium text-gray-700 text-center"
          >
            {{ day }}
          </div>
        </div>

        <!-- Calendar Days -->
        <div class="grid grid-cols-7 divide-x divide-gray-200">
          <div
            v-for="day in calendarDays"
            :key="day.date"
            :class="[
              'min-h-32 p-2 border-b border-gray-200',
              day.isCurrentMonth ? 'bg-white' : 'bg-gray-50',
              day.isToday ? 'bg-blue-50' : '',
            ]"
          >
            <div class="flex items-center justify-between mb-2">
              <span
                :class="[
                  'text-sm font-medium',
                  day.isCurrentMonth ? 'text-gray-900' : 'text-gray-400',
                  day.isToday ? 'text-blue-600' : '',
                ]"
              >
                {{ day.dayNumber }}
              </span>
              <button
                @click="schedulePostForDay(day.date)"
                class="text-gray-400 hover:text-indigo-600"
              >
                <PlusIcon class="h-4 w-4" />
              </button>
            </div>

            <!-- Posts for this day -->
            <div class="space-y-1">
              <div
                v-for="post in getPostsForDay(day.date)"
                :key="post.id"
                @click="viewPost(post)"
                :class="[
                  'text-xs p-1 rounded cursor-pointer truncate',
                  getStatusColor(post.status)
                ]"
                :title="post.content_post?.title || 'Untitled'"
              >
                <div class="flex items-center space-x-1">
                  <span class="truncate">{{ post.content_post?.title || 'Untitled' }}</span>
                  <div class="flex space-x-1">
                    <span
                      v-for="platform in post.platforms.slice(0, 2)"
                      :key="platform"
                      :class="['w-2 h-2 rounded-full', getPlatformDotColor(platform)]"
                    ></span>
                    <span v-if="post.platforms.length > 2" class="text-gray-500">+{{ post.platforms.length - 2 }}</span>
                  </div>
                </div>
                <div class="text-gray-500 mt-1">
                  {{ formatTime(post.scheduled_at) }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Week View -->
      <div v-else-if="selectedView === 'week'" class="calendar-week">
        <!-- Time slots header -->
        <div class="grid grid-cols-8 bg-gray-50 border-b border-gray-200">
          <div class="px-4 py-3 text-sm font-medium text-gray-700"></div>
          <div
            v-for="day in weekDays"
            :key="day.date"
            class="px-4 py-3 text-sm font-medium text-gray-700 text-center"
          >
            <div>{{ day.dayName }}</div>
            <div :class="day.isToday ? 'text-blue-600 font-bold' : 'text-gray-500'">
              {{ day.dayNumber }}
            </div>
          </div>
        </div>

        <!-- Time slots -->
        <div class="divide-y divide-gray-200">
          <div
            v-for="hour in timeSlots"
            :key="hour"
            class="grid grid-cols-8 min-h-16"
          >
            <div class="px-4 py-2 text-sm text-gray-500 border-r border-gray-200">
              {{ formatHour(hour) }}
            </div>
            <div
              v-for="day in weekDays"
              :key="`${day.date}-${hour}`"
              class="border-r border-gray-200 p-1 relative hover:bg-gray-50"
              @click="schedulePostForDateTime(day.date, hour)"
            >
              <!-- Posts for this time slot -->
              <div
                v-for="post in getPostsForDateTime(day.date, hour)"
                :key="post.id"
                @click.stop="viewPost(post)"
                :class="[
                  'text-xs p-1 rounded cursor-pointer mb-1',
                  getStatusColor(post.status)
                ]"
              >
                <div class="truncate font-medium">{{ post.content_post?.title || 'Untitled' }}</div>
                <div class="flex space-x-1 mt-1">
                  <span
                    v-for="platform in post.platforms"
                    :key="platform"
                    :class="['w-2 h-2 rounded-full', getPlatformDotColor(platform)]"
                  ></span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Day View -->
      <div v-else-if="selectedView === 'day'" class="calendar-day">
        <div class="p-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">
            {{ formatDayTitle(currentDate) }}
          </h3>
        </div>

        <div class="divide-y divide-gray-200">
          <div
            v-for="hour in timeSlots"
            :key="hour"
            class="flex min-h-20"
          >
            <div class="w-20 px-4 py-2 text-sm text-gray-500 border-r border-gray-200">
              {{ formatHour(hour) }}
            </div>
            <div
              class="flex-1 p-2 hover:bg-gray-50 cursor-pointer"
              @click="schedulePostForDateTime(currentDate, hour)"
            >
              <!-- Posts for this time slot -->
              <div class="space-y-2">
                <div
                  v-for="post in getPostsForDateTime(currentDate, hour)"
                  :key="post.id"
                  @click.stop="viewPost(post)"
                  :class="[
                    'p-3 rounded-lg cursor-pointer border-l-4',
                    getStatusBorderColor(post.status)
                  ]"
                >
                  <div class="flex items-center justify-between">
                    <h4 class="font-medium text-gray-900">{{ post.content_post?.title || 'Untitled' }}</h4>
                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusColor(post.status)]">
                      {{ getStatusLabel(post.status) }}
                    </span>
                  </div>
                  <p class="text-sm text-gray-600 mt-1 truncate">{{ post.content_post?.content }}</p>
                  <div class="flex items-center space-x-2 mt-2">
                    <span
                      v-for="platform in post.platforms"
                      :key="platform"
                      :class="['px-2 py-1 rounded text-xs font-medium', getPlatformColor(platform)]"
                    >
                      {{ platform }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Schedule Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showCreateModal = false"></div>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
              Quick Schedule Post
            </h3>
            
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Content Post</label>
                <select
                  v-model="quickPost.content_post_id"
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
                      v-model="quickPost.platforms"
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
                  v-model="quickPost.scheduled_at"
                  type="datetime-local"
                  :min="minDateTime"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Timezone</label>
                <select
                  v-model="quickPost.timezone"
                  class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                >
                  <option v-for="tz in timezones" :key="tz.value" :value="tz.value">
                    {{ tz.label }}
                  </option>
                </select>
              </div>
            </div>
          </div>
          
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              @click="createQuickPost"
              :disabled="!canCreateQuickPost"
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
  ChevronLeftIcon,
  ChevronRightIcon,
  PlusIcon,
} from '@heroicons/vue/24/outline'
import { computed, onMounted, ref, watch } from 'vue'

// Reactive data
const selectedView = ref('month')
const currentDate = ref(new Date())
const posts = ref([])
const contentPosts = ref([])
const showCreateModal = ref(false)
const platformFilters = ref([])

const quickPost = ref({
  content_post_id: '',
  platforms: [],
  scheduled_at: '',
  timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
})

const availablePlatforms = ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'youtube']
const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
const timeSlots = Array.from({ length: 24 }, (_, i) => i)

const timezones = [
  { value: 'UTC', label: 'UTC' },
  { value: 'America/New_York', label: 'Eastern Time' },
  { value: 'America/Chicago', label: 'Central Time' },
  { value: 'America/Denver', label: 'Mountain Time' },
  { value: 'America/Los_Angeles', label: 'Pacific Time' },
  { value: 'Europe/London', label: 'London' },
  { value: 'Europe/Paris', label: 'Paris' },
  { value: 'Asia/Tokyo', label: 'Tokyo' },
  { value: 'Asia/Dubai', label: 'Dubai' },
]

// Computed properties
const minDateTime = computed(() => {
  const now = new Date()
  now.setMinutes(now.getMinutes() + 30)
  return now.toISOString().slice(0, 16)
})

const canCreateQuickPost = computed(() => {
  return quickPost.value.content_post_id && 
         quickPost.value.platforms.length > 0 && 
         quickPost.value.scheduled_at
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
      dayNumber: date.getDate(),
      isCurrentMonth: date.getMonth() === month,
      isToday: date.toDateString() === today.toDateString(),
    })
  }
  
  return days
})

const weekDays = computed(() => {
  const startOfWeek = new Date(currentDate.value)
  startOfWeek.setDate(currentDate.value.getDate() - currentDate.value.getDay())
  
  const days = []
  const today = new Date()
  
  for (let i = 0; i < 7; i++) {
    const date = new Date(startOfWeek)
    date.setDate(startOfWeek.getDate() + i)
    
    days.push({
      date: date.toISOString().split('T')[0],
      dayName: daysOfWeek[i],
      dayNumber: date.getDate(),
      isToday: date.toDateString() === today.toDateString(),
    })
  }
  
  return days
})

// Methods
const fetchPosts = async () => {
  try {
    const startDate = getViewStartDate()
    const endDate = getViewEndDate()
    
    const params = new URLSearchParams({
      start_date: startDate.toISOString().split('T')[0],
      end_date: endDate.toISOString().split('T')[0],
    })
    
    if (platformFilters.value.length > 0) {
      platformFilters.value.forEach(platform => {
        params.append('platforms[]', platform)
      })
    }
    
    const response = await fetch(`/api/posts/calendar?${params}`)
    const data = await response.json()
    posts.value = data.data
  } catch (error) {
    console.error('Error fetching posts:', error)
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

const getViewStartDate = () => {
  if (selectedView.value === 'month') {
    const date = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth(), 1)
    date.setDate(date.getDate() - date.getDay())
    return date
  } else if (selectedView.value === 'week') {
    const date = new Date(currentDate.value)
    date.setDate(currentDate.value.getDate() - currentDate.value.getDay())
    return date
  } else {
    return new Date(currentDate.value)
  }
}

const getViewEndDate = () => {
  if (selectedView.value === 'month') {
    const date = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 0)
    date.setDate(date.getDate() + (6 - date.getDay()))
    return date
  } else if (selectedView.value === 'week') {
    const date = new Date(currentDate.value)
    date.setDate(currentDate.value.getDate() - currentDate.value.getDay() + 6)
    return date
  } else {
    return new Date(currentDate.value)
  }
}

const getPostsForDay = (dateString: string) => {
  return posts.value.filter(post => {
    const postDate = new Date(post.scheduled_at).toISOString().split('T')[0]
    return postDate === dateString && 
           (platformFilters.value.length === 0 || 
            post.platforms.some(p => platformFilters.value.includes(p)))
  })
}

const getPostsForDateTime = (dateString: string, hour: number) => {
  return posts.value.filter(post => {
    const postDate = new Date(post.scheduled_at)
    const postDateString = postDate.toISOString().split('T')[0]
    const postHour = postDate.getHours()
    
    return postDateString === dateString && 
           postHour === hour &&
           (platformFilters.value.length === 0 || 
            post.platforms.some(p => platformFilters.value.includes(p)))
  })
}

const formatPeriodTitle = () => {
  if (selectedView.value === 'month') {
    return currentDate.value.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
  } else if (selectedView.value === 'week') {
    const startOfWeek = new Date(currentDate.value)
    startOfWeek.setDate(currentDate.value.getDate() - currentDate.value.getDay())
    const endOfWeek = new Date(startOfWeek)
    endOfWeek.setDate(startOfWeek.getDate() + 6)
    
    return `${startOfWeek.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${endOfWeek.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`
  } else {
    return currentDate.value.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })
  }
}

const formatDayTitle = (date: Date) => {
  return date.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })
}

const formatTime = (dateString: string) => {
  return new Date(dateString).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
}

const formatHour = (hour: number) => {
  return new Date(0, 0, 0, hour).toLocaleTimeString('en-US', { hour: 'numeric' })
}

const previousPeriod = () => {
  if (selectedView.value === 'month') {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1)
  } else if (selectedView.value === 'week') {
    currentDate.value = new Date(currentDate.value.getTime() - 7 * 24 * 60 * 60 * 1000)
  } else {
    currentDate.value = new Date(currentDate.value.getTime() - 24 * 60 * 60 * 1000)
  }
}

const nextPeriod = () => {
  if (selectedView.value === 'month') {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1)
  } else if (selectedView.value === 'week') {
    currentDate.value = new Date(currentDate.value.getTime() + 7 * 24 * 60 * 60 * 1000)
  } else {
    currentDate.value = new Date(currentDate.value.getTime() + 24 * 60 * 60 * 1000)
  }
}

const goToToday = () => {
  currentDate.value = new Date()
}

const changeView = () => {
  fetchPosts()
}

const togglePlatformFilter = (platform: string) => {
  const index = platformFilters.value.indexOf(platform)
  if (index > -1) {
    platformFilters.value.splice(index, 1)
  } else {
    platformFilters.value.push(platform)
  }
  fetchPosts()
}

const schedulePostForDay = (dateString: string) => {
  const date = new Date(dateString + 'T12:00:00')
  quickPost.value.scheduled_at = date.toISOString().slice(0, 16)
  showCreateModal.value = true
}

const schedulePostForDateTime = (dateString: string, hour: number) => {
  const date = new Date(dateString + 'T00:00:00')
  date.setHours(hour)
  quickPost.value.scheduled_at = date.toISOString().slice(0, 16)
  showCreateModal.value = true
}

const createQuickPost = async () => {
  try {
    const response = await fetch('/api/posts', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify(quickPost.value),
    })
    
    if (response.ok) {
      showCreateModal.value = false
      quickPost.value = {
        content_post_id: '',
        platforms: [],
        scheduled_at: '',
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
      }
      fetchPosts()
    } else {
      const error = await response.json()
      alert('Error creating scheduled post: ' + (error.message || 'Unknown error'))
    }
  } catch (error) {
    console.error('Error creating scheduled post:', error)
    alert('Error creating scheduled post')
  }
}

const viewPost = (post: any) => {
  // Navigate to post detail view or open modal
  console.log('View post:', post)
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

const getStatusBorderColor = (status: string) => {
  const colors = {
    scheduled: 'border-blue-400 bg-blue-50',
    publishing: 'border-yellow-400 bg-yellow-50',
    published: 'border-green-400 bg-green-50',
    failed: 'border-red-400 bg-red-50',
    cancelled: 'border-gray-400 bg-gray-50',
  }
  return colors[status] || 'border-gray-400 bg-gray-50'
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
  return colors[platform as keyof typeof colors] || 'bg-gray-100 text-gray-800'
}

const getPlatformActiveColor = (platform: string) => {
  const colors = {
    facebook: 'bg-blue-600 text-white',
    instagram: 'bg-pink-600 text-white',
    twitter: 'bg-sky-600 text-white',
    linkedin: 'bg-indigo-600 text-white',
    tiktok: 'bg-gray-600 text-white',
    youtube: 'bg-red-600 text-white',
  }
  return colors[platform as keyof typeof colors] || 'bg-gray-600 text-white'
}

const getPlatformDotColor = (platform: string) => {
  const colors = {
    facebook: 'bg-blue-500',
    instagram: 'bg-pink-500',
    twitter: 'bg-sky-500',
    linkedin: 'bg-indigo-500',
    tiktok: 'bg-gray-500',
    youtube: 'bg-red-500',
  }
  return colors[platform as keyof typeof colors] || 'bg-gray-500'
}

// Watch for date changes
watch(currentDate, () => {
  fetchPosts()
})

watch(selectedView, () => {
  fetchPosts()
})

// Lifecycle
onMounted(() => {
  fetchPosts()
  fetchContentPosts()
})
</script>
