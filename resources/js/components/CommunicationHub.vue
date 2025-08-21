<template>
  <div class="h-full flex bg-gray-50">
    <!-- Sidebar - Conversations List -->
    <div class="w-1/3 bg-white border-r border-gray-200 flex flex-col">
      <!-- Header -->
      <div class="p-4 border-b border-gray-200">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">{{ $t('communication.conversations') }}</h2>
          <div class="flex items-center space-x-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
              {{ unreadCount }}
            </span>
            <button
              @click="refreshConversations"
              :disabled="loading"
              class="p-1 text-gray-400 hover:text-gray-600"
            >
              <ArrowPathIcon :class="['h-4 w-4', loading ? 'animate-spin' : '']" />
            </button>
          </div>
        </div>

        <!-- Filters -->
        <div class="space-y-3">
          <div class="flex space-x-2">
            <select
              v-model="filters.platform"
              @change="applyFilters"
              class="flex-1 text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">{{ $t('communication.allPlatforms') }}</option>
              <option value="whatsapp">WhatsApp</option>
              <option value="facebook">Facebook</option>
              <option value="instagram">Instagram</option>
              <option value="twitter">Twitter</option>
              <option value="telegram">Telegram</option>
            </select>
            <select
              v-model="filters.status"
              @change="applyFilters"
              class="flex-1 text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">{{ $t('communication.allStatuses') }}</option>
              <option value="open">{{ $t('communication.open') }}</option>
              <option value="pending">{{ $t('communication.pending') }}</option>
              <option value="resolved">{{ $t('communication.resolved') }}</option>
              <option value="closed">{{ $t('communication.closed') }}</option>
            </select>
          </div>
          
          <div class="flex space-x-2">
            <select
              v-model="filters.priority"
              @change="applyFilters"
              class="flex-1 text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">{{ $t('communication.allPriorities') }}</option>
              <option value="urgent">{{ $t('communication.urgent') }}</option>
              <option value="high">{{ $t('communication.high') }}</option>
              <option value="medium">{{ $t('communication.medium') }}</option>
              <option value="low">{{ $t('communication.low') }}</option>
            </select>
            <select
              v-model="filters.country"
              @change="applyFilters"
              class="flex-1 text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">{{ $t('communication.allCountries') }}</option>
              <option value="SA">🇸🇦 {{ $t('countries.SA') }}</option>
              <option value="AE">🇦🇪 {{ $t('countries.AE') }}</option>
              <option value="KW">🇰🇼 {{ $t('countries.KW') }}</option>
              <option value="QA">🇶🇦 {{ $t('countries.QA') }}</option>
              <option value="BH">🇧🇭 {{ $t('countries.BH') }}</option>
              <option value="OM">🇴🇲 {{ $t('countries.OM') }}</option>
            </select>
          </div>

          <div class="relative">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
            <input
              v-model="searchQuery"
              @input="debounceSearch"
              type="text"
              :placeholder="$t('communication.searchPlaceholder')"
              class="w-full pl-10 pr-4 py-2 text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>
        </div>
      </div>

      <!-- Conversations List -->
      <div class="flex-1 overflow-y-auto">
        <div v-if="loading && conversations.length === 0" class="p-4 text-center text-gray-500">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto"></div>
          <p class="mt-2">{{ $t('communication.loading') }}</p>
        </div>

        <div v-else-if="conversations.length === 0" class="p-4 text-center text-gray-500">
          <ChatBubbleLeftRightIcon class="h-12 w-12 mx-auto text-gray-300" />
          <p class="mt-2">{{ $t('communication.noConversations') }}</p>
        </div>

        <div v-else class="divide-y divide-gray-200">
          <div
            v-for="conversation in conversations"
            :key="conversation.id"
            @click="selectConversation(conversation)"
            :class="[
              'p-4 cursor-pointer hover:bg-gray-50 transition-colors',
              selectedConversation?.id === conversation.id ? 'bg-indigo-50 border-r-2 border-indigo-500' : '',
              !conversation.is_read ? 'bg-blue-50' : ''
            ]"
          >
            <div class="flex items-start space-x-3">
              <!-- Platform Icon -->
              <div class="flex-shrink-0">
                <div :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-medium',
                  getPlatformColor(conversation.platform)
                ]">
                  {{ getPlatformIcon(conversation.platform) }}
                </div>
              </div>

              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <p class="text-sm font-medium text-gray-900 truncate">
                      {{ conversation.customer_name }}
                    </p>
                    <span v-if="conversation.country_code" class="text-xs">
                      {{ getCountryFlag(conversation.country_code) }}
                    </span>
                    <span v-if="conversation.language === 'ar'" class="text-xs">🇸🇦</span>
                  </div>
                  <div class="flex items-center space-x-1">
                    <span
                      :class="[
                        'inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium',
                        `bg-${conversation.priority_color}-100 text-${conversation.priority_color}-800`
                      ]"
                    >
                      {{ conversation.priority_label }}
                    </span>
                    <span
                      :class="[
                        'inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium',
                        `bg-${conversation.status_color}-100 text-${conversation.status_color}-800`
                      ]"
                    >
                      {{ conversation.status_label }}
                    </span>
                  </div>
                </div>

                <div class="mt-1">
                  <p class="text-sm text-gray-600 truncate" :class="{ 'font-medium': !conversation.is_read }">
                    {{ conversation.last_message?.content || $t('communication.noMessages') }}
                  </p>
                </div>

                <div class="mt-2 flex items-center justify-between">
                  <div class="flex items-center space-x-2 text-xs text-gray-500">
                    <span>{{ formatTime(conversation.last_message_at) }}</span>
                    <span v-if="conversation.unread_count > 0" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                      {{ conversation.unread_count }}
                    </span>
                  </div>
                  
                  <div class="flex items-center space-x-1">
                    <span v-if="conversation.requires_urgent_response" class="text-red-500" title="Urgent Response Required">
                      <ExclamationTriangleIcon class="h-4 w-4" />
                    </span>
                    <span v-if="conversation.assigned_agent" class="text-green-500" :title="`Assigned to ${conversation.assigned_agent.name}`">
                      <UserIcon class="h-4 w-4" />
                    </span>
                  </div>
                </div>

                <!-- SLA Status -->
                <div v-if="conversation.sla_status.status === 'breached'" class="mt-1">
                  <div class="flex items-center space-x-1 text-xs text-red-600">
                    <ClockIcon class="h-3 w-3" />
                    <span>{{ $t('communication.slaBreached') }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Chat Area -->
    <div class="flex-1 flex flex-col">
      <div v-if="!selectedConversation" class="flex-1 flex items-center justify-center bg-gray-50">
        <div class="text-center">
          <ChatBubbleLeftRightIcon class="h-16 w-16 mx-auto text-gray-300" />
          <h3 class="mt-4 text-lg font-medium text-gray-900">{{ $t('communication.selectConversation') }}</h3>
          <p class="mt-2 text-gray-500">{{ $t('communication.selectConversationDesc') }}</p>
        </div>
      </div>

      <div v-else class="flex-1 flex flex-col">
        <!-- Chat Header -->
        <div class="bg-white border-b border-gray-200 px-6 py-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div :class="[
                'w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-medium',
                getPlatformColor(selectedConversation.platform)
              ]">
                {{ getPlatformIcon(selectedConversation.platform) }}
              </div>
              
              <div>
                <div class="flex items-center space-x-2">
                  <h3 class="text-lg font-medium text-gray-900">
                    {{ selectedConversation.customer_name }}
                  </h3>
                  <span v-if="selectedConversation.country_code" class="text-sm">
                    {{ getCountryFlag(selectedConversation.country_code) }}
                    {{ selectedConversation.country_label }}
                  </span>
                </div>
                <div class="flex items-center space-x-4 text-sm text-gray-500">
                  <span v-if="selectedConversation.customer_phone">
                    📱 {{ selectedConversation.customer_phone }}
                  </span>
                  <span v-if="selectedConversation.customer_email">
                    ✉️ {{ selectedConversation.customer_email }}
                  </span>
                  <span>
                    🌐 {{ selectedConversation.language_label }}
                  </span>
                </div>
              </div>
            </div>

            <div class="flex items-center space-x-2">
              <!-- Quick Actions -->
              <select
                v-model="selectedConversation.status"
                @change="updateConversationStatus"
                class="text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="open">{{ $t('communication.open') }}</option>
                <option value="pending">{{ $t('communication.pending') }}</option>
                <option value="resolved">{{ $t('communication.resolved') }}</option>
                <option value="closed">{{ $t('communication.closed') }}</option>
              </select>

              <select
                v-model="selectedConversation.priority"
                @change="updateConversationPriority"
                class="text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="low">{{ $t('communication.low') }}</option>
                <option value="medium">{{ $t('communication.medium') }}</option>
                <option value="high">{{ $t('communication.high') }}</option>
                <option value="urgent">{{ $t('communication.urgent') }}</option>
              </select>

              <button
                @click="markAsRead"
                v-if="!selectedConversation.is_read"
                class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200"
              >
                {{ $t('communication.markRead') }}
              </button>
            </div>
          </div>

          <!-- Conversation Stats -->
          <div class="mt-3 flex items-center space-x-6 text-xs text-gray-500">
            <span>{{ $t('communication.messages') }}: {{ selectedConversation.message_count }}</span>
            <span v-if="selectedConversation.response_time">
              {{ $t('communication.responseTime') }}: {{ selectedConversation.response_time }}m
            </span>
            <span v-if="selectedConversation.sla_status">
              {{ $t('communication.sla') }}: 
              <span :class="selectedConversation.sla_status.status === 'breached' ? 'text-red-600' : 'text-green-600'">
                {{ selectedConversation.sla_status.status === 'breached' ? $t('communication.breached') : $t('communication.onTime') }}
              </span>
            </span>
          </div>
        </div>

        <!-- Messages Area -->
        <div class="flex-1 overflow-y-auto p-6 space-y-4" ref="messagesContainer">
          <div
            v-for="message in messages"
            :key="message.id"
            :class="[
              'flex',
              message.direction === 'outbound' ? 'justify-end' : 'justify-start'
            ]"
          >
            <div :class="[
              'max-w-xs lg:max-w-md px-4 py-2 rounded-lg',
              message.direction === 'outbound' 
                ? 'bg-indigo-600 text-white' 
                : 'bg-gray-200 text-gray-900'
            ]">
              <div class="flex items-start space-x-2">
                <div class="flex-1">
                  <!-- Message Content -->
                  <div class="text-sm">
                    {{ message.content }}
                  </div>
                  
                  <!-- Translation (if available) -->
                  <div v-if="message.translated_content && message.translated_content !== message.content" 
                       class="mt-1 text-xs opacity-75 italic border-t border-gray-300 pt-1">
                    {{ message.translated_content }}
                  </div>

                  <!-- Message Meta -->
                  <div class="mt-1 flex items-center justify-between text-xs opacity-75">
                    <span>{{ message.formatted_time }}</span>
                    <div class="flex items-center space-x-1">
                      <span v-if="message.sentiment" :title="`Sentiment: ${message.sentiment}`">
                        {{ message.sentiment_icon }}
                      </span>
                      <span v-if="message.direction === 'outbound'">
                        {{ message.delivery_icon }}
                      </span>
                    </div>
                  </div>

                  <!-- Intents -->
                  <div v-if="message.detected_intents && message.detected_intents.length > 0" class="mt-1">
                    <div class="flex flex-wrap gap-1">
                      <span
                        v-for="intent in message.detected_intents"
                        :key="intent"
                        class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"
                      >
                        {{ intent }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Typing Indicator -->
          <div v-if="isTyping" class="flex justify-start">
            <div class="bg-gray-200 rounded-lg px-4 py-2">
              <div class="flex space-x-1">
                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Message Input -->
        <div class="bg-white border-t border-gray-200 px-6 py-4">
          <div class="flex items-end space-x-4">
            <!-- Quick Templates -->
            <div class="flex-shrink-0">
              <select
                v-model="selectedTemplate"
                @change="useTemplate"
                class="text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="">{{ $t('communication.templates') }}</option>
                <option
                  v-for="template in templates"
                  :key="template.name"
                  :value="template"
                >
                  {{ template.display_name }}
                </option>
              </select>
            </div>

            <!-- Message Input -->
            <div class="flex-1">
              <textarea
                v-model="newMessage"
                @keydown.enter.exact.prevent="sendMessage"
                @keydown.shift.enter="newMessage += '\n'"
                :placeholder="selectedConversation.language === 'ar' ? 'اكتب رسالتك هنا...' : 'Type your message...'"
                rows="2"
                class="w-full border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                :class="{ 'text-right': selectedConversation.language === 'ar' }"
              ></textarea>
            </div>

            <!-- Send Button -->
            <div class="flex-shrink-0">
              <button
                @click="sendMessage"
                :disabled="!newMessage.trim() || sending"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
              >
                <PaperAirplaneIcon class="h-4 w-4" />
              </button>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="mt-2 flex items-center space-x-2 text-xs">
            <span class="text-gray-500">{{ $t('communication.quickActions') }}:</span>
            <button
              @click="insertQuickReply('شكراً لتواصلك معنا')"
              class="text-indigo-600 hover:text-indigo-800"
            >
              {{ $t('communication.thankYou') }}
            </button>
            <button
              @click="insertQuickReply('سنتواصل معك قريباً')"
              class="text-indigo-600 hover:text-indigo-800"
            >
              {{ $t('communication.willContact') }}
            </button>
            <button
              @click="insertQuickReply('هل يمكنني مساعدتك في شيء آخر؟')"
              class="text-indigo-600 hover:text-indigo-800"
            >
              {{ $t('communication.anythingElse') }}
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
    ChatBubbleLeftRightIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    MagnifyingGlassIcon,
    PaperAirplaneIcon,
    UserIcon
} from '@heroicons/vue/24/outline'
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

