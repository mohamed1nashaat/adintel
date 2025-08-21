import { useAuthStore } from '@/stores/auth'
import { createRouter, createWebHistory } from 'vue-router'

// Import pages
import Layout from '@/layouts/AppLayout.vue'
import FacebookCallback from '@/pages/FacebookCallback.vue'
import Login from '@/pages/Login.vue'

// Import Phase 2 Feature Pages
import Communications from '@/pages/Communications.vue'
import GCCBenchmarks from '@/pages/GCCBenchmarks.vue'
import PostScheduler from '@/pages/PostScheduler.vue'

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
        component: () => import('@/pages/DashboardFixed.vue'),
        meta: { title: 'AdIntel Dashboard - Dynamic Platform' }
      },
      {
        path: '/reports',
        name: 'reports',
        component: () => import('@/pages/ReportsEnhanced.vue'),
        meta: { title: 'Reports' }
      },
      {
        path: '/integrations',
        name: 'integrations',
        component: () => import('@/pages/IntegrationsEnhanced.vue'),
        meta: { title: 'Integrations' }
      },
      // Phase 2 Feature Routes
      {
        path: '/content',
        name: 'content',
        component: () => import('@/pages/ContentManagerEnhanced.vue'),
        meta: { title: 'Content Manager' }
      },
      {
        path: '/leads',
        name: 'leads',
        component: () => import('@/pages/LeadManagerEnhanced.vue'),
        meta: { title: 'Lead Management' }
      },
      {
        path: '/communications',
        name: 'communications',
        component: Communications,
        meta: { title: 'Communications Hub' }
      },
      {
        path: '/benchmarks',
        name: 'benchmarks',
        component: GCCBenchmarks,
        meta: { title: 'GCC Benchmarks' }
      },
      {
        path: '/scheduler',
        name: 'scheduler',
        component: PostScheduler,
        meta: { title: 'Post Scheduler' }
      },
      {
        path: '/projects',
        name: 'projects',
        component: () => import('@/pages/ProjectManagement.vue'),
        meta: { title: 'Project Management' }
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
