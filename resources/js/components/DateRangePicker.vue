<template>
  <div class="relative">
    <Popover class="relative">
      <PopoverButton
        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      >
        <CalendarIcon class="h-4 w-4 mr-2" aria-hidden="true" />
        {{ formatDateRange() }}
        <ChevronDownIcon class="ml-2 -mr-1 h-4 w-4" aria-hidden="true" />
      </PopoverButton>

      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-1 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-1 opacity-0"
      >
        <PopoverPanel
          class="absolute z-10 mt-3 w-80 max-w-sm bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
        >
          <div class="p-4">
            <div class="space-y-4">
              <!-- Quick Presets -->
              <div>
                <h4 class="text-sm font-medium text-gray-900 mb-2">Quick Select</h4>
                <div class="grid grid-cols-2 gap-2">
                  <button
                    v-for="preset in presets"
                    :key="preset.key"
                    @click="selectPreset(preset)"
                    :class="[
                      'px-3 py-2 text-sm rounded-md border',
                      isActivePreset(preset)
                        ? 'bg-indigo-50 border-indigo-200 text-indigo-700'
                        : 'border-gray-200 text-gray-700 hover:bg-gray-50'
                    ]"
                  >
                    {{ $t(preset.label) }}
                  </button>
                </div>
              </div>

              <!-- Custom Date Range -->
              <div>
                <h4 class="text-sm font-medium text-gray-900 mb-2">Custom Range</h4>
                <div class="space-y-2">
                  <div>
                    <label class="block text-xs font-medium text-gray-700">From</label>
                    <input
                      v-model="customRange.from"
                      type="date"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-700">To</label>
                    <input
                      v-model="customRange.to"
                      type="date"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    />
                  </div>
                  <button
                    @click="applyCustomRange"
                    class="w-full px-3 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  >
                    Apply Custom Range
                  </button>
                </div>
              </div>
            </div>
          </div>
        </PopoverPanel>
      </transition>
    </Popover>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
import { CalendarIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'
import { useDashboardStore } from '@/stores/dashboard'

const dashboardStore = useDashboardStore()

const customRange = reactive({
  from: dashboardStore.dateRange.from,
  to: dashboardStore.dateRange.to
})

const presets = [
  {
    key: 'today',
    label: 'date.today',
    days: 0
  },
  {
    key: 'yesterday',
    label: 'date.yesterday',
    days: 1
  },
  {
    key: 'last_7_days',
    label: 'date.last_7_days',
    days: 7
  },
  {
    key: 'last_30_days',
    label: 'date.last_30_days',
    days: 30
  },
  {
    key: 'this_month',
    label: 'date.this_month',
    days: 'this_month'
  },
  {
    key: 'last_month',
    label: 'date.last_month',
    days: 'last_month'
  }
]

const formatDateRange = () => {
  const from = new Date(dashboardStore.dateRange.from)
  const to = new Date(dashboardStore.dateRange.to)
  
  const fromStr = from.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
  const toStr = to.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
  
  if (fromStr === toStr) {
    return fromStr
  }
  
  return `${fromStr} - ${toStr}`
}

const selectPreset = (preset: any) => {
  let from: Date
  let to: Date = new Date()

  if (preset.days === 'this_month') {
    from = new Date(to.getFullYear(), to.getMonth(), 1)
  } else if (preset.days === 'last_month') {
    from = new Date(to.getFullYear(), to.getMonth() - 1, 1)
    to = new Date(to.getFullYear(), to.getMonth(), 0)
  } else if (preset.days === 0) {
    from = new Date()
  } else if (preset.days === 1) {
    from = new Date(Date.now() - 24 * 60 * 60 * 1000)
    to = new Date(Date.now() - 24 * 60 * 60 * 1000)
  } else {
    from = new Date(Date.now() - preset.days * 24 * 60 * 60 * 1000)
  }

  dashboardStore.setDateRange({
    from: from.toISOString().split('T')[0],
    to: to.toISOString().split('T')[0]
  })

  // Update custom range inputs
  customRange.from = from.toISOString().split('T')[0]
  customRange.to = to.toISOString().split('T')[0]
}

const applyCustomRange = () => {
  if (customRange.from && customRange.to) {
    dashboardStore.setDateRange({
      from: customRange.from,
      to: customRange.to
    })
  }
}

const isActivePreset = (preset: any) => {
  // Simple check - could be more sophisticated
  const currentDays = Math.ceil(
    (new Date(dashboardStore.dateRange.to).getTime() - new Date(dashboardStore.dateRange.from).getTime()) 
    / (1000 * 60 * 60 * 24)
  )
  
  return preset.days === currentDays || (preset.days === 0 && currentDays === 0)
}
</script>
