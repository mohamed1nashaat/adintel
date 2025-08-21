import { createRouter, createWebHistory } from 'vue-router'

// Phase 2 Pages
import BenchmarksEnhanced from '@/pages/BenchmarksEnhanced.vue'
import BrandingEnhanced from '@/pages/BrandingEnhanced.vue'
import CommunicationsEnhanced from '@/pages/CommunicationsEnhanced.vue'
import ContentManagementEnhanced from '@/pages/ContentManagementEnhanced.vue'
import CustomDashboardsEnhanced from '@/pages/CustomDashboardsEnhanced.vue'
import FeatureFlagsEnhanced from '@/pages/FeatureFlagsEnhanced.vue'
import FeatureSuggestionsEnhanced from '@/pages/FeatureSuggestionsEnhanced.vue'
import LeadManagerEnhanced from '@/pages/LeadManagerEnhanced.vue'
import OfflineDataEnhanced from '@/pages/OfflineDataEnhanced.vue'
import Phase2Dashboard from '@/pages/Phase2Dashboard.vue'
import PitchGeneratorEnhanced from '@/pages/PitchGeneratorEnhanced.vue'
import PostSchedulerEnhanced from '@/pages/PostSchedulerEnhanced.vue'

// Existing pages
import Dashboard from '@/pages/Dashboard.vue'
import Integrations from '@/pages/Integrations.vue'
import Login from '@/pages/Login.vue'
import Reports from '@/pages/Reports.vue'

const routes = [
  {
    path: '/',
    redirect: '/phase2-dashboard'
  },
  {
    path: '/login',
    name: 'Login',
    component: Login
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard
  },
  {
    path: '/integrations',
    name: 'Integrations',
    component: Integrations
  },
  {
    path: '/reports',
    name: 'Reports',
    component: Reports
  },
  
  // Phase 2 Routes
  {
    path: '/phase2-dashboard',
    name: 'Phase2Dashboard',
    component: Phase2Dashboard,
    meta: {
      title: 'Phase 2 Dashboard - AdIntel',
      description: 'Complete overview of all Phase 2 features'
    }
  },
  {
    path: '/content-management',
    name: 'ContentManagement',
    component: ContentManagementEnhanced,
    meta: {
      title: 'Content Management - AdIntel',
      description: 'AI-powered content workflows with approval processes'
    }
  },
  {
    path: '/lead-management',
    name: 'LeadManagement',
    component: LeadManagerEnhanced,
    meta: {
      title: 'Lead Management - AdIntel',
      description: 'Enhanced lead management with custom audiences and file upload'
    }
  },
  {
    path: '/post-publishing',
    name: 'PostPublishing',
    component: ContentManagementEnhanced, // Reuse content management for now
    meta: {
      title: 'Post Publishing - AdIntel',
      description: 'Multi-platform post preview and publishing'
    }
  },
  {
    path: '/scheduling',
    name: 'Scheduling',
    component: PostSchedulerEnhanced,
    meta: {
      title: 'Post Scheduling - AdIntel',
      description: 'Advanced scheduling with recurring posts and timezone support'
    }
  },
  {
    path: '/communications',
    name: 'Communications',
    component: CommunicationsEnhanced,
    meta: {
      title: 'Communications Hub - AdIntel',
      description: 'Multi-platform message aggregation with WhatsApp integration'
    }
  },
  {
    path: '/benchmarks',
    name: 'Benchmarks',
    component: BenchmarksEnhanced,
    meta: {
      title: 'Benchmark Analysis - AdIntel',
      description: 'Industry comparisons with competitive intelligence'
    }
  },
  {
    path: '/pitch-generator',
    name: 'PitchGenerator',
    component: PitchGeneratorEnhanced,
    meta: {
      title: 'AI Pitch Generator - AdIntel',
      description: 'AI-powered pitch generation with OpenAI integration'
    }
  },
  {
    path: '/feature-suggestions',
    name: 'FeatureSuggestions',
    component: FeatureSuggestionsEnhanced,
    meta: {
      title: 'Feature Suggestions - AdIntel',
      description: 'AI-powered recommendations and analytics'
    }
  },
  {
    path: '/custom-dashboards',
    name: 'CustomDashboards',
    component: CustomDashboardsEnhanced,
    meta: {
      title: 'Custom Dashboards - AdIntel',
      description: 'Drag-and-drop dashboard builder with 10+ widget types'
    }
  },
  {
    path: '/branding',
    name: 'Branding',
    component: BrandingEnhanced,
    meta: {
      title: 'Custom Branding - AdIntel',
      description: 'Logo upload and CSS generation for custom branding'
    }
  },
  {
    path: '/offline-data',
    name: 'OfflineData',
    component: OfflineDataEnhanced,
    meta: {
      title: 'Offline Data Integration - AdIntel',
      description: 'Conversion tracking with platform sync capabilities'
    }
  },
  {
    path: '/feature-flags',
    name: 'FeatureFlags',
    component: FeatureFlagsEnhanced,
    meta: {
      title: 'Feature Flags - AdIntel',
      description: 'Feature flags with rollout management and conditional logic'
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard for meta titles
router.beforeEach((to, from, next) => {
  if (to.meta?.title) {
    document.title = to.meta.title as string
  }
  next()
})

export default router