// Reactive data
const conversations = ref([])
const selectedConversation = ref(null)
const messages = ref([])
const loading = ref(false)
const sending = ref(false)
const isTyping = ref(false)
const newMessage = ref('')
const searchQuery = ref('')
const selectedTemplate = ref('')
const templates = ref([])

const filters = ref({
  platform: '',
  status: '',
  priority: '',
  country: '',
  unread_only: false
})

const messagesContainer = ref(null)

// Computed
const unreadCount = computed(() => {
  return conversations.value.filter(c => !c.is_read).length
})

// Methods
const refreshConversations = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    Object.entries(filters.value).forEach(([key, value]) => {
      if (value) params.append(key, value)
    })
    if (searchQuery.value) params.append('search', searchQuery.value)

    const response = await fetch(`/api/communication/conversations?${params}`)
    const data = await response.json()
    conversations.value = data.data
  } catch (error) {
    console.error('Error fetching conversations:', error)
  } finally {
    loading.value = false
  }
}

const selectConversation = async (conversation) => {
  selectedConversation.value = conversation
  await loadMessages(conversation.id)
  
  if (!conversation.is_read) {
    await markAsRead()
  }
}

const loadMessages = async (conversationId) => {
  try {
    const response = await fetch(`/api/communication/conversations/${conversationId}`)
    const data = await response.json()
    messages.value = data.data.messages
    
    await nextTick()
    scrollToBottom()
  } catch (error) {
    console.error('Error loading messages:', error)
  }
}

