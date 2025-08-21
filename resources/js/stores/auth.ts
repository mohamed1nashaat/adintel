import axios from 'axios'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

export interface User {
  id: number
  name: string
  email: string
  default_tenant_id: number
}

export interface Tenant {
  id: number
  name: string
  slug: string
  role: 'admin' | 'viewer'
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const tenants = ref<Tenant[]>([])
  const currentTenant = ref<Tenant | null>(null)
  const loading = ref(false)

  const isAuthenticated = computed(() => !!user.value)
  const isAdmin = computed(() => currentTenant.value?.role === 'admin')

  async function login(email: string, password: string) {
    loading.value = true
    try {
      const response = await axios.post('/api/auth/login', { email, password })
      const { user: userData, token, tenants: userTenants } = response.data

      // Store auth data
      localStorage.setItem('auth_token', token)
      user.value = userData
      tenants.value = userTenants

      // Set current tenant (default or first available)
      const defaultTenant = userTenants.find((t: Tenant) => t.id === userData.default_tenant_id) || userTenants[0]
      if (defaultTenant) {
        setCurrentTenant(defaultTenant)
      }

      return response.data
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await axios.post('/api/auth/logout')
    } catch (error) {
      // Ignore logout errors
    } finally {
      // Clear local data
      localStorage.removeItem('auth_token')
      localStorage.removeItem('current_tenant_id')
      user.value = null
      tenants.value = []
      currentTenant.value = null
    }
  }

  async function fetchUser() {
    const response = await axios.get('/api/me')
    const { user: userData, tenants: userTenants, current_tenant } = response.data

    user.value = userData
    tenants.value = userTenants
    
    if (current_tenant) {
      currentTenant.value = current_tenant
    }

    return response.data
  }

  function setCurrentTenant(tenant: Tenant) {
    currentTenant.value = tenant
    localStorage.setItem('current_tenant_id', tenant.id.toString())
  }

  return {
    user,
    tenants,
    currentTenant,
    loading,
    isAuthenticated,
    isAdmin,
    login,
    logout,
    fetchUser,
    setCurrentTenant
  }
})
