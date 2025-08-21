<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          👥 Lead Management
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Capture, manage, and nurture leads with Google Sheets integration
        </p>
      </div>
      <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
        <button class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
          Export to Sheets
        </button>
        <button class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
          Add Lead
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
              <span class="text-blue-600 font-semibold text-sm">👥</span>
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-500">Total Leads</p>
            <p class="text-2xl font-semibold text-gray-900">156</p>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
              <span class="text-green-600 font-semibold text-sm">🆕</span>
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-500">New This Week</p>
            <p class="text-2xl font-semibold text-gray-900">12</p>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 bg-yellow-100 rounded-full flex items-center justify-center">
              <span class="text-yellow-600 font-semibold text-sm">⭐</span>
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-500">Qualified</p>
            <p class="text-2xl font-semibold text-gray-900">8</p>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="h-8 w-8 bg-purple-100 rounded-full flex items-center justify-center">
              <span class="text-purple-600 font-semibold text-sm">💰</span>
            </div>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-gray-500">Conversion Rate</p>
            <p class="text-2xl font-semibold text-gray-900">15.2%</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Google Sheets Integration -->
    <div class="bg-white shadow rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">📊 Google Sheets Integration</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="border rounded-lg p-4">
          <h4 class="font-medium text-gray-900">Webhook Status</h4>
          <p class="text-sm text-gray-500 mt-1">Real-time lead capture</p>
          <div class="mt-2 flex items-center">
            <div class="h-2 w-2 bg-green-400 rounded-full mr-2"></div>
            <span class="text-sm text-green-600">Active</span>
          </div>
        </div>
        <div class="border rounded-lg p-4">
          <h4 class="font-medium text-gray-900">Last Sync</h4>
          <p class="text-sm text-gray-500 mt-1">Google Sheets synchronization</p>
          <p class="text-sm text-gray-900 mt-2">2 minutes ago</p>
        </div>
      </div>
    </div>

    <!-- Lead Sources -->
    <div class="bg-white shadow rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Lead Sources</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div v-for="source in leadSources" :key="source.name" class="text-center">
          <div class="flex justify-center mb-2">
            <div class="h-12 w-12 rounded-full flex items-center justify-center" :class="source.bgColor">
              <span class="text-2xl">{{ source.emoji }}</span>
            </div>
          </div>
          <p class="text-sm font-medium text-gray-900">{{ source.name }}</p>
          <p class="text-xs text-gray-500">{{ source.count }} leads</p>
        </div>
      </div>
    </div>

    <!-- Recent Leads -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Leads</h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">Latest leads from all sources</p>
      </div>
      <ul class="divide-y divide-gray-200">
        <li v-for="lead in leads" :key="lead.id" class="px-4 py-4 hover:bg-gray-50">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                  <span class="text-sm font-medium text-gray-700">{{ getInitials(lead.name || lead.email) }}</span>
                </div>
              </div>
              <div class="ml-4">
                <div class="text-sm font-medium text-gray-900">{{ lead.name || 'Anonymous' }}</div>
                <div class="text-sm text-gray-500">{{ lead.email }} • {{ lead.source }}</div>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <span :class="getStatusClass(lead.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                {{ lead.status }}
              </span>
              <span class="text-sm text-gray-500">{{ lead.created_at }}</span>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const leadSources = ref([
  { name: 'Facebook', emoji: '📘', bgColor: 'bg-blue-100', count: 45 },
  { name: 'Google', emoji: '🔍', bgColor: 'bg-red-100', count: 38 },
  { name: 'LinkedIn', emoji: '💼', bgColor: 'bg-blue-100', count: 32 },
  { name: 'WhatsApp', emoji: '💬', bgColor: 'bg-green-100', count: 28 },
  { name: 'Website', emoji: '🌐', bgColor: 'bg-gray-100', count: 13 }
])

const leads = ref([
  { id: 1, name: 'Ahmed Al-Rashid', email: 'ahmed@example.com', source: 'Facebook', status: 'new', created_at: '2025-01-15' },
  { id: 2, name: 'Fatima Al-Zahra', email: 'fatima@example.com', source: 'Google', status: 'qualified', created_at: '2025-01-14' },
  { id: 3, name: 'Mohammed Al-Otaibi', email: 'mohammed@company.com', source: 'LinkedIn', status: 'contacted', created_at: '2025-01-13' },
  { id: 4, name: '', email: 'contact@business.ae', source: 'Website', status: 'new', created_at: '2025-01-12' },
  { id: 5, name: 'Sarah Al-Mansouri', email: 'sarah@startup.qa', source: 'WhatsApp', status: 'qualified', created_at: '2025-01-11' }
])

const getInitials = (name: string) => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const getStatusClass = (status: string) => {
  const classMap: { [key: string]: string } = {
    'new': 'bg-blue-100 text-blue-800',
    'qualified': 'bg-green-100 text-green-800',
    'contacted': 'bg-yellow-100 text-yellow-800',
    'converted': 'bg-purple-100 text-purple-800'
  }
  return classMap[status] || 'bg-gray-100 text-gray-800'
}
</script>