const sendMessage = async () => {
  if (!newMessage.value.trim() || sending.value) return

  sending.value = true
  try {
    const response = await fetch(`/api/communication/conversations/${selectedConversation.value.id}/messages`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        content: newMessage.value
      })
    })

    if (response.ok) {
      newMessage.value = ''
      await loadMessages(selectedConversation.value.id)
    }
  } catch (error) {
    console.error('Error sending message:', error)
  } finally {
    sending.value = false
  }
}

const markAsRead = async () => {
  if (!selectedConversation.value) return

  try {
    await fetch(`/api/communication/conversations/${selectedConversation.value.id}/mark-read`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })

    selectedConversation.value.is_read = true
    const conv = conversations.value.find(c => c.id === selectedConversation.value.id)
    if (conv) conv.is_read = true
  } catch (error) {
    console.error('Error marking as read:', error)
  }
}

const updateConversationStatus = async () => {
  try {
    await fetch(`/api/communication/conversations/${selectedConversation.value.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        status: selectedConversation.value.status
      })
    })
  } catch (error) {
    console.error('Error updating status:', error)
  }
}

const updateConversationPriority = async () => {
  try {
    await fetch(`/api/communication/conversations/${selectedConversation.value.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        priority: selectedConversation.value.priority
      })
    })
  } catch (error) {
    console.error('Error updating priority:', error)
  }
}

