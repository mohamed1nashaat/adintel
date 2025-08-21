<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="$emit('close')">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white" @click.stop>
      <!-- Header -->
      <div class="flex items-center justify-between pb-4 border-b">
        <h3 class="text-lg font-semibold text-gray-900">Content Moderation</h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <!-- Content Preview -->
      <div class="mt-6">
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
          <h4 class="font-semibold text-gray-900 mb-2">{{ post?.title }}</h4>
          <p class="text-gray-700 mb-3">{{ post?.content }}</p>
          <div class="flex items-center space-x-4 text-sm text-gray-500">
            <span class="capitalize">{{ post?.platform }}</span>
            <span>by {{ post?.author }}</span>
            <span>{{ formatDate(post?.createdAt) }}</span>
          </div>
        </div>

        <!-- AI Analysis -->
        <div class="bg-blue-50 rounded-lg p-4 mb-6">
          <h4 class="text-sm font-medium text-blue-900 mb-3">AI Content Analysis</h4>
          
          <!-- AI Score -->
          <div class="flex items-center mb-3">
            <span class="text-sm text-blue-700 mr-3 w-20">Overall Score:</span>
            <div class="flex items-center flex-1">
              <div class="w-32 bg-blue-200 rounded-full h-2 mr-3">
                <div :class="aiScoreColor" class="h-2 rounded-full" :style="{ width: post?.aiScore + '%' }"></div>
              </div>
              <span class="text-sm font-medium text-blue-900">{{ post?.aiScore }}%</span>
            </div>
          </div>

          <!-- Analysis Details -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-blue-700">Sentiment:</span>
                <span class="font-medium text-blue-900">{{ aiAnalysis.sentiment }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-blue-700">Toxicity:</span>
                <span class="font-medium" :class="toxicityColor">{{ aiAnalysis.toxicity }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-blue-700">Engagement Potential:</span>
                <span class="font-medium text-blue-900">{{ aiAnalysis.engagement }}</span>
              </div>
            </div>
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-blue-700">Brand Safety:</span>
                <span class="font-medium" :class="brandSafetyColor">{{ aiAnalysis.brandSafety }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-blue-700">Compliance:</span>
                <span class="font-medium text-blue-900">{{ aiAnalysis.compliance }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-blue-700">Readability:</span>
                <span class="font-medium text-blue-900">{{ aiAnalysis.readability }}</span>
              </div>
            </div>
          </div>

          <!-- AI Recommendations -->
          <div class="bg-white rounded p-3">
            <h5 class="text-sm font-medium text-gray-900 mb-2">AI Recommendations:</h5>
            <ul class="text-sm text-gray-700 space-y-1">
              <li v-for="recommendation in aiAnalysis.recommendations" :key="recommendation" class="flex items-start">
                <svg class="w-4 h-4 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                {{ recommendation }}
              </li>
            </ul>
          </div>
        </div>

        <!-- Moderation Decision -->
        <div class="mb-6">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Moderation Decision</h4>
          <div class="space-y-3">
            <label class="flex items-center">
              <input v-model="decision" type="radio" value="approve" class="mr-3 text-green-600">
              <span class="text-green-700 font-medium">Approve Content</span>
              <span class="ml-2 text-sm text-gray-500">Content meets guidelines and can be published</span>
            </label>
            <label class="flex items-center">
              <input v-model="decision" type="radio" value="approve_with_changes" class="mr-3 text-yellow-600">
              <span class="text-yellow-700 font-medium">Approve with Suggested Changes</span>
              <span class="ml-2 text-sm text-gray-500">Content is good but could be improved</span>
            </label>
            <label class="flex items-center">
              <input v-model="decision" type="radio" value="reject" class="mr-3 text-red-600">
              <span class="text-red-700 font-medium">Reject Content</span>
              <span class="ml-2 text-sm text-gray-500">Content violates guidelines or needs major revision</span>
            </label>
          </div>
        </div>

        <!-- Rejection Reason (if rejecting) -->
        <div v-if="decision === 'reject'" class="mb-6">
          <label for="rejectionReason" class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason</label>
          <select v-model="rejectionReason" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent mb-3">
            <option value="">Select a reason...</option>
            <option value="inappropriate_content">Inappropriate Content</option>
            <option value="brand_guidelines">Violates Brand Guidelines</option>
            <option value="factual_errors">Contains Factual Errors</option>
            <option value="poor_quality">Poor Quality/Grammar</option>
            <option value="compliance_issues">Compliance Issues</option>
            <option value="other">Other</option>
          </select>
          <textarea
            v-model="rejectionNotes"
            rows="3"
            placeholder="Additional notes or specific feedback..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent"
          ></textarea>
        </div>

        <!-- Suggested Changes (if approving with changes) -->
        <div v-if="decision === 'approve_with_changes'" class="mb-6">
          <label for="suggestedChanges" class="block text-sm font-medium text-gray-700 mb-2">Suggested Changes</label>
          <textarea
            v-model="suggestedChanges"
            rows="3"
            placeholder="Describe the suggested improvements..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
          ></textarea>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-3 pt-4 border-t">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
          >
            Cancel
          </button>
          <button
            v-if="decision === 'approve' || decision === 'approve_with_changes'"
            @click="handleApprove"
            class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
          >
            Approve Content
          </button>
          <button
            v-if="decision === 'reject'"
            @click="handleReject"
            :disabled="!rejectionReason"
            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Reject Content
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';

const props = defineProps<{
  post: any
}>()

const emit = defineEmits<{
  close: []
  approve: [id: number]
  reject: [id: number, reason: string]
}>()

const decision = ref('')
const rejectionReason = ref('')
const rejectionNotes = ref('')
const suggestedChanges = ref('')

// Mock AI analysis data
const aiAnalysis = ref({
  sentiment: 'Positive',
  toxicity: 'Low',
  engagement: 'High',
  brandSafety: 'Safe',
  compliance: 'Compliant',
  readability: 'Good',
  recommendations: [
    'Consider adding a call-to-action to increase engagement',
    'The content tone aligns well with brand voice',
    'Optimal length for the selected platform'
  ]
})

const aiScoreColor = computed(() => {
  if (props.post?.aiScore >= 90) return 'bg-green-500'
  if (props.post?.aiScore >= 70) return 'bg-yellow-500'
  return 'bg-red-500'
})

const toxicityColor = computed(() => {
  switch (aiAnalysis.value.toxicity) {
    case 'Low': return 'text-green-600'
    case 'Medium': return 'text-yellow-600'
    case 'High': return 'text-red-600'
    default: return 'text-gray-600'
  }
})

const brandSafetyColor = computed(() => {
  switch (aiAnalysis.value.brandSafety) {
    case 'Safe': return 'text-green-600'
    case 'Caution': return 'text-yellow-600'
    case 'Unsafe': return 'text-red-600'
    default: return 'text-gray-600'
  }
})

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const handleApprove = () => {
  emit('approve', props.post.id)
}

const handleReject = () => {
  const reason = rejectionNotes.value || rejectionReason.value
  emit('reject', props.post.id, reason)
}
</script>
