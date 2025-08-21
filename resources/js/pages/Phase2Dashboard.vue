<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">AdIntel Phase 2</h1>
            <p class="text-gray-600 mt-1">Complete Enterprise Advertising Intelligence Platform</p>
          </div>
          <div class="flex items-center space-x-4">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
              <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
              </svg>
              100% Complete
            </span>
            <span class="text-sm text-gray-500">12/12 Features</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Progress Overview -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-6 text-white mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-2xl font-bold mb-2">Phase 2 Implementation Complete!</h2>
            <p class="text-blue-100">All 12 enterprise features have been successfully implemented with full backend services and API coverage.</p>
          </div>
          <div class="text-right">
            <div class="text-4xl font-bold">100%</div>
            <div class="text-blue-100">Implementation</div>
          </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="mt-4">
          <div class="bg-blue-400 rounded-full h-3">
            <div class="bg-white rounded-full h-3 transition-all duration-1000" :style="{ width: progressWidth }"></div>
          </div>
        </div>
      </div>

      <!-- Feature Categories -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="flex items-center">
            <div class="bg-green-100 rounded-lg p-3">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-semibold text-gray-900">Core Features</h3>
              <p class="text-gray-600">8 Features Complete</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="flex items-center">
            <div class="bg-blue-100 rounded-lg p-3">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 7.172V5L8 4z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-semibold text-gray-900">Advanced Features</h3>
              <p class="text-gray-600">4 Features Complete</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="flex items-center">
            <div class="bg-purple-100 rounded-lg p-3">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-semibold text-gray-900">API Endpoints</h3>
              <p class="text-gray-600">80+ Routes Active</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Features Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <FeatureCard
          v-for="feature in features"
          :key="feature.id"
          :feature="feature"
          @navigate="navigateToFeature"
        />
      </div>

      <!-- Technical Stats -->
      <div class="mt-12 bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Technical Implementation Stats</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <div class="text-center">
            <div class="text-3xl font-bold text-blue-600">23</div>
            <div class="text-sm text-gray-600">Database Migrations</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-green-600">15+</div>
            <div class="text-sm text-gray-600">Models Created</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-purple-600">12+</div>
            <div class="text-sm text-gray-600">Service Classes</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-orange-600">80+</div>
            <div class="text-sm text-gray-600">API Endpoints</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import FeatureCard from '@/components/FeatureCard.vue'
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const progressWidth = ref('0%')

const features = ref([
  {
    id: 1,
    title: 'Content Management & Moderation',
    description: 'AI-powered content workflows with approval processes and multi-platform publishing',
    icon: '📝',
    status: 'complete',
    route: '/content-management',
    category: 'core',
    endpoints: 8,
    color: 'bg-blue-500'
  },
  {
    id: 2,
    title: 'Lead Management & Custom Audiences',
    description: 'Enhanced lead management with custom audience builder and file upload capabilities',
    icon: '👥',
    status: 'complete',
    route: '/lead-management',
    category: 'core',
    endpoints: 8,
    color: 'bg-green-500'
  },
  {
    id: 3,
    title: 'Post Preview & Publishing',
    description: 'Multi-platform post preview and publishing with approval workflows',
    icon: '📱',
    status: 'complete',
    route: '/post-publishing',
    category: 'core',
    endpoints: 6,
    color: 'bg-purple-500'
  },
  {
    id: 4,
    title: 'Post Scheduling System',
    description: 'Advanced scheduling with recurring posts and timezone support',
    icon: '⏰',
    status: 'complete',
    route: '/scheduling',
    category: 'core',
    endpoints: 7,
    color: 'bg-orange-500'
  },
  {
    id: 5,
    title: 'Unified Communication Hub',
    description: 'Multi-platform message aggregation with WhatsApp integration',
    icon: '💬',
    status: 'complete',
    route: '/communications',
    category: 'core',
    endpoints: 8,
    color: 'bg-pink-500'
  },
  {
    id: 6,
    title: 'Benchmark Analysis System',
    description: 'Industry comparisons with 15+ categories and competitive intelligence',
    icon: '📊',
    status: 'complete',
    route: '/benchmarks',
    category: 'core',
    endpoints: 8,
    color: 'bg-indigo-500'
  },
  {
    id: 7,
    title: 'AI-Powered Pitch Generator',
    description: 'Complete pitch generation with OpenAI integration and templates',
    icon: '🤖',
    status: 'complete',
    route: '/pitch-generator',
    category: 'ai',
    endpoints: 11,
    color: 'bg-cyan-500'
  },
  {
    id: 8,
    title: 'Feature Suggestion Engine',
    description: 'AI-powered recommendations with comprehensive analytics',
    icon: '💡',
    status: 'complete',
    route: '/feature-suggestions',
    category: 'ai',
    endpoints: 11,
    color: 'bg-yellow-500'
  },
  {
    id: 9,
    title: 'Custom Dashboard System',
    description: '10+ widget types with drag-and-drop builder and templates',
    icon: '📈',
    status: 'complete',
    route: '/custom-dashboards',
    category: 'advanced',
    endpoints: 12,
    color: 'bg-red-500'
  },
  {
    id: 10,
    title: 'Custom Logo & Branding',
    description: 'Complete branding solution with logo upload and CSS generation',
    icon: '🎨',
    status: 'complete',
    route: '/branding',
    category: 'advanced',
    endpoints: 7,
    color: 'bg-teal-500'
  },
  {
    id: 11,
    title: 'Offline Data Integration',
    description: 'Comprehensive conversion tracking with platform sync capabilities',
    icon: '📋',
    status: 'complete',
    route: '/offline-data',
    category: 'advanced',
    endpoints: 8,
    color: 'bg-gray-500'
  },
  {
    id: 12,
    title: 'Advanced Feature Engine',
    description: 'Feature flags with rollout management and conditional logic',
    icon: '⚙️',
    status: 'complete',
    route: '/feature-flags',
    category: 'advanced',
    endpoints: 7,
    color: 'bg-emerald-500'
  }
])

const navigateToFeature = (route: string) => {
  router.push(route)
}

onMounted(() => {
  // Animate progress bar
  setTimeout(() => {
    progressWidth.value = '100%'
  }, 500)
})
</script>

<style scoped>
.transition-all {
  transition: all 0.3s ease;
}
</style>
