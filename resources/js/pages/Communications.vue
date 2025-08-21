<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          💬 Communications Hub
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Unified inbox for all social platforms including WhatsApp Business
        </p>
      </div>
      <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
        <button class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
          Mark All Read
        </button>
        <button class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
          Compose Message
        </button>
      </div>
    </div>

    <!-- Platform Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
      <div v-for="platform in platforms" :key="platform.name" class="bg-white p-4 rounded-lg shadow text-center">
        <div class="flex justify-center mb-2">
          <div class="h-10 w-10 rounded-full flex items-center justify-center" :class="platform.bgColor">
            <span class="text-xl">{{ platform.emoji }}</span>
          </div>
        </div>
        <p class="text-sm font-medium text-gray-900">{{ platform.name }}</p>
        <p class="text-xs text-gray-500">{{ platform.unread }} unread</p>
      </div>
    </div>

    <!-- WhatsApp Business Integration -->
    <div class="bg-white shadow rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">💬 WhatsApp Business</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="border rounded-lg p-4">
          <h4 class="font-medium text-gray-900">Connection Status</h4>
          <div class="mt-2 flex items-center">
            <div class="h-2 w-2 bg-green-400 rounded-full mr-2"></div>
            <span class="text-sm text-green-600">Connected</span>
          </div>
        </div>
        <div class="border rounded-lg p-4">
          <h4 class="font-medium text-gray-900">Messages Today</h4>
          <p class="text-2xl font-semibold text-gray-900 mt-1">24</p>
        </div>
        <div class="border rounded-lg p-4">
          <h4 class="font-medium text-gray-900">Response Time</h4>
          <p class="text-2xl font-semibold text-gray-900 mt-1">2.5m</p>
        </div>
      </div>
    </div>

    <!-- Message List -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Conversations List -->
      <div class="lg:col-span-1">
        <div class="bg-white shadow rounded-lg">
          <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Conversations</h3>
          </div>
          <ul class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
            <li v-for="conversation in conversations" :key="conversation.id" 
                class="px-4 py-4 hover:bg-gray-50 cursor-pointer"
                :class="{ 'bg-blue-50': selectedConversation === conversation.id }"
                @click="selectConversation(conversation.id)">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <span class="text-xl">{{ conversation.platform_emoji }}</span>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ conversation.contact }}</p>
                    <p class="text-xs text-gray-500">{{ conversation.time }}</p>
                  </div>
                  <p class="text-sm text-gray-500 truncate">{{ conversation.last_message }}</p>
                </div>
                <div v-if="conversation.unread > 0" class="flex-shrink-0">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    {{ conversation.unread }}
                  </span>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <!-- Message Thread -->
      <div class="lg:col-span-2">
        <div class="bg-white shadow rounded-lg h-96 flex flex-col">
          <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
              {{ selectedConversationData?.contact || 'Select a conversation' }}
            </h3>
            <p class="text-sm text-gray-500">{{ selectedConversationData?.platform || '' }}</p>
          </div>
          
          <div class="flex-1 overflow-y-auto p-4 space-y-4">
            <div v-for="message in messages" :key="message.id" 
                 class="flex" :class="message.sent_by === 'user' ? 'justify-end' : 'justify-start'">
              <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg"
                   :class="message.sent_by === 'user' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-900'">
                <p class="text-sm">{{ message.content }}</p>
                <p class="text-xs mt-1 opacity-75">{{ message.time }}</p>
              </div>
            </div>
          </div>

          <div class="border-t border-gray-200 p-4">
            <div class="flex space-x-2">
              <input type="text" v-model="newMessage" 
                     class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm"
                     placeholder="Type your message..."
                     @keyup.enter="sendMessage">
              <button @click="sendMessage" 
                      class="px-4 py-2 bg-blue-500 text-white rounded-md text-sm font-medium hover:bg-blue-600">
                Send
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

const platforms = ref([
  { name: 'WhatsApp', emoji: '💬', bgColor: 'bg-green-100', unread: 3 },
  { name: 'Facebook', emoji: '📘', bgColor: 'bg-blue-100', unread: 2 },
  { name: 'Instagram', emoji: '📷', bgColor: 'bg-pink-100', unread: 1 },
  { name: 'Twitter', emoji: '🐦', bgColor: 'bg-blue-100', unread: 1 },
  { name: 'LinkedIn', emoji: '💼', bgColor: 'bg-blue-100', unread: 0 },
  { name: 'TikTok', emoji: '🎵', bgColor: 'bg-gray-100', unread: 0 },
  { name: 'YouTube', emoji: '📺', bgColor: 'bg-red-100', unread: 0 },
  { name: 'Snapchat', emoji: '👻', bgColor: 'bg-yellow-100', unread: 0 }
])

const conversations = ref([
  { 
    id: 1, 
    contact: 'Ahmed Al-Rashid', 
    platform: 'WhatsApp', 
    platform_emoji: '💬',
    last_message: 'مرحبا، أريد معرفة المزيد عن خدماتكم',
    time: '2m ago',
    unread: 2
  },
  { 
    id: 2, 
    contact: 'Fatima Al-Zahra', 
    platform: 'Facebook', 
    platform_emoji: '📘',
    last_message: 'Thank you for the quick response!',
    time: '15m ago',
    unread: 1
  },
  { 
    id: 3, 
    contact: 'Mohammed Al-Otaibi', 
    platform: 'Instagram', 
    platform_emoji: '📷',
    last_message: 'Can you send me the pricing details?',
    time: '1h ago',
    unread: 0
  },
  { 
    id: 4, 
    contact: 'Sarah Al-Mansouri', 
    platform: 'LinkedIn', 
    platform_emoji: '💼',
    last_message: 'Great meeting you at the conference',
    time: '2h ago',
    unread: 0
  }
])

const selectedConversation = ref(1)
const newMessage = ref('')

const selectedConversationData = computed(() => {
  return conversations.value.find(c => c.id === selectedConversation.value)
})

const messages = ref([
  { id: 1, content: 'مرحبا، كيف يمكنني مساعدتك؟', sent_by: 'agent', time: '10:30 AM' },
  { id: 2, content: 'مرحبا، أريد معرفة المزيد عن خدماتكم', sent_by: 'customer', time: '10:32 AM' },
  { id: 3, content: 'بالطبع! نحن نقدم خدمات التسويق الرقمي الشاملة', sent_by: 'agent', time: '10:33 AM' },
  { id: 4, content: 'هل يمكنك إرسال تفاصيل الأسعار؟', sent_by: 'customer', time: '10:35 AM' }
])

const selectConversation = (id: number) => {
  selectedConversation.value = id
  // In a real app, this would fetch messages for the selected conversation
}

const sendMessage = () => {
  if (newMessage.value.trim()) {
    messages.value.push({
      id: messages.value.length + 1,
      content: newMessage.value,
      sent_by: 'agent',
      time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    })
    newMessage.value = ''
  }
}
</script>
