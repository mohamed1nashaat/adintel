<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="$emit('close')">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white" @click.stop>
      <!-- Header -->
      <div class="flex items-center justify-between pb-4 border-b">
        <h3 class="text-lg font-semibold text-gray-900">
          {{ post ? 'Edit Content' : 'Create New Content' }}
        </h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="mt-6">
        <!-- Title -->
        <div class="mb-4">
          <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title</label>
          <input
            id="title"
            v-model="formData.title"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Enter content title..."
          >
        </div>

        <!-- Content -->
        <div class="mb-4">
          <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Content</label>
          <textarea
            id="content"
            v-model="formData.content"
            rows="4"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Write your content here..."
          ></textarea>
        </div>

        <!-- Platform and Status -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label for="platform" class="block text-sm font-medium text-gray-700 mb-2">Platform</label>
            <select
              id="platform"
              v-model="formData.platform"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Select Platform</option>
              <option value="facebook">Facebook</option>
              <option value="instagram">Instagram</option>
              <option value="twitter">Twitter</option>
              <option value="linkedin">LinkedIn</option>
              <option value="tiktok">TikTok</option>
            </select>
          </div>

          <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select
              id="status"
              v-model="formData.status"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="draft">Draft</option>
              <option value="pending">Pending Review</option>
              <option value="approved">Approved</option>
            </select>
          </div>
        </div>

        <!-- Image Upload -->
        <div class="mb-4">
          <label for="image" class="block text-sm font-medium text-gray-700 mb-2">Image</label>
          <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
            <div class="space-y-1 text-center">
              <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
              <div class="flex text-sm text-gray-600">
                <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                  <span>Upload a file</span>
                  <input id="file-upload" name="file-upload" type="file" class="sr-only" accept="image/*" @change="handleImageUpload">
                </label>
                <p class="pl-1">or drag and drop</p>
              </div>
              <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
            </div>
          </div>
          <div v-if="formData.image" class="mt-2">
            <img :src="formData.image" alt="Preview" class="h-20 w-20 object-cover rounded">
          </div>
        </div>

        <!-- Scheduled Date -->
        <div class="mb-6">
          <label for="scheduledAt" class="block text-sm font-medium text-gray-700 mb-2">Schedule for later (optional)</label>
          <input
            id="scheduledAt"
            v-model="formData.scheduledAt"
            type="datetime-local"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
        </div>

        <!-- AI Analysis -->
        <div v-if="aiAnalysis" class="mb-6 p-4 bg-blue-50 rounded-lg">
          <h4 class="text-sm font-medium text-blue-900 mb-2">AI Content Analysis</h4>
          <div class="flex items-center mb-2">
            <span class="text-sm text-blue-700 mr-2">Score:</span>
            <div class="flex items-center">
              <div class="w-20 bg-blue-200 rounded-full h-2 mr-2">
                <div :class="aiScoreColor" class="h-2 rounded-full" :style="{ width: aiAnalysis.score + '%' }"></div>
              </div>
              <span class="text-sm font-medium text-blue-900">{{ aiAnalysis.score }}%</span>
            </div>
          </div>
          <p class="text-sm text-blue-700">{{ aiAnalysis.feedback }}</p>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-3 pt-4 border-t">
          <button
            type="button"
            @click="analyzeContent"
            :disabled="!formData.content"
            class="px-4 py-2 text-sm font-medium text-blue-600 bg-blue-100 rounded-md hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Analyze with AI
          </button>
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            {{ post ? 'Update' : 'Create' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{
  post?: any
}>()

const emit = defineEmits<{
  close: []
  save: [data: any]
}>()

const formData = ref({
  title: '',
  content: '',
  platform: '',
  status: 'draft',
  image: '',
  scheduledAt: '',
  author: 'Current User'
})

const aiAnalysis = ref<{
  score: number
  feedback: string
} | null>(null)

const aiScoreColor = computed(() => {
  if (!aiAnalysis.value) return 'bg-gray-400'
  if (aiAnalysis.value.score >= 90) return 'bg-green-500'
  if (aiAnalysis.value.score >= 70) return 'bg-yellow-500'
  return 'bg-red-500'
})

const handleImageUpload = (event: Event) => {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (file) {
    const reader = new FileReader()
    reader.onload = (e) => {
      formData.value.image = e.target?.result as string
    }
    reader.readAsDataURL(file)
  }
}

const analyzeContent = async () => {
  if (!formData.value.content) return

  // Simulate AI analysis
  const score = Math.floor(Math.random() * 30) + 70 // Random score between 70-100
  const feedbacks = [
    'Great content! The tone is engaging and the message is clear.',
    'Consider adding more emotional appeal to increase engagement.',
    'The content is informative but could benefit from a stronger call-to-action.',
    'Excellent use of keywords and hashtags for better reach.',
    'The content length is optimal for the selected platform.'
  ]

  aiAnalysis.value = {
    score,
    feedback: feedbacks[Math.floor(Math.random() * feedbacks.length)]
  }
}

const handleSubmit = () => {
  const data = {
    ...formData.value,
    aiScore: aiAnalysis.value?.score || Math.floor(Math.random() * 30) + 70
  }

  if (props.post) {
    data.id = props.post.id
  }

  emit('save', data)
}

onMounted(() => {
  if (props.post) {
    formData.value = {
      title: props.post.title,
      content: props.post.content,
      platform: props.post.platform,
      status: props.post.status,
      image: props.post.image || '',
      scheduledAt: props.post.scheduledAt || '',
      author: props.post.author
    }
  }
})
</script>
