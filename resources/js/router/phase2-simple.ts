import { createRouter, createWebHistory } from 'vue-router'

// Phase 2 Pages
import ContentManagementEnhanced from '@/pages/ContentManagementEnhanced.vue'
import LeadManagerEnhanced from '@/pages/LeadManagerEnhanced.vue'
import Phase2Dashboard from '@/pages/Phase2Dashboard.vue'

// Existing pages
import Communications from '@/pages/Communications.vue'
import Dashboard from '@/pages/Dashboard.vue'
import GCCBenchmarks from '@/pages/GCCBenchmarks.vue'
import Integrations from '@/pages/Integrations.vue'
import Login from '@/pages/Login.vue'
import PitchGeneratorPage from '@/pages/PitchGeneratorPage.vue'
import PostScheduler from '@/pages/PostScheduler.vue'
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
    component: ContentManagementEnhanced,
    meta: {
      title: 'Post Publishing - AdIntel',
      description: 'Multi-platform post preview and publishing'
    }
  },
  {
    path: '/scheduling',
    name: 'Scheduling',
    component: PostScheduler,
    meta: {
      title: 'Post Scheduling - AdIntel',
      description: 'Advanced scheduling with recurring posts and timezone support'
    }
  },
  {
    path: '/communications',
    name: 'Communications',
    component: Communications,
    meta: {
      title: 'Communications Hub - AdIntel',
      description: 'Multi-platform message aggregation with WhatsApp integration'
    }
  },
  {
    path: '/benchmarks',
    name: 'Benchmarks',
    component: GCCBenchmarks,
    meta: {
      title: 'Benchmark Analysis - AdIntel',
      description: 'Industry comparisons with competitive intelligence'
    }
  },
  {
    path: '/pitch-generator',
    name: 'PitchGenerator',
    component: PitchGeneratorPage,
    meta: {
      title: 'AI Pitch Generator - AdIntel',
      description: 'AI-powered pitch generation with OpenAI integration'
    }
  },
  {
    path: '/feature-suggestions',
    name: 'FeatureSuggestions',
    component: Dashboard, // Placeholder
    meta: {
      title: 'Feature Suggestions - AdIntel',
      description: 'AI-powered recommendations and analytics'
    }
  },
  {
    path: '/custom-dashboards',
    name: 'CustomDashboards',
    component: Dashboard, // Placeholder
    meta: {
      title: 'Custom Dashboards - AdIntel',
      description: 'Drag-and-drop dashboard builder with 10+ widget types'
    }
  },
  {
    path: '/branding',
    name: 'Branding',
    component: Dashboard, // Placeholder
    meta: {
      title: 'Custom Branding - AdIntel',
      description: 'Logo upload and CSS generation for custom branding'
    }
  },
  {
    path: '/offline-data',
    name: 'OfflineData',
    component: Dashboard, // Placeholder
    meta: {
      title: 'Offline Data Integration - AdIntel',
      description: 'Conversion tracking with platform sync capabilities'
    }
  },
  {
    path: '/feature-flags',
    name: 'FeatureFlags',
    component: Dashboard, // Placeholder
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

// Navigation guard for authentication and meta titles
router.beforeEach(async (to, from, next) => {
  // Set page title
  if (to.meta?.title) {
    document.title = to.meta.title as string
  }

  // Check authentication for protected routes
  const token = localStorage.getItem('auth_token')
  const isAuthRoute = to.name === 'Login'
  const requiresAuth = to.matched.some(record => record.meta?.requiresAuth !== false)

  if (!isAuthRoute && requiresAuth && !token) {
    // Redirect to login if not authenticated
    next('/login')
    return
  }

  if (isAuthRoute && token) {
    // Redirect to dashboard if already authenticated
    next('/phase2-dashboard')
    return
  }

  next()
})

export default router
