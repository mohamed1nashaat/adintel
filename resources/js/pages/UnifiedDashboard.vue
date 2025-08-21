<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation Header -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <h1 class="text-2xl font-bold text-gray-900">AdIntel</h1>
              <p class="text-xs text-gray-500">Complete Enterprise Platform</p>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                  'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                  activeTab === tab.id
                    ? 'border-indigo-500 text-gray-900'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]"
              >
                <component :is="tab.icon" class="w-4 h-4 mr-2" />
                {{ tab.name }}
              </button>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-500">{{ user?.name }}</span>
            <button @click="logout" class="text-sm text-gray-500 hover:text-gray-700">
              Logout
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <!-- Dashboard Overview -->
      <div v-if="activeTab === 'overview'" class="space-y-6">
        <!-- Header Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <ChartBarIcon class="h-6 w-6 text-gray-400" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Campaigns</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.totalCampaigns }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <CurrencyDollarIcon class="h-6 w-6 text-gray-400" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Spend</dt>
                    <dd class="text-lg font-medium text-gray-900">${{ stats.totalSpend.toLocaleString() }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <UserGroupIcon class="h-6 w-6 text-gray-400" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Leads</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.totalLeads.toLocaleString() }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <ArrowTrendingUpIcon class="h-6 w-6 text-gray-400" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">ROAS</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.roas }}x</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Complete Platform Features Grid -->
        <div class="bg-white shadow rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Complete Platform Features</h3>
            <p class="text-sm text-gray-500">Full-featured SaaS advertising intelligence and management platform</p>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <!-- Core Analytics -->
              <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center mb-3">
                  <ChartBarIcon class="h-6 w-6 text-blue-500 mr-2" />
                  <h4 class="font-medium text-gray-900">Analytics Dashboard</h4>
                </div>
                <p class="text-sm text-gray-600 mb-3">Real-time KPI tracking & insights</p>
                <button @click="activeTab = 'analytics'" class="text-blue-600 text-sm hover:text-blue-800">
                  View Analytics →
                </button>
              </div>

              <!-- Platform Integrations -->
              <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center mb-3">
                  <CogIcon class="h-6 w-6 text-green-500 mr-2" />
                  <h4 class="font-medium text-gray-900">Platform Integrations</h4>
                </div>
                <p class="text-sm text-gray-600 mb-3">Facebook, Google, TikTok, Snapchat, LinkedIn</p>
                <button @click="activeTab = 'integrations'" class="text-green-600 text-sm hover:text-green-800">
                  Manage Integrations →
                </button>
              </div>

              <!-- Reports & Export -->
              <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center mb-3">
                  <DocumentTextIcon class="h-6 w-6 text-purple-500 mr-2" />
                  <h4 class="font-medium text-gray-900">Reports & Export</h4>
                </div>
                <p class="text-sm text-gray-600 mb-3">Custom reports, CSV/Excel export</p>
                <button @click="activeTab = 'reports'" class="text-purple-600 text-sm hover:text-purple-800">
                  Generate Reports →
                </button>
              </div>

              <!-- Content Management -->
              <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center mb-3">
                  <PencilIcon class="h-6 w-6 text-orange-500 mr-2" />
                  <h4 class="font-medium text-gray-900">Content Management</h4>
                </div>
                <p class="text-sm text-gray-600 mb-3">AI moderation, scheduling, calendar</p>
                <button @click="activeTab = 'content'" class="text-orange-600 text-sm hover:text-orange-800">
                  Manage Content →
                </button>
              </div>

              <!-- Lead Management -->
              <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center mb-3">
                  <UserGroupIcon class="h-6 w-6 text-red-500 mr-2" />
                  <h4 class="font-medium text-gray-900">Lead Management</h4>
                </div>
                <p class="text-sm text-gray-600 mb-3">Custom audiences, file upload</p>
                <button @click="activeTab = 'leads'" class="text-red-600 text-sm hover:text-red-800">
                  Manage Leads →
                </button>
              </div>

              <!-- Communication Hub -->
              <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center mb-3">
                  <ChatBubbleLeftRightIcon class="h-6 w-6 text-indigo-500 mr-2" />
                  <h4 class="font-medium text-gray-900">Communication Hub</h4>
                </div>
                <p class="text-sm text-gray-600 mb-3">Multi-platform messaging, WhatsApp</p>
                <button @click="activeTab = 'communications'" class="text-indigo-600 text-sm hover:text-indigo-800">
                  View Messages →
                </button>
              </div>

              <!-- AI Pitch Generator -->
              <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center mb-3">
                  <svg class="h-6 w-6 text-pink-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                  </svg>
                  <h4 class="font-medium text-gray-900">AI Pitch Generator</h4>
                </div>
                <p class="text-sm text-gray-600 mb-3">Industry-specific pitch creation</p>
                <button @click="activeTab = 'pitching'" class="text-pink-600 text-sm hover:text-pink-800">
                  Generate Pitches →
                </button>
              </div>

              <!-- Custom Branding -->
              <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center mb-3">
                  <svg class="h-6 w-6 text-yellow-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path>
                  </svg>
                  <h4 class="font-medium text-gray-900">Custom Branding</h4>
                </div>
                <p class="text-sm text-gray-600 mb-3">Logo upload, color themes</p>
                <button @click="activeTab = 'branding'" class="text-yellow-600 text-sm hover:text-yellow-800">
                  Customize Brand →
                </button>
              </div>

              <!-- Project Management -->
              <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center mb-3">
                  <svg class="h-6 w-6 text-teal-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                  </svg>
                  <h4 class="font-medium text-gray-900">Project Management</h4>
                </div>
                <p class="text-sm text-gray-600 mb-3">Multi-project navigation</p>
                <button @click="activeTab = 'projects'" class="text-teal-600 text-sm hover:text-teal-800">
                  Manage Projects →
                </button>
              </div>

              <!-- User Management -->
              <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center mb-3">
                  <svg class="h-6 w-6 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                  </svg>
                  <h4 class="font-medium text-gray-900">User Management</h4>
                </div>
                <p class="text-sm text-gray-600 mb-3">Team access, role management</p>
                <button @click="activeTab = 'users'" class="text-gray-600 text-sm hover:text-gray-800">
                  Manage Users →
                </button>
              </div>

              <!-- Benchmarks -->
              <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center mb-3">
                  <ArrowTrendingUpIcon class="h-6 w-6 text-emerald-500 mr-2" />
                  <h4 class="font-medium text-gray-900">Benchmarks</h4>
                </div>
                <p class="text-sm text-gray-600 mb-3">Industry performance comparison</p>
                <button @click="activeTab = 'benchmarks'" class="text-emerald-600 text-sm hover:text-emerald-800">
                  View Benchmarks →
                </button>
              </div>

              <!-- Feature Suggestions -->
              <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center mb-3">
                  <svg class="h-6 w-6 text-violet-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                  </svg>
                  <h4 class="font-medium text-gray-900">Feature Suggestions</h4>
                </div>
                <p class="text-sm text-gray-600 mb-3">AI-powered recommendations</p>
                <button @click="activeTab = 'suggestions'" class="text-violet-600 text-sm hover:text-violet-800">
                  View Suggestions →
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Analytics Tab -->
      <div v-if="activeTab === 'analytics'">
        <Dashboard />
      </div>

      <!-- Integrations Tab -->
      <div v-if="activeTab === 'integrations'">
        <IntegrationsEnhanced />
      </div>

      <!-- Reports Tab -->
      <div v-if="activeTab === 'reports'">
        <ReportsEnhanced />
      </div>

      <!-- Content Management Tab -->
      <div v-if="activeTab === 'content'">
        <ContentManagementEnhanced />
      </div>

      <!-- Lead Management Tab -->
      <div v-if="activeTab === 'leads'">
        <LeadManagerEnhanced />
      </div>

      <!-- Communications Tab -->
      <div v-if="activeTab === 'communications'">
        <Communications />
      </div>

      <!-- Pitching Tab -->
      <div v-if="activeTab === 'pitching'">
        <PitchGeneratorPage />
      </div>

      <!-- Branding Tab -->
      <div v-if="activeTab === 'branding'">
        <BrandingPage />
      </div>

      <!-- Projects Tab -->
      <div v-if="activeTab === 'projects'">
        <ProjectManagement />
      </div>

      <!-- Users Tab -->
      <div v-if="activeTab === 'users'">
        <UserManagement />
      </div>

      <!-- Benchmarks Tab -->
      <div v-if="activeTab === 'benchmarks'">
        <BenchmarksPage />
      </div>

      <!-- Suggestions Tab -->
      <div v-if="activeTab === 'suggestions'">
        <FeatureSuggestionEngine />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import {
  ArrowTrendingUpIcon,
  ChartBarIcon,
  ChatBubbleLeftRightIcon,
  CogIcon,
  CurrencyDollarIcon,
  DocumentTextIcon,
  PencilIcon,
  UserGroupIcon
} from '@heroicons/vue/24/outline'
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

