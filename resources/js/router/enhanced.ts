import { useAuthStore } from '@/stores/auth'
import { createRouter, createWebHistory } from 'vue-router'

// Import pages
import Layout from '@/layouts/AppLayout.vue'
import ContentManagement from '@/pages/ContentManagement.vue'
import Dashboard from '@/pages/Dashboard.vue'
import EnhancedDashboard from '@/pages/EnhancedDashboard.vue'
import FacebookCallback from '@/pages/FacebookCallback.vue'
import Integrations from '@/pages/Integrations.vue'
import Login from '@/pages/Login.vue'
import Reports from '@/pages/Reports.vue'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: Login,
    meta: { requiresAuth: false }
  },
  {
    path: '/integrations/facebook/callback',
    name: 'facebook-callback',
    component: FacebookCallback,
    meta: { requiresAuth: true }
  },
  {
    path: '/',
    component: Layout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: '/dashboard'
      },
      {
        path: '/dashboard',
        name: 'dashboard',
        component: Dashboard,
        meta: { title: 'Dashboard' }
      },
      {
        path: '/enhanced',
        name: 'enhanced-dashboard',
        component: EnhancedDashboard,
        meta: { title: 'Enhanced Dashboard - Phase 2' }
      },
      {
        path: '/reports',
        name: 'reports',
        component: Reports,
        meta: { title: 'Reports' }
      },
      {
        path: '/integrations',
        name: 'integrations',
        component: Integrations,
        meta: { title: 'Integrations' }
      },
      // Phase 2 Feature Routes
      {
        path: '/content',
        name: 'content-management',
        component: ContentManagement,
        meta: { title: 'Content Management' }
      },
      {
        path: '/leads',
        name: 'lead-management',
        component: () => import('@/components/LeadDashboardSimple.vue'),
        meta: { title: 'Lead Management' }
      },
      {
        path: '/posts',
        name: 'post-scheduling',
        component: () => import('@/components/PostScheduler.vue'),
        meta: { title: 'Post Scheduling' }
      },
      {
        path: '/calendar',
        name: 'post-calendar',
        component: () => import('@/components/PostCalendar.vue'),
        meta: { title: 'Post Calendar' }
      },
      {
        path: '/communications',
        name: 'communications',
        component: () => import('@/components/CommunicationHub.vue'),
        meta: { title: 'Communications Hub' }
      },
      {
        path: '/benchmarks',
        name: 'benchmarks',
        component: () => import('@/pages/Benchmarks.vue'),
        meta: { title: 'GCC Benchmarks' }
      },
      {
        path: '/pitches',
        name: 'pitch-generator',
        component: () => import('@/pages/PitchGenerator.vue'),
        meta: { title: 'SEMrush Pitch Generator' }
      },
      {
        path: '/features',
        name: 'feature-suggestions',
        component: () => import('@/pages/FeatureSuggestions.vue'),
        meta: { title: 'Feature Suggestions' }
      },
      {
        path: '/custom-dashboard',
        name: 'custom-dashboard',
        component: () => import('@/pages/CustomDashboard.vue'),
        meta: { title: 'Custom Dashboard Builder' }
      },
      {
        path: '/branding',
        name: 'branding',
        component: () => import('@/pages/Branding.vue'),
        meta: { title: 'Brand Customization' }
      },
      {
        path: '/offline-data',
        name: 'offline-data',
        component: () => import('@/pages/OfflineData.vue'),
        meta: { title: 'Offline Data Integration' }
      },
      {
        path: '/feature-flags',
        name: 'feature-flags',
        component: () => import('@/pages/FeatureFlags.vue'),
        meta: { title: 'Feature Flags' }
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth !== false)
  
  if (requiresAuth && !authStore.isAuthenticated) {
    next('/login')
  } else if (to.path === '/login' && authStore.isAuthenticated) {
    next('/dashboard')
  } else {
    next()
  }
})

export default router