const loadTemplates = async () => {
  try {
    const response = await fetch('/api/communication/templates')
    const data = await response.json()
    templates.value = data.data
  } catch (error) {
    console.error('Error loading templates:', error)
  }
}

const useTemplate = () => {
  if (selectedTemplate.value) {
    newMessage.value = selectedTemplate.value.content
    selectedTemplate.value = ''
  }
}

const insertQuickReply = (text) => {
  newMessage.value = text
}

const applyFilters = () => {
  refreshConversations()
}

const debounceSearch = (() => {
  let timeout
  return () => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
      refreshConversations()
    }, 500)
  }
})()

const scrollToBottom = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

// Helper functions
const getPlatformIcon = (platform) => {
  const icons = {
    whatsapp: '💬',
    facebook: '📘',
    instagram: '📷',
    twitter: '🐦',
    telegram: '✈️'
  }
  return icons[platform] || '💬'
}

const getPlatformColor = (platform) => {
  const colors = {
    whatsapp: 'bg-green-500',
    facebook: 'bg-blue-600',
    instagram: 'bg-pink-500',
    twitter: 'bg-blue-400',
    telegram: 'bg-blue-500'
  }
  return colors[platform] || 'bg-gray-500'
}

const getCountryFlag = (countryCode) => {
  const flags = {
    SA: '🇸🇦',
    AE: '🇦🇪',
    KW: '🇰🇼',
    QA: '🇶🇦',
    BH: '🇧🇭',
    OM: '🇴🇲'
  }
  return flags[countryCode] || '🌍'
}

const formatTime = (timestamp) => {
  if (!timestamp) return ''
  const date = new Date(timestamp)
  const now = new Date()
  const diff = now - date

  if (diff < 60000) return t('communication.justNow')
  if (diff < 3600000) return `${Math.floor(diff / 60000)}m`
  if (diff < 86400000) return `${Math.floor(diff / 3600000)}h`
  return date.toLocaleDateString()
}

// Lifecycle
onMounted(() => {
  refreshConversations()
  loadTemplates()
  
  // Auto-refresh every 30 seconds
  setInterval(refreshConversations, 30000)
})

// Watch for new messages (in real implementation, use WebSocket)
watch(selectedConversation, (newConv) => {
  if (newConv) {
    // Simulate typing indicator occasionally
    if (Math.random() > 0.8) {
      isTyping.value = true
      setTimeout(() => {
        isTyping.value = false
      }, 2000)
    }
  }
})
</script>

<style scoped>
/* RTL support for Arabic */
.rtl {
  direction: rtl;
}

/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