// Import components
import FeatureSuggestionEngine from '@/components/FeatureSuggestionEngine.vue'
import Communications from '@/pages/Communications.vue'
import ContentManagementEnhanced from '@/pages/ContentManagementEnhanced.vue'
import Dashboard from '@/pages/Dashboard.vue'
import IntegrationsEnhanced from '@/pages/IntegrationsEnhanced.vue'
import LeadManagerEnhanced from '@/pages/LeadManagerEnhanced.vue'
import PitchGeneratorPage from '@/pages/PitchGeneratorPage.vue'
import ProjectManagement from '@/pages/ProjectManagement.vue'
import ReportsEnhanced from '@/pages/ReportsEnhanced.vue'

// Import placeholder components for missing pages
import BenchmarksPage from '@/pages/BenchmarksPage.vue'
import BrandingPage from '@/pages/BrandingPage.vue'
import UserManagement from '@/pages/UserManagement.vue'

const authStore = useAuthStore()
const router = useRouter()

const activeTab = ref('overview')
const user = ref(authStore.user)

const tabs = [
  { id: 'overview', name: 'Overview', icon: ChartBarIcon },
  { id: 'analytics', name: 'Analytics', icon: ChartBarIcon },
  { id: 'integrations', name: 'Integrations', icon: CogIcon },
  { id: 'reports', name: 'Reports', icon: DocumentTextIcon },
  { id: 'content', name: 'Content', icon: PencilIcon },
  { id: 'leads', name: 'Leads', icon: UserGroupIcon },
  { id: 'communications', name: 'Messages', icon: ChatBubbleLeftRightIcon },
  { id: 'pitching', name: 'Pitching', icon: 'LightBulbIcon' },
  { id: 'branding', name: 'Branding', icon: 'PaintBrushIcon' },
  { id: 'projects', name: 'Projects', icon: 'BriefcaseIcon' },
  { id: 'users', name: 'Users', icon: 'UsersIcon' },
  { id: 'benchmarks', name: 'Benchmarks', icon: ArrowTrendingUpIcon },
  { id: 'suggestions', name: 'Suggestions', icon: 'BoltIcon' }
]

const stats = ref({
  totalCampaigns: 24,
  totalSpend: 125430,
  totalLeads: 1247,
  roas: 3.2
})

const logout = async () => {
  await authStore.logout()
  router.push('/login')
}

onMounted(async () => {
  // Load initial data
  try {
    await authStore.fetchUser()
    user.value = authStore.user
  } catch (error) {
    console.error('Error loading user data:', error)
  }
})
</script>

<style scoped>
.transition-shadow {
  transition: box-shadow 0.2s ease;
}
</style>
