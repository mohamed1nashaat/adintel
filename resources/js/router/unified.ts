import { createRouter, createWebHistory } from 'vue-router'

// Import pages
import Communications from '@/pages/Communications.vue'
import ContentManagementEnhanced from '@/pages/ContentManagementEnhanced.vue'
import Dashboard from '@/pages/Dashboard.vue'
import FacebookCallback from '@/pages/FacebookCallback.vue'
import IntegrationsEnhanced from '@/pages/IntegrationsEnhanced.vue'
import LeadManagerEnhanced from '@/pages/LeadManagerEnhanced.vue'
import Login from '@/pages/Login.vue'
import ReportsEnhanced from '@/pages/ReportsEnhanced.vue'
import UnifiedDashboard from '@/pages/UnifiedDashboard.vue'

const routes = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: {
      requiresAuth: false,
      title: 'Login - AdIntel'
    }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: UnifiedDashboard,
    meta: {
      requiresAuth: true,
      title: 'AdIntel - Complete Enterprise Platform'
    }
  },
  {
    path: '/analytics',
    name: 'Analytics',
    component: Dashboard,
    meta: {
      requiresAuth: true,
      title: 'Analytics - AdIntel'
    }
  },
  {
    path: '/integrations',
    name: 'Integrations',
    component: IntegrationsEnhanced,
    meta: {
      requiresAuth: true,
      title: 'Integrations - AdIntel'
    }
  },
  {
    path: '/reports',
    name: 'Reports',
    component: ReportsEnhanced,
    meta: {
      requiresAuth: true,
      title: 'Reports - AdIntel'
    }
  },
  {
    path: '/content',
    name: 'ContentManagement',
    component: ContentManagementEnhanced,
    meta: {
      requiresAuth: true,
      title: 'Content Management - AdIntel'
    }
  },
  {
    path: '/leads',
    name: 'LeadManagement',
    component: LeadManagerEnhanced,
    meta: {
      requiresAuth: true,
      title: 'Lead Management - AdIntel'
    }
  },
  {
    path: '/communications',
    name: 'Communications',
    component: Communications,
    meta: {
      requiresAuth: true,
      title: 'Communications - AdIntel'
    }
  },
  {
    path: '/facebook/callback',
    name: 'FacebookCallback',
    component: FacebookCallback,
    meta: {
      requiresAuth: true,
      title: 'Facebook Integration - AdIntel'
    }
  },
  // Legacy routes for backward compatibility
  {
    path: '/phase2-dashboard',
    redirect: '/dashboard'
  },
  {
    path: '/content-management',
    redirect: '/content'
  },
  {
    path: '/lead-management',
    redirect: '/leads'
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
    next('/dashboard')
    return
  }

  next()
})

export default router
