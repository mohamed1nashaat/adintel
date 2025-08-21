<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <!-- Logo -->
            <div class="flex-shrink-0 flex items-center">
              <h1 class="text-xl font-bold text-gray-900">{{ $t('app.name') }}</h1>
            </div>
            
            <!-- Navigation Links -->
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <router-link
                v-for="item in navigation"
                :key="item.name"
                :to="item.href"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                :class="[
                  $route.path === item.href
                    ? 'border-blue-500 text-gray-900'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                ]"
              >
                <component :is="item.icon" class="w-4 h-4 mr-2" />
                {{ $t(item.label) }}
              </router-link>
            </div>
          </div>

          <div class="flex items-center space-x-4">
            <!-- Objective Selector -->
            <ObjectiveSelector />
            
            <!-- Tenant Selector -->
            <TenantSelector />
            
            <!-- User Menu -->
            <UserMenu />
          </div>
        </div>
      </div>
    </nav>

    <!-- Page Content -->
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
      <router-view />
    </main>
  </div>
</template>

<script setup lang="ts">
import ObjectiveSelector from '@/components/ObjectiveSelector.vue'
import TenantSelector from '@/components/TenantSelector.vue'
import UserMenu from '@/components/UserMenu.vue'
import {
  CalendarDaysIcon,
  ChartBarIcon,
  ChartPieIcon,
  ChatBubbleLeftRightIcon,
  CogIcon,
  DocumentTextIcon,
  PencilSquareIcon,
  UserGroupIcon
} from '@heroicons/vue/24/outline'
import { useRoute } from 'vue-router'

const route = useRoute()

const navigation = [
  {
    name: 'dashboard',
    href: '/dashboard',
    label: 'navigation.dashboard',
    icon: ChartBarIcon
  },
  {
    name: 'content',
    href: '/content',
    label: 'Content Manager',
    icon: PencilSquareIcon
  },
  {
    name: 'leads',
    href: '/leads',
    label: 'Lead Management',
    icon: UserGroupIcon
  },
  {
    name: 'communications',
    href: '/communications',
    label: 'Communications',
    icon: ChatBubbleLeftRightIcon
  },
  {
    name: 'benchmarks',
    href: '/benchmarks',
    label: 'GCC Benchmarks',
    icon: ChartPieIcon
  },
  {
    name: 'scheduler',
    href: '/scheduler',
    label: 'Post Scheduler',
    icon: CalendarDaysIcon
  },
  {
    name: 'reports',
    href: '/reports',
    label: 'navigation.reports',
    icon: DocumentTextIcon
  },
  {
    name: 'integrations',
    href: '/integrations',
    label: 'navigation.integrations',
    icon: CogIcon
  }
]
</script>
